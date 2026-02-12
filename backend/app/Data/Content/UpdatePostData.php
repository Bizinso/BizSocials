<?php

declare(strict_types=1);

namespace App\Data\Content;

use Spatie\LaravelData\Data;

final class UpdatePostData extends Data
{
    /**
     * @param array<string, mixed>|null $content_variations
     * @param array<string>|null $hashtags
     * @param array<string>|null $mentions
     */
    public function __construct(
        public ?string $content_text = null,
        public ?array $content_variations = null,
        public ?array $hashtags = null,
        public ?array $mentions = null,
        public ?string $link_url = null,
        public ?string $first_comment = null,
    ) {}
}
