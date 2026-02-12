<?php

declare(strict_types=1);

namespace App\Data\Admin;

use App\Models\Platform\PlanDefinition;
use Spatie\LaravelData\Data;

final class PlanData extends Data
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public ?string $description,
        public bool $is_active,
        public bool $is_public,
        public int $sort_order,
        public string $price_inr_monthly,
        public string $price_inr_yearly,
        public string $price_usd_monthly,
        public string $price_usd_yearly,
        public int $trial_days,
        /** @var array<string, int> */
        public array $limits,
        /** @var array<string> */
        public array $features,
        public ?array $metadata,
        public ?string $razorpay_plan_id_inr,
        public ?string $razorpay_plan_id_usd,
        public int $subscription_count,
        public string $created_at,
        public string $updated_at,
    ) {}

    /**
     * Create PlanData from a PlanDefinition model.
     */
    public static function fromModel(PlanDefinition $plan): self
    {
        $plan->loadMissing(['limits']);

        // Convert limits to key-value array
        $limits = [];
        foreach ($plan->limits as $limit) {
            $limits[$limit->limit_key] = $limit->limit_value;
        }

        // Get active subscription count for this plan
        $subscriptionCount = $plan->subscriptions()
            ->where('status', 'active')
            ->count();

        return new self(
            id: $plan->id,
            code: $plan->code->value,
            name: $plan->name,
            description: $plan->description,
            is_active: $plan->is_active,
            is_public: $plan->is_public,
            sort_order: $plan->sort_order,
            price_inr_monthly: (string) $plan->price_inr_monthly,
            price_inr_yearly: (string) $plan->price_inr_yearly,
            price_usd_monthly: (string) $plan->price_usd_monthly,
            price_usd_yearly: (string) $plan->price_usd_yearly,
            trial_days: $plan->trial_days,
            limits: $limits,
            features: $plan->features ?? [],
            metadata: $plan->metadata,
            razorpay_plan_id_inr: $plan->razorpay_plan_id_inr,
            razorpay_plan_id_usd: $plan->razorpay_plan_id_usd,
            subscription_count: $subscriptionCount,
            created_at: $plan->created_at->toIso8601String(),
            updated_at: $plan->updated_at->toIso8601String(),
        );
    }
}
