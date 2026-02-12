<?php

declare(strict_types=1);

namespace App\Data\KnowledgeBase;

use Spatie\LaravelData\Data;

final class KBSearchResultData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public string $slug,
        public ?string $excerpt,
        public string $category_name,
        public string $article_type,
        public float $relevance_score,
    ) {}
}
