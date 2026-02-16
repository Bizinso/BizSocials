<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Inbox\InboxItemType;
use App\Enums\Social\SocialPlatform;
use App\Models\Inbox\InboxItem;
use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;
use App\Services\Notification\NotificationBroadcastService;
use App\Services\Social\FacebookClient;
use App\Services\Social\InstagramClient;
use App\Services\Social\TwitterClient;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * MessageFetchingService
 *
 * Fetches messages (comments, mentions, DMs) from social media platforms
 * and stores them in the inbox_items table for unified inbox management.
 */
final class MessageFetchingService extends BaseService
{
    public function __construct(
        private readonly FacebookClient $facebookClient,
        private readonly InstagramClient $instagramClient,
        private readonly TwitterClient $twitterClient,
        private readonly NotificationBroadcastService $broadcastService,
        private readonly InboxNotificationService $inboxNotificationService,
    ) {}

    /**
     * Fetch messages from all connected social accounts for a workspace.
     *
     * @param Workspace $workspace
     * @param array<string, mixed> $options
     * @return array{success: bool, fetched: int, errors: array<string, string>}
     */
    public function fetchAllMessages(Workspace $workspace, array $options = []): array
    {
        $socialAccounts = SocialAccount::where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->get();

        $totalFetched = 0;
        $errors = [];

        foreach ($socialAccounts as $account) {
            try {
                $result = $this->fetchMessagesForAccount($account, $options);
                $totalFetched += $result['fetched'];
                
                if (!$result['success'] && isset($result['error'])) {
                    $errors[$account->id] = $result['error'];
                }
            } catch (\Exception $e) {
                $errors[$account->id] = $e->getMessage();
                Log::error('Failed to fetch messages for account', [
                    'account_id' => $account->id,
                    'platform' => $account->platform->value,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->log('Fetched messages for workspace', [
            'workspace_id' => $workspace->id,
            'total_fetched' => $totalFetched,
            'accounts_processed' => $socialAccounts->count(),
            'errors_count' => count($errors),
        ]);

        return [
            'success' => count($errors) === 0,
            'fetched' => $totalFetched,
            'errors' => $errors,
        ];
    }

    /**
     * Fetch messages for a specific social account.
     *
     * @param SocialAccount $account
     * @param array<string, mixed> $options
     * @return array{success: bool, fetched: int, error?: string}
     */
    public function fetchMessagesForAccount(SocialAccount $account, array $options = []): array
    {
        return match ($account->platform) {
            SocialPlatform::FACEBOOK => $this->fetchFacebookMessages($account, $options),
            SocialPlatform::INSTAGRAM => $this->fetchInstagramMessages($account, $options),
            SocialPlatform::TWITTER => $this->fetchTwitterMessages($account, $options),
            default => [
                'success' => false,
                'fetched' => 0,
                'error' => 'Platform not supported for message fetching',
            ],
        };
    }

    /**
     * Fetch messages from Facebook (comments on posts).
     *
     * @param SocialAccount $account
     * @param array<string, mixed> $options
     * @return array{success: bool, fetched: int, error?: string}
     */
    private function fetchFacebookMessages(SocialAccount $account, array $options = []): array
    {
        if (empty($account->access_token)) {
            return [
                'success' => false,
                'fetched' => 0,
                'error' => 'No access token available',
            ];
        }

        $fetchedCount = 0;

        // Fetch posts first to get comments from them
        $postsResult = $this->facebookClient->fetchPosts(
            $account->platform_account_id,
            $account->access_token,
            [
                'limit' => $options['posts_limit'] ?? 10,
                'since' => $options['since'] ?? null,
            ]
        );

        if (!$postsResult['success']) {
            return [
                'success' => false,
                'fetched' => 0,
                'error' => $postsResult['error'] ?? 'Failed to fetch posts',
            ];
        }

        $posts = $postsResult['posts'] ?? [];

        foreach ($posts as $post) {
            $postId = $post['id'] ?? null;
            if (!$postId) {
                continue;
            }

            // Fetch comments for this post
            $commentsResult = $this->facebookClient->fetchComments(
                $postId,
                $account->access_token,
                [
                    'limit' => $options['comments_limit'] ?? 50,
                ]
            );

            if ($commentsResult['success']) {
                $comments = $commentsResult['comments'] ?? [];
                
                foreach ($comments as $comment) {
                    $stored = $this->storeComment($account, $comment, $postId, SocialPlatform::FACEBOOK);
                    if ($stored) {
                        $fetchedCount++;
                    }
                }
            }
        }

        return [
            'success' => true,
            'fetched' => $fetchedCount,
        ];
    }

    /**
     * Fetch messages from Instagram (comments on posts).
     *
     * @param SocialAccount $account
     * @param array<string, mixed> $options
     * @return array{success: bool, fetched: int, error?: string}
     */
    private function fetchInstagramMessages(SocialAccount $account, array $options = []): array
    {
        if (empty($account->access_token)) {
            return [
                'success' => false,
                'fetched' => 0,
                'error' => 'No access token available',
            ];
        }

        $fetchedCount = 0;

        // Fetch media first to get comments from them
        $mediaResult = $this->instagramClient->fetchMedia(
            $account->platform_account_id,
            $account->access_token,
            [
                'limit' => $options['media_limit'] ?? 10,
            ]
        );

        if (!$mediaResult['success']) {
            return [
                'success' => false,
                'fetched' => 0,
                'error' => $mediaResult['error'] ?? 'Failed to fetch media',
            ];
        }

        $mediaItems = $mediaResult['media'] ?? [];

        foreach ($mediaItems as $media) {
            $mediaId = $media['id'] ?? null;
            if (!$mediaId) {
                continue;
            }

            // Fetch comments for this media
            $commentsResult = $this->instagramClient->fetchComments(
                $mediaId,
                $account->access_token,
                [
                    'limit' => $options['comments_limit'] ?? 50,
                ]
            );

            if ($commentsResult['success']) {
                $comments = $commentsResult['comments'] ?? [];
                
                foreach ($comments as $comment) {
                    $stored = $this->storeComment($account, $comment, $mediaId, SocialPlatform::INSTAGRAM);
                    if ($stored) {
                        $fetchedCount++;
                    }
                }
            }
        }

        return [
            'success' => true,
            'fetched' => $fetchedCount,
        ];
    }

    /**
     * Fetch messages from Twitter (mentions, replies).
     *
     * @param SocialAccount $account
     * @param array<string, mixed> $options
     * @return array{success: bool, fetched: int, error?: string}
     */
    private function fetchTwitterMessages(SocialAccount $account, array $options = []): array
    {
        // Twitter client is currently a stub implementation
        // This would need Twitter API v2 elevated access to implement
        return [
            'success' => false,
            'fetched' => 0,
            'error' => 'Twitter message fetching not yet implemented (requires API v2 elevated access)',
        ];
    }

    /**
     * Store a comment/message in the inbox_items table.
     *
     * @param SocialAccount $account
     * @param array<string, mixed> $comment
     * @param string $postId
     * @param SocialPlatform $platform
     * @return bool True if stored, false if already exists
     */
    private function storeComment(
        SocialAccount $account,
        array $comment,
        string $postId,
        SocialPlatform $platform
    ): bool {
        $commentId = $comment['id'] ?? null;
        if (!$commentId) {
            return false;
        }

        // Check if this comment already exists
        $exists = InboxItem::where('platform_item_id', $commentId)
            ->where('social_account_id', $account->id)
            ->exists();

        if ($exists) {
            return false;
        }

        // Extract author information based on platform
        $authorData = $this->extractAuthorData($comment, $platform);
        
        // Extract comment text
        $contentText = $this->extractContentText($comment, $platform);
        
        // Extract created time
        $createdAt = $this->extractCreatedTime($comment, $platform);

        // Create inbox item
        $inboxItem = InboxItem::create([
            'workspace_id' => $account->workspace_id,
            'social_account_id' => $account->id,
            'post_target_id' => null, // Could be linked to PostTarget if we track it
            'item_type' => InboxItemType::COMMENT,
            'status' => InboxItemStatus::UNREAD,
            'platform_item_id' => $commentId,
            'platform_post_id' => $postId,
            'author_name' => $authorData['name'],
            'author_username' => $authorData['username'] ?? null,
            'author_profile_url' => $authorData['profile_url'] ?? null,
            'author_avatar_url' => $authorData['avatar_url'] ?? null,
            'content_text' => $contentText,
            'platform_created_at' => $createdAt,
            'metadata' => [
                'raw_data' => $comment,
                'platform' => $platform->value,
            ],
        ]);

        // Broadcast the new inbox item for real-time updates
        $this->broadcastService->broadcastInboxItemReceived($inboxItem);

        // Send notifications to relevant users
        $this->inboxNotificationService->notifyNewMessage($inboxItem);

        return true;
    }

    /**
     * Extract author data from comment based on platform.
     *
     * @param array<string, mixed> $comment
     * @param SocialPlatform $platform
     * @return array{name: string, username: ?string, profile_url: ?string, avatar_url: ?string}
     */
    private function extractAuthorData(array $comment, SocialPlatform $platform): array
    {
        return match ($platform) {
            SocialPlatform::FACEBOOK => [
                'name' => $comment['from']['name'] ?? 'Unknown',
                'username' => null,
                'profile_url' => isset($comment['from']['id']) 
                    ? "https://facebook.com/{$comment['from']['id']}" 
                    : null,
                'avatar_url' => $comment['from']['picture']['data']['url'] ?? null,
            ],
            SocialPlatform::INSTAGRAM => [
                'name' => $comment['from']['username'] ?? 'Unknown',
                'username' => $comment['from']['username'] ?? null,
                'profile_url' => isset($comment['from']['username']) 
                    ? "https://instagram.com/{$comment['from']['username']}" 
                    : null,
                'avatar_url' => null, // Instagram API doesn't provide avatar in comments
            ],
            default => [
                'name' => 'Unknown',
                'username' => null,
                'profile_url' => null,
                'avatar_url' => null,
            ],
        };
    }

    /**
     * Extract content text from comment based on platform.
     *
     * @param array<string, mixed> $comment
     * @param SocialPlatform $platform
     * @return string
     */
    private function extractContentText(array $comment, SocialPlatform $platform): string
    {
        return match ($platform) {
            SocialPlatform::FACEBOOK => $comment['message'] ?? '',
            SocialPlatform::INSTAGRAM => $comment['text'] ?? '',
            default => '',
        };
    }

    /**
     * Extract created time from comment based on platform.
     *
     * @param array<string, mixed> $comment
     * @param SocialPlatform $platform
     * @return Carbon
     */
    private function extractCreatedTime(array $comment, SocialPlatform $platform): Carbon
    {
        $timestamp = match ($platform) {
            SocialPlatform::FACEBOOK => $comment['created_time'] ?? null,
            SocialPlatform::INSTAGRAM => $comment['timestamp'] ?? null,
            default => null,
        };

        if ($timestamp) {
            return Carbon::parse($timestamp);
        }

        return now();
    }

    /**
     * Fetch messages incrementally (only new messages since last fetch).
     *
     * @param SocialAccount $account
     * @return array{success: bool, fetched: int, error?: string}
     */
    public function fetchIncrementalMessages(SocialAccount $account): array
    {
        // Get the most recent message timestamp for this account
        $lastMessage = InboxItem::where('social_account_id', $account->id)
            ->orderByDesc('platform_created_at')
            ->first();

        $since = $lastMessage?->platform_created_at;

        return $this->fetchMessagesForAccount($account, [
            'since' => $since?->toIso8601String(),
        ]);
    }
}
