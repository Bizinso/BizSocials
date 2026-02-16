<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Data\Inbox\CreateReplyData;
use App\Enums\Inbox\InboxItemType;
use App\Enums\Social\SocialPlatform;
use App\Models\Inbox\InboxItem;
use App\Models\Inbox\InboxReply;
use App\Models\User;
use App\Services\BaseService;
use App\Services\Social\FacebookClient;
use App\Services\Social\InstagramClient;
use App\Services\Social\TwitterClient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class InboxReplyService extends BaseService
{
    public function __construct(
        private readonly FacebookClient $facebookClient,
        private readonly InstagramClient $instagramClient,
        private readonly TwitterClient $twitterClient,
    ) {
    }
    /**
     * List replies for an inbox item.
     *
     * @return Collection<int, InboxReply>
     */
    public function listForItem(InboxItem $item): Collection
    {
        return $item->replies()
            ->with(['repliedBy'])
            ->orderByDesc('sent_at')
            ->get();
    }

    /**
     * Create a reply to an inbox item.
     *
     * @throws ValidationException
     */
    public function create(InboxItem $item, User $user, CreateReplyData $data): InboxReply
    {
        // Validate that the item can be replied to
        if (!$item->item_type->canReply()) {
            throw ValidationException::withMessages([
                'inbox_item' => ['This item type cannot be replied to.'],
            ]);
        }

        return $this->transaction(function () use ($item, $user, $data) {
            // Create the reply record first
            $reply = InboxReply::create([
                'inbox_item_id' => $item->id,
                'replied_by_user_id' => $user->id,
                'content_text' => $data->content_text,
                'sent_at' => now(),
                'platform_reply_id' => null,
            ]);

            // Send the reply to the platform
            try {
                $platformReplyId = $this->sendReplyToPlatform($item, $data->content_text);
                $reply->markAsSent($platformReplyId);
                
                $this->log('Reply sent successfully', [
                    'reply_id' => $reply->id,
                    'inbox_item_id' => $item->id,
                    'user_id' => $user->id,
                    'platform_reply_id' => $platformReplyId,
                ]);
            } catch (\Exception $e) {
                $reply->markAsFailed($e->getMessage());
                
                $this->log('Reply failed to send', [
                    'reply_id' => $reply->id,
                    'inbox_item_id' => $item->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                
                throw ValidationException::withMessages([
                    'reply' => ['Failed to send reply: ' . $e->getMessage()],
                ]);
            }

            return $reply->fresh(['repliedBy']) ?? $reply;
        });
    }

    /**
     * Get a reply by ID.
     *
     * @throws ModelNotFoundException
     */
    public function get(string $id): InboxReply
    {
        $reply = InboxReply::with(['repliedBy', 'inboxItem'])
            ->find($id);

        if ($reply === null) {
            throw new ModelNotFoundException('Reply not found.');
        }

        return $reply;
    }

    /**
     * Mark a reply as sent successfully.
     */
    public function markAsSent(InboxReply $reply, string $platformReplyId): InboxReply
    {
        $reply->markAsSent($platformReplyId);

        $this->log('Reply marked as sent', [
            'reply_id' => $reply->id,
            'platform_reply_id' => $platformReplyId,
        ]);

        return $reply->fresh(['repliedBy']) ?? $reply;
    }

    /**
     * Mark a reply as failed.
     */
    public function markAsFailed(InboxReply $reply, string $reason): InboxReply
    {
        $reply->markAsFailed($reason);

        $this->log('Reply marked as failed', [
            'reply_id' => $reply->id,
            'reason' => $reason,
        ]);

        return $reply->fresh(['repliedBy']) ?? $reply;
    }

    /**
     * Send reply to the appropriate social platform.
     *
     * @throws \Exception
     */
    private function sendReplyToPlatform(InboxItem $item, string $message): string
    {
        $socialAccount = $item->socialAccount;
        $platform = $socialAccount->platform;
        $accessToken = $socialAccount->access_token;

        if (!$accessToken) {
            throw new \Exception('Social account access token not found');
        }

        $result = match ($platform) {
            SocialPlatform::FACEBOOK => $this->facebookClient->replyToComment(
                $item->platform_item_id,
                $accessToken,
                $message
            ),
            SocialPlatform::INSTAGRAM => $this->instagramClient->replyToComment(
                $item->platform_item_id,
                $accessToken,
                $message
            ),
            SocialPlatform::TWITTER => $this->sendTwitterReply($item, $accessToken, $message),
            default => throw new \Exception("Platform {$platform->value} does not support replies"),
        };

        if (!$result['success']) {
            throw new \Exception($result['error'] ?? 'Unknown error sending reply');
        }

        return $result['comment_id'] ?? $result['tweet_id'] ?? '';
    }

    /**
     * Send a reply to Twitter.
     *
     * @return array{success: bool, tweet_id?: string, error?: string}
     */
    private function sendTwitterReply(InboxItem $item, string $accessToken, string $message): array
    {
        // Twitter requires the tweet ID to reply to
        $tweetId = $item->platform_item_id;
        
        // Use TwitterClient to post a reply
        // Twitter API v2 requires posting a tweet with reply settings
        return $this->twitterClient->postTweet(
            $accessToken,
            $message,
            null, // No media
            $tweetId // Reply to tweet ID
        );
    }
}
