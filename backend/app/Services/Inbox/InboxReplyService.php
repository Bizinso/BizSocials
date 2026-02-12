<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Data\Inbox\CreateReplyData;
use App\Enums\Inbox\InboxItemType;
use App\Models\Inbox\InboxItem;
use App\Models\Inbox\InboxReply;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class InboxReplyService extends BaseService
{
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
            $reply = InboxReply::create([
                'inbox_item_id' => $item->id,
                'replied_by_user_id' => $user->id,
                'content_text' => $data->content_text,
                'sent_at' => now(),
                // In a real implementation, platform_reply_id would be set
                // after successfully posting to the platform
                'platform_reply_id' => null,
            ]);

            $this->log('Reply created', [
                'reply_id' => $reply->id,
                'inbox_item_id' => $item->id,
                'user_id' => $user->id,
            ]);

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
}
