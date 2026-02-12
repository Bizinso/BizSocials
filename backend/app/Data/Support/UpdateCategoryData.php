<?php

declare(strict_types=1);

namespace App\Data\Support;

use Spatie\LaravelData\Data;

final class UpdateCategoryData extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $color = null,
        public ?string $icon = null,
        public ?string $parent_id = null,
        public ?int $sort_order = null,
        public ?bool $is_active = null,
    ) {}
}
