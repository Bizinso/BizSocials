<?php

declare(strict_types=1);

namespace App\Data\Admin;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;

final class UpdatePlanData extends Data
{
    public function __construct(
        #[Nullable, Max(100)]
        public ?string $name = null,

        #[Nullable, Max(500)]
        public ?string $description = null,

        public ?bool $is_active = null,

        public ?bool $is_public = null,

        #[Nullable, Min(0)]
        public ?float $price_inr_monthly = null,

        #[Nullable, Min(0)]
        public ?float $price_inr_yearly = null,

        #[Nullable, Min(0)]
        public ?float $price_usd_monthly = null,

        #[Nullable, Min(0)]
        public ?float $price_usd_yearly = null,

        #[Nullable, Min(0)]
        public ?int $trial_days = null,

        #[Nullable, Min(0)]
        public ?int $sort_order = null,

        /** @var array<string>|null */
        public ?array $features = null,

        public ?array $metadata = null,

        #[Nullable, Max(100)]
        public ?string $razorpay_plan_id_inr = null,

        #[Nullable, Max(100)]
        public ?string $razorpay_plan_id_usd = null,
    ) {}
}
