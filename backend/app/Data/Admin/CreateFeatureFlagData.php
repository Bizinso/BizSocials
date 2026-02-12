<?php

declare(strict_types=1);

namespace App\Data\Admin;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class CreateFeatureFlagData extends Data
{
    public function __construct(
        #[Required, Max(100)]
        public string $key,

        #[Required, Max(255)]
        public string $name,

        #[Nullable, Max(1000)]
        public ?string $description = null,

        public bool $is_enabled = false,

        #[Min(0), Max(100)]
        public int $rollout_percentage = 100,

        /** @var array<string>|null */
        public ?array $allowed_plans = null,

        /** @var array<string>|null */
        public ?array $allowed_tenants = null,

        public ?array $metadata = null,
    ) {}
}
