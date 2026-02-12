<?php

declare(strict_types=1);

namespace App\Data\KnowledgeBase;

use App\Models\KnowledgeBase\KBArticle;
use Spatie\LaravelData\Data;

final class KBArticleSummaryData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public string $slug,
        public ?string $excerpt,
        public string $category_id,
        public string $category_name,
        public string $article_type,
        public string $difficulty_level,
        public int $view_count,
        public bool $is_featured,
        public ?string $published_at,
    ) {}

    /**
     * Create KBArticleSummaryData from a KBArticle model.
     */
    public static function fromModel(KBArticle $article): self
    {
        $article->loadMissing('category');

        return new self(
            id: $article->id,
            title: $article->title,
            slug: $article->slug,
            excerpt: $article->excerpt,
            category_id: $article->category_id,
            category_name: $article->category?->name ?? '',
            article_type: $article->article_type->value,
            difficulty_level: $article->difficulty_level->value,
            view_count: $article->view_count,
            is_featured: $article->is_featured,
            published_at: $article->published_at?->toIso8601String(),
        );
    }
}
