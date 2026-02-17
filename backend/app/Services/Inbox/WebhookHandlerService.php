<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Inbox\InboxItemType;
use App\Enums\Social\SocialPlatform;
use App\Models\Inbox\InboxItem;
use App\Models\Social\SocialAccount;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * WebhookHandlerService
 *
 * Handles incoming webhooks from social media platforms for real-time
 * message notifications (comments, mentions, DMs).
 */
final class WebhookHandlerService extends BaseService
{
    /**
     * Handle Facebook webhook payload.
     *
     * @param array<string, mixed> $payload
     * @param string $signature
     * @param string $appSecret
     * @return array{success: bool, processed: int, error?: string}
     * @throws ValidationException
     */
    public function handleFacebookWebhook(array $payload, string $signature, string $appSecret): array
    {
        // Verify webhook signature
        if (!$this->verifyFacebookSignature($payload, $signature, $appSecret)) {
            throw ValidationException::withMessages([
                'signature' => ['Invalid webhook signature'],
            ]);
        }

        $processed = 0;

        // Facebook sends an array of entries
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $pageId = $entry['id'] ?? null;
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $field = $change['field'] ?? null;
                $value = $change['value'] ?? [];

                // Handle different webhook fields
                if ($field === 'feed' && isset($value['item']) && $value['item'] === 'comment') {
                    $result = $this->processFacebookComment($pageId, $value);
                    if ($result) {
                        $processed++;
                    }
                }
            }
        }

        $this->log('Processed Facebook webhook', [
            'processed' => $processed,
        ]);

        return [
            'success' => true,
            'processed' => $processed,
        ];
    }

    /**
     * Handle Instagram webhook payload.
     *
     * @param array<string, mixed> $payload
     * @param string $signature
     * @param string $appSecret
     * @return array{success: bool, processed: int, error?: string}
     * @throws ValidationException
     */
    public function handleInstagramWebhook(array $payload, string $signature, string $appSecret): array
    {
        // Verify webhook signature (same as Facebook)
        if (!$this->verifyFacebookSignature($payload, $signature, $appSecret)) {
            throw ValidationException::withMessages([
                'signature' => ['Invalid webhook signature'],
            ]);
        }

        $processed = 0;

        // Instagram webhooks have similar structure to Facebook
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $instagramAccountId = $entry['id'] ?? null;
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $field = $change['field'] ?? null;
                $value = $change['value'] ?? [];

                // Handle comments
                if ($field === 'comments' && isset($value['id'])) {
                    $result = $this->processInstagramComment($instagramAccountId, $value);
                    if ($result) {
                        $processed++;
                    }
                }

                // Handle mentions in stories
                if ($field === 'mentions' && isset($value['media_id'])) {
                    $result = $this->processInstagramMention($instagramAccountId, $value);
                    if ($result) {
                        $processed++;
                    }
                }
            }
        }

        $this->log('Processed Instagram webhook', [
            'processed' => $processed,
        ]);

        return [
            'success' => true,
            'processed' => $processed,
        ];
    }

    /**
     * Handle Twitter webhook payload.
     *
     * @param array<string, mixed> $payload
     * @param string $signature
     * @param string $consumerSecret
     * @return array{success: bool, processed: int, error?: string}
     * @throws ValidationException
     */
    public function handleTwitterWebhook(array $payload, string $signature, string $consumerSecret): array
    {
        // Verify Twitter webhook signature
        if (!$this->verifyTwitterSignature($payload, $signature, $consumerSecret)) {
            throw ValidationException::withMessages([
                'signature' => ['Invalid webhook signature'],
            ]);
        }

        $processed = 0;

        // Handle tweet mentions
        if (isset($payload['tweet_create_events'])) {
            foreach ($payload['tweet_create_events'] as $tweet) {
                $result = $this->processTwitterMention($tweet);
                if ($result) {
                    $processed++;
                }
            }
        }

        // Handle direct messages
        if (isset($payload['direct_message_events'])) {
            foreach ($payload['direct_message_events'] as $dm) {
                $result = $this->processTwitterDirectMessage($dm);
                if ($result) {
                    $processed++;
                }
            }
        }

        $this->log('Processed Twitter webhook', [
            'processed' => $processed,
        ]);

        return [
            'success' => true,
            'processed' => $processed,
        ];
    }

    /**
     * Verify Facebook/Instagram webhook signature.
     *
     * @param array<string, mixed> $payload
     * @param string $signature
     * @param string $appSecret
     * @return bool
     */
    private function verifyFacebookSignature(array $payload, string $signature, string $appSecret): bool
    {
        // Facebook sends signature as "sha256=<hash>"
        if (!str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expectedHash = substr($signature, 7);
        $payloadJson = json_encode($payload);
        $calculatedHash = hash_hmac('sha256', $payloadJson, $appSecret);

        return hash_equals($expectedHash, $calculatedHash);
    }

    /**
     * Verify Twitter webhook signature.
     *
     * @param array<string, mixed> $payload
     * @param string $signature
     * @param string $consumerSecret
     * @return bool
     */
    private function verifyTwitterSignature(array $payload, string $signature, string $consumerSecret): bool
    {
        // Twitter uses HMAC-SHA256 with base64 encoding
        if (!str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expectedHash = substr($signature, 7);
        $payloadJson = json_encode($payload);
        $calculatedHash = base64_encode(hash_hmac('sha256', $payloadJson, $consumerSecret, true));

        return hash_equals($expectedHash, $calculatedHash);
    }

    /**
     * Process a Facebook comment from webhook.
     *
     * @param string|null $pageId
     * @param array<string, mixed> $commentData
     * @return bool
     */
    private function processFacebookComment(?string $pageId, array $commentData): bool
    {
        $commentId = $commentData['comment_id'] ?? null;
        $postId = $commentData['post_id'] ?? null;
        
        if (!$commentId || !$postId || !$pageId) {
            return false;
        }

        // Find the social account for this page
        $account = SocialAccount::where('platform', SocialPlatform::FACEBOOK)
            ->where('platform_account_id', $pageId)
            ->first();

        if (!$account) {
            Log::warning('No social account found for Facebook page', ['page_id' => $pageId]);
            return false;
        }

        // Check if comment already exists
        $exists = InboxItem::where('platform_item_id', $commentId)
            ->where('social_account_id', $account->id)
            ->exists();

        if ($exists) {
            return false;
        }

        // Create inbox item
        InboxItem::create([
            'workspace_id' => $account->workspace_id,
            'social_account_id' => $account->id,
            'post_target_id' => null,
            'item_type' => InboxItemType::COMMENT,
            'status' => InboxItemStatus::UNREAD,
            'platform_item_id' => $commentId,
            'platform_post_id' => $postId,
            'author_name' => $commentData['from']['name'] ?? 'Unknown',
            'author_username' => null,
            'author_profile_url' => isset($commentData['from']['id']) 
                ? "https://facebook.com/{$commentData['from']['id']}" 
                : null,
            'author_avatar_url' => null,
            'content_text' => $commentData['message'] ?? '',
            'platform_created_at' => isset($commentData['created_time']) 
                ? Carbon::parse($commentData['created_time']) 
                : now(),
            'metadata' => [
                'raw_data' => $commentData,
                'platform' => 'facebook',
                'webhook' => true,
            ],
        ]);

        return true;
    }

    /**
     * Process an Instagram comment from webhook.
     *
     * @param string|null $instagramAccountId
     * @param array<string, mixed> $commentData
     * @return bool
     */
    private function processInstagramComment(?string $instagramAccountId, array $commentData): bool
    {
        $commentId = $commentData['id'] ?? null;
        $mediaId = $commentData['media_id'] ?? null;
        
        if (!$commentId || !$mediaId || !$instagramAccountId) {
            return false;
        }

        // Find the social account for this Instagram account
        $account = SocialAccount::where('platform', SocialPlatform::INSTAGRAM)
            ->where('platform_account_id', $instagramAccountId)
            ->first();

        if (!$account) {
            Log::warning('No social account found for Instagram account', ['account_id' => $instagramAccountId]);
            return false;
        }

        // Check if comment already exists
        $exists = InboxItem::where('platform_item_id', $commentId)
            ->where('social_account_id', $account->id)
            ->exists();

        if ($exists) {
            return false;
        }

        // Create inbox item
        InboxItem::create([
            'workspace_id' => $account->workspace_id,
            'social_account_id' => $account->id,
            'post_target_id' => null,
            'item_type' => InboxItemType::COMMENT,
            'status' => InboxItemStatus::UNREAD,
            'platform_item_id' => $commentId,
            'platform_post_id' => $mediaId,
            'author_name' => $commentData['from']['username'] ?? 'Unknown',
            'author_username' => $commentData['from']['username'] ?? null,
            'author_profile_url' => isset($commentData['from']['username']) 
                ? "https://instagram.com/{$commentData['from']['username']}" 
                : null,
            'author_avatar_url' => null,
            'content_text' => $commentData['text'] ?? '',
            'platform_created_at' => now(),
            'metadata' => [
                'raw_data' => $commentData,
                'platform' => 'instagram',
                'webhook' => true,
            ],
        ]);

        return true;
    }

    /**
     * Process an Instagram mention from webhook.
     *
     * @param string|null $instagramAccountId
     * @param array<string, mixed> $mentionData
     * @return bool
     */
    private function processInstagramMention(?string $instagramAccountId, array $mentionData): bool
    {
        $mediaId = $mentionData['media_id'] ?? null;
        $commentId = $mentionData['comment_id'] ?? null;
        
        if (!$mediaId || !$instagramAccountId) {
            return false;
        }

        // Find the social account
        $account = SocialAccount::where('platform', SocialPlatform::INSTAGRAM)
            ->where('platform_account_id', $instagramAccountId)
            ->first();

        if (!$account) {
            return false;
        }

        // Use comment_id if available, otherwise use media_id as unique identifier
        $uniqueId = $commentId ?? "mention_{$mediaId}";

        // Check if mention already exists
        $exists = InboxItem::where('platform_item_id', $uniqueId)
            ->where('social_account_id', $account->id)
            ->exists();

        if ($exists) {
            return false;
        }

        // Create inbox item
        InboxItem::create([
            'workspace_id' => $account->workspace_id,
            'social_account_id' => $account->id,
            'post_target_id' => null,
            'item_type' => InboxItemType::MENTION,
            'status' => InboxItemStatus::UNREAD,
            'platform_item_id' => $uniqueId,
            'platform_post_id' => $mediaId,
            'author_name' => 'Unknown',
            'author_username' => null,
            'author_profile_url' => null,
            'author_avatar_url' => null,
            'content_text' => 'You were mentioned in a story',
            'platform_created_at' => now(),
            'metadata' => [
                'raw_data' => $mentionData,
                'platform' => 'instagram',
                'webhook' => true,
                'type' => 'story_mention',
            ],
        ]);

        return true;
    }

    /**
     * Process a Twitter mention from webhook.
     *
     * @param array<string, mixed> $tweetData
     * @return bool
     */
    private function processTwitterMention(array $tweetData): bool
    {
        $tweetId = $tweetData['id_str'] ?? null;
        $userId = $tweetData['user']['id_str'] ?? null;
        
        if (!$tweetId || !$userId) {
            return false;
        }

        // Find the social account being mentioned
        // This requires checking if the tweet mentions our connected account
        $mentionedUsernames = array_map(
            fn($mention) => $mention['screen_name'] ?? null,
            $tweetData['entities']['user_mentions'] ?? []
        );

        $account = SocialAccount::where('platform', SocialPlatform::TWITTER)
            ->whereIn('platform_username', $mentionedUsernames)
            ->first();

        if (!$account) {
            return false;
        }

        // Check if tweet already exists
        $exists = InboxItem::where('platform_item_id', $tweetId)
            ->where('social_account_id', $account->id)
            ->exists();

        if ($exists) {
            return false;
        }

        // Create inbox item
        InboxItem::create([
            'workspace_id' => $account->workspace_id,
            'social_account_id' => $account->id,
            'post_target_id' => null,
            'item_type' => InboxItemType::MENTION,
            'status' => InboxItemStatus::UNREAD,
            'platform_item_id' => $tweetId,
            'platform_post_id' => $tweetId,
            'author_name' => $tweetData['user']['name'] ?? 'Unknown',
            'author_username' => $tweetData['user']['screen_name'] ?? null,
            'author_profile_url' => isset($tweetData['user']['screen_name']) 
                ? "https://twitter.com/{$tweetData['user']['screen_name']}" 
                : null,
            'author_avatar_url' => $tweetData['user']['profile_image_url_https'] ?? null,
            'content_text' => $tweetData['text'] ?? '',
            'platform_created_at' => isset($tweetData['created_at']) 
                ? Carbon::parse($tweetData['created_at']) 
                : now(),
            'metadata' => [
                'raw_data' => $tweetData,
                'platform' => 'twitter',
                'webhook' => true,
            ],
        ]);

        return true;
    }

    /**
     * Process a Twitter direct message from webhook.
     *
     * @param array<string, mixed> $dmData
     * @return bool
     */
    private function processTwitterDirectMessage(array $dmData): bool
    {
        $messageId = $dmData['id'] ?? null;
        $senderId = $dmData['message_create']['sender_id'] ?? null;
        $recipientId = $dmData['message_create']['target']['recipient_id'] ?? null;
        
        if (!$messageId || !$senderId || !$recipientId) {
            return false;
        }

        // Find the social account (recipient)
        $account = SocialAccount::where('platform', SocialPlatform::TWITTER)
            ->where('platform_account_id', $recipientId)
            ->first();

        if (!$account) {
            return false;
        }

        // Check if DM already exists
        $exists = InboxItem::where('platform_item_id', $messageId)
            ->where('social_account_id', $account->id)
            ->exists();

        if ($exists) {
            return false;
        }

        // Create inbox item
        InboxItem::create([
            'workspace_id' => $account->workspace_id,
            'social_account_id' => $account->id,
            'post_target_id' => null,
            'item_type' => InboxItemType::DIRECT_MESSAGE,
            'status' => InboxItemStatus::UNREAD,
            'platform_item_id' => $messageId,
            'platform_post_id' => null,
            'author_name' => 'Twitter User',
            'author_username' => null,
            'author_profile_url' => null,
            'author_avatar_url' => null,
            'content_text' => $dmData['message_create']['message_data']['text'] ?? '',
            'platform_created_at' => isset($dmData['created_timestamp']) 
                ? Carbon::createFromTimestampMs((int)$dmData['created_timestamp']) 
                : now(),
            'metadata' => [
                'raw_data' => $dmData,
                'platform' => 'twitter',
                'webhook' => true,
                'sender_id' => $senderId,
            ],
        ]);

        return true;
    }
}
