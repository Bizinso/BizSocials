<?php

declare(strict_types=1);

namespace App\Data\Inbox;

use App\Models\Inbox\InboxReply;
use Spatie\LaravelData\Data;

final class InboxReplyData extends Data
{
    public function __construct(
        public string $id,
        public string $inbox_item_id,
        public string $replied_by_user_id,
        public string $replied_by_name,
        public string $content_text,
        public ?string $platform_reply_id,
        public string $sent_at,
        public ?string $failed_at,
        public ?string $failure_reason,
        public string $created_at,
        public string $updated_at,
    ) {}

    /**
     * Create InboxReplyData from an InboxReply model.
     */
    public static function fromModel(InboxReply $reply): self
    {
        return new self(
            id: $reply->id,
            inbox_item_id: $reply->inbox_item_id,
            replied_by_user_id: $reply->replied_by_user_id,
            replied_by_name: $reply->repliedBy?->name ?? 'Unknown',
            content_text: $reply->content_text,
            platform_reply_id: $reply->platform_reply_id,
            sent_at: $reply->sent_at->toIso8601String(),
            failed_at: $reply->failed_at?->toIso8601String(),
            failure_reason: $reply->failure_reason,
            created_at: $reply->created_at->toIso8601String(),
            updated_at: $reply->updated_at->toIso8601String(),
        );
    }
}
