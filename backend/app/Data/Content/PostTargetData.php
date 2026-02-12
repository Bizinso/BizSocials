<?php

declare(strict_types=1);

namespace App\Data\Content;

use App\Models\Content\PostTarget;
use Spatie\LaravelData\Data;

final class PostTargetData extends Data
{
    public function __construct(
        public string $id,
        public string $post_id,
        public string $social_account_id,
        public string $platform,
        public string $account_name,
        public string $status,
        public ?string $platform_post_id,
        public ?string $platform_post_url,
        public ?string $published_at,
        public ?string $error_message,
    ) {}

    /**
     * Create PostTargetData from a PostTarget model.
     */
    public static function fromModel(PostTarget $target): self
    {
        return new self(
            id: $target->id,
            post_id: $target->post_id,
            social_account_id: $target->social_account_id,
            platform: $target->platform_code,
            account_name: $target->socialAccount?->account_name ?? 'Unknown Account',
            status: $target->status->value,
            platform_post_id: $target->external_post_id,
            platform_post_url: $target->external_post_url,
            published_at: $target->published_at?->toIso8601String(),
            error_message: $target->error_message,
        );
    }
}
