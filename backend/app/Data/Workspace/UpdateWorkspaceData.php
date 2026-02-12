<?php

declare(strict_types=1);

namespace App\Data\Workspace;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class UpdateWorkspaceData extends Data
{
    public function __construct(
        #[Nullable, Max(100)]
        public string|Optional|null $name = null,
        #[Nullable, Max(500)]
        public string|Optional|null $description = null,
        #[Nullable, Max(50)]
        public string|Optional|null $icon = null,
        #[Nullable, Max(20)]
        public string|Optional|null $color = null,
    ) {}
}
