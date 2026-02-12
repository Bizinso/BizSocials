<?php

declare(strict_types=1);

namespace App\Data\Support;

use App\Models\Support\SupportCategory;
use Spatie\LaravelData\Data;

final class SupportCategoryData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $slug,
        public ?string $description,
        public string $color,
        public ?string $icon,
        public bool $is_active,
        public int $sort_order,
        public int $ticket_count,
    ) {}

    /**
     * Create SupportCategoryData from a SupportCategory model.
     */
    public static function fromModel(SupportCategory $category): self
    {
        return new self(
            id: $category->id,
            name: $category->name,
            slug: $category->slug,
            description: $category->description,
            color: $category->color,
            icon: $category->icon,
            is_active: $category->is_active,
            sort_order: $category->sort_order,
            ticket_count: $category->ticket_count,
        );
    }
}
