<?php

declare(strict_types=1);

namespace App\Data\Admin;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class CreatePlanData extends Data
{
    public function __construct(
        #[Required, Max(50)]
        public string $code,

        #[Required, Max(100)]
        public string $name,

        #[Nullable, Max(500)]
        public ?string $description = null,

        public bool $is_active = true,

        public bool $is_public = true,

        #[Required, Min(0)]
        public float $price_inr_monthly = 0,

        #[Required, Min(0)]
        public float $price_inr_yearly = 0,

        #[Required, Min(0)]
        public float $price_usd_monthly = 0,

        #[Required, Min(0)]
        public float $price_usd_yearly = 0,

        #[Min(0)]
        public int $trial_days = 0,

        #[Min(0)]
        public int $sort_order = 0,

        /** @var array<string, int>|null */
        public ?array $limits = null,

        /** @var array<string>|null */
        public ?array $features = null,

        public ?array $metadata = null,

        #[Nullable, Max(100)]
        public ?string $razorpay_plan_id_inr = null,

        #[Nullable, Max(100)]
        public ?string $razorpay_plan_id_usd = null,
    ) {}
}
