<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Models\Billing\Subscription;
use Spatie\LaravelData\Data;

final class SubscriptionData extends Data
{
    public function __construct(
        public string $id,
        public string $tenant_id,
        public string $plan_id,
        public string $plan_name,
        public string $status,
        public string $billing_cycle,
        public string $currency,
        public string $amount,
        public ?string $current_period_start,
        public ?string $current_period_end,
        public ?string $trial_end,
        public bool $is_on_trial,
        public int $trial_days_remaining,
        public int $days_until_renewal,
        public bool $cancel_at_period_end,
        public ?string $cancelled_at,
        public string $created_at,
    ) {}

    /**
     * Create SubscriptionData from a Subscription model.
     */
    public static function fromModel(Subscription $subscription): self
    {
        $subscription->loadMissing('plan');

        return new self(
            id: $subscription->id,
            tenant_id: $subscription->tenant_id,
            plan_id: $subscription->plan_id,
            plan_name: $subscription->plan?->name ?? 'Unknown',
            status: $subscription->status->value,
            billing_cycle: $subscription->billing_cycle->value,
            currency: $subscription->currency->value,
            amount: number_format((float) $subscription->amount, 2, '.', ''),
            current_period_start: $subscription->current_period_start?->toIso8601String(),
            current_period_end: $subscription->current_period_end?->toIso8601String(),
            trial_end: $subscription->trial_end?->toIso8601String(),
            is_on_trial: $subscription->isOnTrial(),
            trial_days_remaining: $subscription->trialDaysRemaining(),
            days_until_renewal: $subscription->daysUntilRenewal(),
            cancel_at_period_end: $subscription->cancel_at_period_end,
            cancelled_at: $subscription->cancelled_at?->toIso8601String(),
            created_at: $subscription->created_at->toIso8601String(),
        );
    }
}
