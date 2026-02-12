<?php

declare(strict_types=1);

namespace App\Jobs\Social;

use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationType;
use App\Enums\Social\SocialAccountStatus;
use App\Models\Notification\Notification;
use App\Models\Social\SocialAccount;
use App\Services\Social\OAuthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * RefreshExpiringTokensJob
 *
 * Runs daily to find social accounts with tokens expiring in the next 7 days
 * and attempts to refresh them automatically. For accounts that cannot be
 * refreshed automatically, it sends notifications to the users to reconnect.
 *
 * Features:
 * - Finds accounts with tokens expiring in 7 days
 * - Attempts automatic token refresh using refresh tokens
 * - Sends notifications for accounts requiring manual reconnection
 * - Updates account status based on refresh outcome
 * - Handles platform-specific token refresh logic
 */
final class RefreshExpiringTokensJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 600;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [60, 120, 300];

    /**
     * Number of days before expiration to start refreshing.
     */
    private int $daysBeforeExpiry;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $daysBeforeExpiry = 7,
    ) {
        $this->onQueue('social');
        $this->daysBeforeExpiry = $daysBeforeExpiry;
    }

    /**
     * Execute the job.
     */
    public function handle(OAuthService $oauthService): void
    {
        Log::info('[RefreshExpiringTokensJob] Starting token refresh check', [
            'days_before_expiry' => $this->daysBeforeExpiry,
        ]);

        // Find accounts with tokens expiring soon
        $expiringAccounts = SocialAccount::query()
            ->needsTokenRefresh($this->daysBeforeExpiry)
            ->with(['workspace', 'connectedBy'])
            ->get();

        if ($expiringAccounts->isEmpty()) {
            Log::debug('[RefreshExpiringTokensJob] No accounts with expiring tokens found');
            return;
        }

        Log::info('[RefreshExpiringTokensJob] Found accounts with expiring tokens', [
            'count' => $expiringAccounts->count(),
        ]);

        $refreshedCount = 0;
        $needsReconnectCount = 0;
        $failedCount = 0;

        foreach ($expiringAccounts as $account) {
            try {
                $result = $this->processAccount($account, $oauthService);

                if ($result === 'refreshed') {
                    $refreshedCount++;
                } elseif ($result === 'needs_reconnect') {
                    $needsReconnectCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Throwable $e) {
                $failedCount++;
                Log::error('[RefreshExpiringTokensJob] Failed to process account', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[RefreshExpiringTokensJob] Token refresh completed', [
            'total_processed' => $expiringAccounts->count(),
            'refreshed' => $refreshedCount,
            'needs_reconnect' => $needsReconnectCount,
            'failed' => $failedCount,
        ]);
    }

    /**
     * Process a single account for token refresh.
     *
     * @return string 'refreshed', 'needs_reconnect', or 'failed'
     */
    private function processAccount(SocialAccount $account, OAuthService $oauthService): string
    {
        Log::debug('[RefreshExpiringTokensJob] Processing account', [
            'account_id' => $account->id,
            'platform' => $account->platform->value,
            'expires_at' => $account->token_expires_at?->toIso8601String(),
        ]);

        // Check if we have a refresh token
        if ($account->refreshToken === null) {
            Log::debug('[RefreshExpiringTokensJob] No refresh token available', [
                'account_id' => $account->id,
            ]);

            $this->sendReconnectNotification($account, 'No refresh token available');
            return 'needs_reconnect';
        }

        try {
            // Attempt to refresh the token - returns OAuthTokenData on success
            $tokenData = $oauthService->refreshToken($account);

            // Update the account with new tokens
            $account->updateTokens(
                accessToken: $tokenData->accessToken,
                refreshToken: $tokenData->refreshToken,
                expiresAt: $tokenData->expiresAt,
            );

            Log::info('[RefreshExpiringTokensJob] Token refreshed successfully', [
                'account_id' => $account->id,
                'platform' => $account->platform->value,
                'new_expires_at' => $tokenData->expiresAt?->toIso8601String(),
            ]);

            return 'refreshed';
        } catch (\Throwable $e) {
            Log::error('[RefreshExpiringTokensJob] Token refresh threw exception', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            // Check if this is a permanent error (token revoked)
            if ($this->isPermanentError($e)) {
                $account->markRevoked();
                $this->sendReconnectNotification($account, 'Access was revoked');
                return 'needs_reconnect';
            }

            return 'failed';
        }
    }

    /**
     * Check if an error is a permanent OAuth error (token revoked, etc.).
     */
    private function isPermanentError(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        $permanentErrorIndicators = [
            'revoked',
            'invalid_grant',
            'access_denied',
            'unauthorized',
            'token has been expired or revoked',
            'user has not authorized',
        ];

        foreach ($permanentErrorIndicators as $indicator) {
            if (str_contains($message, $indicator)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send a notification to reconnect the social account.
     */
    private function sendReconnectNotification(SocialAccount $account, string $reason): void
    {
        $account->loadMissing('connectedBy');

        $user = $account->connectedBy;

        if ($user === null) {
            Log::warning('[RefreshExpiringTokensJob] No user to notify for account', [
                'account_id' => $account->id,
            ]);
            return;
        }

        // Check if we already sent a notification recently
        $recentNotification = Notification::query()
            ->where('user_id', $user->id)
            ->where('type', NotificationType::ACCOUNT_TOKEN_EXPIRING)
            ->where('created_at', '>', now()->subDays(1))
            ->whereJsonContains('data->account_id', $account->id)
            ->exists();

        if ($recentNotification) {
            Log::debug('[RefreshExpiringTokensJob] Skipping notification - sent recently', [
                'account_id' => $account->id,
                'user_id' => $user->id,
            ]);
            return;
        }

        try {
            Notification::createForUser(
                user: $user,
                type: NotificationType::ACCOUNT_TOKEN_EXPIRING,
                title: 'Social Account Needs Reconnection',
                message: sprintf(
                    'Your %s account "%s" needs to be reconnected. %s',
                    $account->platform->value,
                    $account->account_name,
                    $reason
                ),
                channel: NotificationChannel::IN_APP,
                data: [
                    'account_id' => $account->id,
                    'workspace_id' => $account->workspace_id,
                    'platform' => $account->platform->value,
                    'reason' => $reason,
                ],
                actionUrl: "/workspaces/{$account->workspace_id}/settings/social-accounts",
            );

            Log::debug('[RefreshExpiringTokensJob] Reconnect notification sent', [
                'account_id' => $account->id,
                'user_id' => $user->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[RefreshExpiringTokensJob] Failed to send notification', [
                'account_id' => $account->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('[RefreshExpiringTokensJob] Job failed', [
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);
    }
}
