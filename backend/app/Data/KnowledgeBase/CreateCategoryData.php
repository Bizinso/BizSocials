<?php

declare(strict_types=1);

namespace App\Data\KnowledgeBase;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class CreateCategoryData extends Data
{
    public function __construct(
        #[Required, Max(100)]
        public string $name,
        public ?string $description = null,
        public ?string $icon = null,
        public ?string $color = null,
        public ?string $parent_id = null,
        public ?string $slug = null,
    ) {}
}
