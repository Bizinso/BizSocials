<?php

declare(strict_types=1);

namespace App\Data\KnowledgeBase;

use App\Enums\KnowledgeBase\KBArticleType;
use App\Enums\KnowledgeBase\KBDifficultyLevel;
use Spatie\LaravelData\Data;

final class UpdateArticleData extends Data
{
    /**
     * @param array<string>|null $tag_ids
     */
    public function __construct(
        public ?string $category_id = null,
        public ?string $title = null,
        public ?string $content = null,
        public ?string $excerpt = null,
        public ?string $slug = null,
        public ?KBArticleType $article_type = null,
        public ?KBDifficultyLevel $difficulty_level = null,
        public ?bool $is_featured = null,
        public ?string $featured_image = null,
        public ?string $meta_title = null,
        public ?string $meta_description = null,
        public ?array $tag_ids = null,
    ) {}
}
