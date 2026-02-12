<?php

declare(strict_types=1);

namespace App\Data\Support;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class CreateCategoryData extends Data
{
    public function __construct(
        #[Required, Max(100)]
        public string $name,
        public ?string $description = null,
        public ?string $color = null,
        public ?string $icon = null,
        public ?string $parent_id = null,
        public int $sort_order = 0,
        public bool $is_active = true,
    ) {}
}
