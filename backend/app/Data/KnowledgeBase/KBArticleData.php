<?php

declare(strict_types=1);

namespace App\Data\KnowledgeBase;

use App\Models\KnowledgeBase\KBArticle;
use Spatie\LaravelData\Data;

final class KBArticleData extends Data
{
    /**
     * @param array<array{id: string, name: string, slug: string}> $tags
     */
    public function __construct(
        public string $id,
        public string $category_id,
        public string $category_name,
        public string $title,
        public string $slug,
        public ?string $excerpt,
        public string $content,
        public string $content_format,
        public ?string $featured_image,
        public string $article_type,
        public string $difficulty_level,
        public string $status,
        public bool $is_featured,
        public int $view_count,
        public int $helpful_count,
        public int $not_helpful_count,
        public float $helpfulness_score,
        public ?string $meta_title,
        public ?string $meta_description,
        public ?string $published_at,
        public string $created_at,
        public string $updated_at,
        public array $tags,
    ) {}

    /**
     * Create KBArticleData from a KBArticle model.
     */
    public static function fromModel(KBArticle $article): self
    {
        $article->loadMissing(['category', 'tags']);

        $totalVotes = $article->helpful_count + $article->not_helpful_count;
        $helpfulnessScore = $totalVotes > 0
            ? round(($article->helpful_count / $totalVotes) * 100, 1)
            : 0.0;

        return new self(
            id: $article->id,
            category_id: $article->category_id,
            category_name: $article->category?->name ?? '',
            title: $article->title,
            slug: $article->slug,
            excerpt: $article->excerpt,
            content: $article->content,
            content_format: $article->content_format->value,
            featured_image: $article->featured_image,
            article_type: $article->article_type->value,
            difficulty_level: $article->difficulty_level->value,
            status: $article->status->value,
            is_featured: $article->is_featured,
            view_count: $article->view_count,
            helpful_count: $article->helpful_count,
            not_helpful_count: $article->not_helpful_count,
            helpfulness_score: $helpfulnessScore,
            meta_title: $article->meta_title,
            meta_description: $article->meta_description,
            published_at: $article->published_at?->toIso8601String(),
            created_at: $article->created_at->toIso8601String(),
            updated_at: $article->updated_at->toIso8601String(),
            tags: $article->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->toArray(),
        );
    }
}
