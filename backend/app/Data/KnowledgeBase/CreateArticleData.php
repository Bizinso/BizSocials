<?php

declare(strict_types=1);

namespace App\Data\KnowledgeBase;

use App\Enums\KnowledgeBase\KBArticleType;
use App\Enums\KnowledgeBase\KBContentFormat;
use App\Enums\KnowledgeBase\KBDifficultyLevel;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

final class CreateArticleData extends Data
{
    /**
     * @param array<string>|null $tag_ids
     */
    public function __construct(
        #[Required, Uuid]
        public string $category_id,
        #[Required, Max(200)]
        public string $title,
        #[Required]
        public string $content,
        public ?string $excerpt = null,
        public ?string $slug = null,
        public KBContentFormat $content_format = KBContentFormat::MARKDOWN,
        public KBArticleType $article_type = KBArticleType::HOW_TO,
        public KBDifficultyLevel $difficulty_level = KBDifficultyLevel::BEGINNER,
        public bool $is_featured = false,
        public ?string $featured_image = null,
        public ?string $meta_title = null,
        public ?string $meta_description = null,
        public ?array $tag_ids = null,
    ) {}
}
