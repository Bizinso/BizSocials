<?php

declare(strict_types=1);

namespace App\Jobs\Inbox;

use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Inbox\InboxItemType;
use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationType;
use App\Enums\Social\SocialAccountStatus;
use App\Models\Inbox\InboxItem;
use App\Models\Notification\Notification;
use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use App\Services\Social\SocialPlatformAdapterFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SyncInboxJob
 *
 * Syncs comments and mentions from connected social accounts for a workspace.
 * This job fetches new engagement items from social platforms and creates
 * InboxItem records for the unified inbox.
 *
 * Features:
 * - Processes all connected social accounts in the workspace
 * - Creates InboxItem records for new comments and mentions
 * - Sends notifications for high priority items (mentions)
 * - Handles platform API rate limits gracefully
 */
final class SyncInboxJob implements ShouldQueue
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
    public int $timeout = 300;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [30, 60, 120];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $workspaceId,
    ) {
        $this->onQueue('inbox');
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "sync-inbox-{$this->workspaceId}";
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[SyncInboxJob] Starting inbox sync', [
            'workspace_id' => $this->workspaceId,
        ]);

        $workspace = Workspace::find($this->workspaceId);

        if ($workspace === null) {
            Log::warning('[SyncInboxJob] Workspace not found', [
                'workspace_id' => $this->workspaceId,
            ]);
            return;
        }

        $socialAccounts = SocialAccount::query()
            ->where('workspace_id', $this->workspaceId)
            ->where('status', SocialAccountStatus::CONNECTED)
            ->get();

        if ($socialAccounts->isEmpty()) {
            Log::debug('[SyncInboxJob] No connected social accounts found', [
                'workspace_id' => $this->workspaceId,
            ]);
            return;
        }

        $totalNewItems = 0;
        $highPriorityItems = [];

        foreach ($socialAccounts as $account) {
            try {
                $result = $this->syncAccountInbox($account);
                $totalNewItems += $result['new_items'];
                $highPriorityItems = array_merge($highPriorityItems, $result['high_priority']);

                Log::debug('[SyncInboxJob] Synced account', [
                    'account_id' => $account->id,
                    'platform' => $account->platform->value,
                    'new_items' => $result['new_items'],
                ]);
            } catch (\Throwable $e) {
                Log::error('[SyncInboxJob] Failed to sync account', [
                    'account_id' => $account->id,
                    'platform' => $account->platform->value,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Send notifications for high priority items
        $this->sendHighPriorityNotifications($workspace, $highPriorityItems);

        Log::info('[SyncInboxJob] Completed inbox sync', [
            'workspace_id' => $this->workspaceId,
            'accounts_processed' => $socialAccounts->count(),
            'total_new_items' => $totalNewItems,
            'high_priority_count' => count($highPriorityItems),
        ]);
    }

    /**
     * Sync inbox items for a single social account.
     *
     * @return array{new_items: int, high_priority: array<InboxItem>}
     */
    private function syncAccountInbox(SocialAccount $account): array
    {
        $newItems = 0;
        $highPriorityItems = [];

        // Fetch items from the platform API
        // NOTE: This is a stub implementation. Actual platform integration
        // will be implemented in future tasks using platform-specific adapters.
        $platformItems = $this->fetchPlatformItems($account);

        foreach ($platformItems as $item) {
            // Check if item already exists
            $exists = InboxItem::query()
                ->where('social_account_id', $account->id)
                ->where('platform_item_id', $item['platform_item_id'])
                ->exists();

            if ($exists) {
                continue;
            }

            // Create new inbox item
            $inboxItem = InboxItem::create([
                'workspace_id' => $account->workspace_id,
                'social_account_id' => $account->id,
                'post_target_id' => $item['post_target_id'] ?? null,
                'item_type' => $item['type'],
                'status' => InboxItemStatus::UNREAD,
                'platform_item_id' => $item['platform_item_id'],
                'platform_post_id' => $item['platform_post_id'] ?? null,
                'author_name' => $item['author_name'],
                'author_username' => $item['author_username'] ?? null,
                'author_profile_url' => $item['author_profile_url'] ?? null,
                'author_avatar_url' => $item['author_avatar_url'] ?? null,
                'content_text' => $item['content_text'],
                'platform_created_at' => $item['platform_created_at'],
                'metadata' => $item['metadata'] ?? null,
            ]);

            $newItems++;

            // Track high priority items (mentions)
            if ($item['type'] === InboxItemType::MENTION) {
                $highPriorityItems[] = $inboxItem;
            }
        }

        return [
            'new_items' => $newItems,
            'high_priority' => $highPriorityItems,
        ];
    }

    /**
     * Fetch items from the social platform.
     *
     * @return array<array<string, mixed>>
     */
    private function fetchPlatformItems(SocialAccount $account): array
    {
        Log::debug('[SyncInboxJob] Fetching platform items', [
            'account_id' => $account->id,
            'platform' => $account->platform->value,
        ]);

        $factory = app(SocialPlatformAdapterFactory::class);
        $adapter = $factory->create($account->platform);

        // Fetch items since the last sync (or last 24 hours)
        $lastItem = InboxItem::query()
            ->where('social_account_id', $account->id)
            ->orderByDesc('platform_created_at')
            ->first();

        $since = $lastItem?->platform_created_at ?? now()->subDay();

        return $adapter->fetchInboxItems($account, $since);
    }

    /**
     * Send notifications for high priority inbox items.
     *
     * @param  array<InboxItem>  $items
     */
    private function sendHighPriorityNotifications(Workspace $workspace, array $items): void
    {
        if (empty($items)) {
            return;
        }

        // Get workspace members who should receive notifications
        $workspace->loadMissing('members');

        foreach ($workspace->members as $member) {
            try {
                $itemCount = count($items);
                $firstItem = $items[0];

                Notification::createForUser(
                    user: $member,
                    type: NotificationType::NEW_MENTION,
                    title: $itemCount === 1
                        ? 'New Mention'
                        : sprintf('%d New Mentions', $itemCount),
                    message: $itemCount === 1
                        ? sprintf('%s mentioned you: "%s"', $firstItem->author_name, $this->truncate($firstItem->content_text, 100))
                        : sprintf('You have %d new mentions across your social accounts.', $itemCount),
                    channel: NotificationChannel::IN_APP,
                    data: [
                        'workspace_id' => $workspace->id,
                        'item_count' => $itemCount,
                        'first_item_id' => $firstItem->id,
                    ],
                    actionUrl: "/workspaces/{$workspace->id}/inbox",
                );
            } catch (\Throwable $e) {
                Log::warning('[SyncInboxJob] Failed to send notification', [
                    'user_id' => $member->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Truncate a string to a maximum length.
     */
    private function truncate(string $text, int $maxLength): string
    {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return mb_substr($text, 0, $maxLength - 3) . '...';
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('[SyncInboxJob] Job failed', [
            'workspace_id' => $this->workspaceId,
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);
    }
}
