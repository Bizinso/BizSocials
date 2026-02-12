<?php

declare(strict_types=1);

namespace App\Data\KnowledgeBase;

use Spatie\LaravelData\Data;

final class UpdateCategoryData extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $icon = null,
        public ?string $color = null,
        public ?string $slug = null,
    ) {}
}
