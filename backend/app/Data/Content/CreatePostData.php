<?php

declare(strict_types=1);

namespace App\Data\Content;

use App\Enums\Content\PostType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class CreatePostData extends Data
{
    /**
     * @param array<string, mixed>|null $content_variations
     * @param array<string>|null $hashtags
     * @param array<string>|null $mentions
     * @param array<string>|null $social_account_ids
     */
    public function __construct(
        #[Nullable, StringType]
        public ?string $content_text = null,
        public ?array $content_variations = null,
        public PostType $post_type = PostType::STANDARD,
        public ?string $scheduled_at = null,
        public ?string $scheduled_timezone = null,
        public ?array $hashtags = null,
        public ?array $mentions = null,
        public ?string $link_url = null,
        public ?string $first_comment = null,
        public ?array $social_account_ids = null,
    ) {}
}
