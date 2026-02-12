<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Data\Social\ConnectAccountData;
use App\Data\Social\HealthStatusData;
use App\Enums\Social\SocialAccountStatus;
use App\Enums\Social\SocialPlatform;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class SocialAccountService extends BaseService
{
    public function __construct(
        private readonly OAuthService $oauthService,
    ) {}

    /**
     * List social accounts for a workspace.
     *
     * @param array<string, mixed> $filters
     */
    public function listForWorkspace(Workspace $workspace, array $filters = []): LengthAwarePaginator
    {
        $query = SocialAccount::forWorkspace($workspace->id);

        // Apply status filter
        if (!empty($filters['status'])) {
            $status = SocialAccountStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        // Apply platform filter
        if (!empty($filters['platform'])) {
            $platform = SocialPlatform::tryFrom($filters['platform']);
            if ($platform !== null) {
                $query->forPlatform($platform);
            }
        }

        // Apply connected filter
        if (isset($filters['connected']) && $filters['connected']) {
            $query->connected();
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = min($perPage, 100); // Max 100 per page

        return $query
            ->orderBy('connected_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get a social account by ID.
     *
     * @throws ModelNotFoundException
     */
    public function getById(string $id): SocialAccount
    {
        $account = SocialAccount::find($id);

        if ($account === null) {
            throw new ModelNotFoundException('Social account not found.');
        }

        return $account;
    }

    /**
     * Get a social account by workspace and ID.
     *
     * @throws ValidationException
     */
    public function getByWorkspaceAndId(Workspace $workspace, string $id): SocialAccount
    {
        $account = SocialAccount::forWorkspace($workspace->id)
            ->where('id', $id)
            ->first();

        if ($account === null) {
            throw ValidationException::withMessages([
                'social_account' => ['Social account not found.'],
            ]);
        }

        return $account;
    }

    /**
     * Connect a new social account to a workspace.
     */
    public function connect(Workspace $workspace, User $user, ConnectAccountData $data): SocialAccount
    {
        return $this->transaction(function () use ($workspace, $user, $data) {
            // Check if this platform account is already connected to this workspace
            $existingAccount = SocialAccount::forWorkspace($workspace->id)
                ->where('platform', $data->platform)
                ->where('platform_account_id', $data->platform_account_id)
                ->first();

            if ($existingAccount !== null) {
                // If account exists, update tokens and reactivate it
                $expiresAt = $data->token_expires_at
                    ? Carbon::parse($data->token_expires_at)
                    : null;

                $existingAccount->updateTokens(
                    $data->access_token,
                    $data->refresh_token,
                    $expiresAt
                );

                $existingAccount->update([
                    'account_name' => $data->account_name,
                    'account_username' => $data->account_username,
                    'profile_image_url' => $data->profile_image_url,
                    'metadata' => array_merge(
                        $existingAccount->metadata ?? [],
                        $data->metadata ?? []
                    ),
                ]);

                $this->log('Social account reconnected', [
                    'account_id' => $existingAccount->id,
                    'workspace_id' => $workspace->id,
                    'platform' => $data->platform->value,
                ]);

                return $existingAccount->fresh();
            }

            // Build the account data with encrypted tokens
            $accountData = [
                'workspace_id' => $workspace->id,
                'platform' => $data->platform,
                'platform_account_id' => $data->platform_account_id,
                'account_name' => $data->account_name,
                'account_username' => $data->account_username,
                'profile_image_url' => $data->profile_image_url,
                'status' => SocialAccountStatus::CONNECTED,
                'connected_by_user_id' => $user->id,
                'connected_at' => now(),
                'last_refreshed_at' => now(),
                'metadata' => $data->metadata,
                'token_expires_at' => $data->token_expires_at !== null
                    ? Carbon::parse($data->token_expires_at)
                    : null,
            ];

            // Create the account
            $account = new SocialAccount($accountData);

            // Set encrypted tokens via the model's accessors
            $account->accessToken = $data->access_token;
            $account->refreshToken = $data->refresh_token;

            $account->save();

            $this->log('Social account connected', [
                'account_id' => $account->id,
                'workspace_id' => $workspace->id,
                'platform' => $data->platform->value,
                'user_id' => $user->id,
            ]);

            return $account;
        });
    }

    /**
     * Disconnect a social account.
     */
    public function disconnect(SocialAccount $account): void
    {
        $this->transaction(function () use ($account) {
            // Revoke token on the platform (if supported)
            try {
                $this->oauthService->revokeToken($account);
            } catch (\Exception $e) {
                // Log but don't fail - the platform might be unavailable
                $this->log('Failed to revoke token on platform', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ], 'warning');
            }

            // Mark the account as disconnected
            $account->disconnect();

            $this->log('Social account disconnected', [
                'account_id' => $account->id,
                'workspace_id' => $account->workspace_id,
                'platform' => $account->platform->value,
            ]);
        });
    }

    /**
     * Refresh the OAuth tokens for a social account.
     */
    public function refresh(SocialAccount $account): SocialAccount
    {
        return $this->transaction(function () use ($account) {
            if ($account->refreshToken === null) {
                throw ValidationException::withMessages([
                    'refresh_token' => ['No refresh token available for this account.'],
                ]);
            }

            try {
                $tokenData = $this->oauthService->refreshToken($account);

                $expiresAt = $tokenData->expires_in !== null
                    ? now()->addSeconds($tokenData->expires_in)
                    : null;

                $account->updateTokens(
                    $tokenData->access_token,
                    $tokenData->refresh_token,
                    $expiresAt
                );

                $this->log('Social account token refreshed', [
                    'account_id' => $account->id,
                    'workspace_id' => $account->workspace_id,
                    'platform' => $account->platform->value,
                ]);

                return $account;
            } catch (\Exception $e) {
                // Mark token as expired if refresh fails
                $account->markTokenExpired();

                $this->log('Token refresh failed', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ], 'error');

                throw ValidationException::withMessages([
                    'refresh' => ['Failed to refresh token: ' . $e->getMessage()],
                ]);
            }
        });
    }

    /**
     * Update the status of a social account.
     */
    public function updateStatus(SocialAccount $account, SocialAccountStatus $status): SocialAccount
    {
        return $this->transaction(function () use ($account, $status) {
            $account->status = $status;

            if ($status === SocialAccountStatus::DISCONNECTED) {
                $account->disconnected_at = now();
            }

            $account->save();

            $this->log('Social account status updated', [
                'account_id' => $account->id,
                'new_status' => $status->value,
            ]);

            return $account;
        });
    }

    /**
     * Get health status for all accounts in a workspace.
     */
    public function getHealthStatus(Workspace $workspace): HealthStatusData
    {
        $accounts = SocialAccount::forWorkspace($workspace->id)->get();

        $connected = $accounts->filter(fn ($a) => $a->status === SocialAccountStatus::CONNECTED)->count();
        $expired = $accounts->filter(fn ($a) => $a->status === SocialAccountStatus::TOKEN_EXPIRED)->count();
        $revoked = $accounts->filter(fn ($a) => $a->status === SocialAccountStatus::REVOKED)->count();
        $disconnected = $accounts->filter(fn ($a) => $a->status === SocialAccountStatus::DISCONNECTED)->count();

        // Group by platform
        $byPlatform = [];
        foreach (SocialPlatform::cases() as $platform) {
            $platformAccounts = $accounts->filter(fn ($a) => $a->platform === $platform);
            $platformConnected = $platformAccounts->filter(fn ($a) => $a->status === SocialAccountStatus::CONNECTED)->count();

            $byPlatform[$platform->value] = [
                'total' => $platformAccounts->count(),
                'connected' => $platformConnected,
            ];
        }

        return new HealthStatusData(
            total_accounts: $accounts->count(),
            connected_count: $connected,
            expired_count: $expired,
            revoked_count: $revoked,
            disconnected_count: $disconnected,
            by_platform: $byPlatform,
        );
    }

    /**
     * Get all accounts that need token refresh.
     *
     * @return Collection<int, SocialAccount>
     */
    public function getAccountsNeedingRefresh(int $daysBeforeExpiry = 7): Collection
    {
        return SocialAccount::needsTokenRefresh($daysBeforeExpiry)->get();
    }

    /**
     * Validate that a user can manage social accounts in a workspace.
     *
     * @throws ValidationException
     */
    public function validateUserCanManageSocialAccounts(User $user, Workspace $workspace): void
    {
        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            throw ValidationException::withMessages([
                'workspace' => ['Workspace not found.'],
            ]);
        }

        // Check if user is a member with appropriate role
        $role = $workspace->getMemberRole($user->id);

        if ($role === null) {
            // If user is not a member, check if they are tenant admin
            if (!$user->isAdmin()) {
                throw ValidationException::withMessages([
                    'permission' => ['You do not have access to this workspace.'],
                ]);
            }

            return;
        }

        // Check if the role can manage social accounts
        if (!$role->canManageSocialAccounts()) {
            throw ValidationException::withMessages([
                'permission' => ['You do not have permission to manage social accounts.'],
            ]);
        }
    }
}
