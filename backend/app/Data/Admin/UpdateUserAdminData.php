<?php

declare(strict_types=1);

namespace App\Data\Admin;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;

final class UpdateUserAdminData extends Data
{
    public function __construct(
        #[Nullable, Max(255)]
        public ?string $name = null,

        #[Nullable]
        public ?string $role_in_tenant = null,

        #[Nullable]
        public ?string $timezone = null,

        #[Nullable, Max(10)]
        public ?string $language = null,

        #[Nullable]
        public ?bool $mfa_enabled = null,

        #[Nullable]
        public ?array $settings = null,
    ) {}
}
