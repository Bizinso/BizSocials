<?php

declare(strict_types=1);

namespace App\Data\Admin;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;

final class UpdateTenantAdminData extends Data
{
    public function __construct(
        #[Nullable, Max(255)]
        public ?string $name = null,

        #[Nullable]
        public ?string $plan_id = null,

        #[Nullable]
        public ?array $settings = null,

        #[Nullable]
        public ?array $metadata = null,
    ) {}
}
