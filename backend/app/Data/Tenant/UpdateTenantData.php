<?php

declare(strict_types=1);

namespace App\Data\Tenant;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class UpdateTenantData extends Data
{
    public function __construct(
        #[Nullable, Max(100)]
        public string|Optional|null $name = null,
        #[Nullable, Url]
        public string|Optional|null $website = null,
        #[Nullable, Max(100)]
        public string|Optional|null $timezone = null,
        #[Nullable, Max(100)]
        public string|Optional|null $industry = null,
        #[Nullable, Max(50)]
        public string|Optional|null $company_size = null,
    ) {}
}
