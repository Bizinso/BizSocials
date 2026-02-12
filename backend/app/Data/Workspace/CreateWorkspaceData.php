<?php

declare(strict_types=1);

namespace App\Data\Workspace;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class CreateWorkspaceData extends Data
{
    public function __construct(
        #[Required, Max(100)]
        public string $name,
        #[Nullable, Max(500)]
        public ?string $description = null,
        #[Nullable, Max(50)]
        public ?string $icon = null,
        #[Nullable, Max(20)]
        public ?string $color = null,
    ) {}
}
