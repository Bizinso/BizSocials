<?php

declare(strict_types=1);

namespace App\Data\Inbox;

use App\Models\Inbox\InboxItem;
use Spatie\LaravelData\Data;

final class InboxItemData extends Data
{
    public function __construct(
        public string $id,
        public string $workspace_id,
        public string $social_account_id,
        public ?string $post_target_id,
        public string $item_type,
        public string $status,
        public string $platform_item_id,
        public ?string $platform_post_id,
        public string $author_name,
        public ?string $author_username,
        public ?string $author_profile_url,
        public ?string $author_avatar_url,
        public string $content_text,
        public string $platform_created_at,
        public ?string $assigned_to_user_id,
        public ?string $assigned_to_name,
        public ?string $assigned_at,
        public ?string $resolved_at,
        public ?string $resolved_by_user_id,
        public ?string $resolved_by_name,
        public int $reply_count,
        public string $created_at,
        public string $updated_at,
        public ?string $platform,
        public ?string $account_name,
    ) {}

    /**
     * Create InboxItemData from an InboxItem model.
     */
    public static function fromModel(InboxItem $item): self
    {
        return new self(
            id: $item->id,
            workspace_id: $item->workspace_id,
            social_account_id: $item->social_account_id,
            post_target_id: $item->post_target_id,
            item_type: $item->item_type->value,
            status: $item->status->value,
            platform_item_id: $item->platform_item_id,
            platform_post_id: $item->platform_post_id,
            author_name: $item->author_name,
            author_username: $item->author_username,
            author_profile_url: $item->author_profile_url,
            author_avatar_url: $item->author_avatar_url,
            content_text: $item->content_text,
            platform_created_at: $item->platform_created_at->toIso8601String(),
            assigned_to_user_id: $item->assigned_to_user_id,
            assigned_to_name: $item->assignedTo?->name,
            assigned_at: $item->assigned_at?->toIso8601String(),
            resolved_at: $item->resolved_at?->toIso8601String(),
            resolved_by_user_id: $item->resolved_by_user_id,
            resolved_by_name: $item->resolvedBy?->name,
            reply_count: $item->replies()->count(),
            created_at: $item->created_at->toIso8601String(),
            updated_at: $item->updated_at->toIso8601String(),
            platform: $item->socialAccount?->platform->value,
            account_name: $item->socialAccount?->account_name,
        );
    }
}
