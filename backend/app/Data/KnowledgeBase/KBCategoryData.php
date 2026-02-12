<?php

declare(strict_types=1);

namespace App\Data\KnowledgeBase;

use App\Models\KnowledgeBase\KBCategory;
use Spatie\LaravelData\Data;

final class KBCategoryData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public ?string $icon,
        public ?string $color,
        public int $sort_order,
        public int $article_count,
        public ?string $parent_id,
    ) {}

    /**
     * Create KBCategoryData from a KBCategory model.
     */
    public static function fromModel(KBCategory $category): self
    {
        return new self(
            id: $category->id,
            name: $category->name,
            slug: $category->slug,
            description: $category->description,
            icon: $category->icon,
            color: $category->color,
            sort_order: $category->sort_order,
            article_count: $category->article_count,
            parent_id: $category->parent_id,
        );
    }
}
