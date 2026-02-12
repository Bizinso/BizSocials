<?php

declare(strict_types=1);

namespace App\Data\Content;

use App\Models\Content\Post;
use Spatie\LaravelData\Data;

final class PostData extends Data
{
    public function __construct(
        public string $id,
        public string $workspace_id,
        public string $author_id,
        public ?string $author_name,
        public ?string $content_text,
        public ?array $content_variations,
        public string $status,
        public string $post_type,
        public ?string $scheduled_at,
        public ?string $scheduled_timezone,
        public ?string $published_at,
        public ?array $hashtags,
        public ?array $mentions,
        public ?string $link_url,
        public ?array $link_preview,
        public ?string $first_comment,
        public ?string $rejection_reason,
        public int $target_count,
        public int $media_count,
        public string $created_at,
        public string $updated_at,
    ) {}

    /**
     * Create PostData from a Post model.
     */
    public static function fromModel(Post $post): self
    {
        return new self(
            id: $post->id,
            workspace_id: $post->workspace_id,
            author_id: $post->created_by_user_id,
            author_name: $post->author?->name,
            content_text: $post->content_text,
            content_variations: $post->content_variations,
            status: $post->status->value,
            post_type: $post->post_type->value,
            scheduled_at: $post->scheduled_at?->toIso8601String(),
            scheduled_timezone: $post->scheduled_timezone,
            published_at: $post->published_at?->toIso8601String(),
            hashtags: $post->hashtags,
            mentions: $post->mentions,
            link_url: $post->link_url,
            link_preview: $post->link_preview,
            first_comment: $post->first_comment,
            rejection_reason: $post->rejection_reason,
            target_count: $post->targets()->count(),
            media_count: $post->media()->count(),
            created_at: $post->created_at->toIso8601String(),
            updated_at: $post->updated_at->toIso8601String(),
        );
    }
}
