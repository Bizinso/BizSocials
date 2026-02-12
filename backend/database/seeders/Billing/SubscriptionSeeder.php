<?php

declare(strict_types=1);

namespace Database\Seeders\Billing;

use App\Enums\Billing\BillingCycle;
use App\Enums\Billing\Currency;
use App\Enums\Billing\SubscriptionStatus;
use App\Enums\Platform\PlanCode;
use App\Enums\Tenant\TenantStatus;
use App\Models\Billing\Subscription;
use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Seeder;

/**
 * Seeder for Subscription model.
 *
 * Creates subscriptions for active tenants with their respective plans.
 */
final class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get active tenants that have plans
        $tenants = Tenant::whereIn('status', [TenantStatus::ACTIVE, TenantStatus::SUSPENDED])
            ->whereNotNull('plan_id')
            ->get();

        foreach ($tenants as $tenant) {
            $plan = PlanDefinition::find($tenant->plan_id);

            if ($plan === null) {
                continue;
            }

            // Determine billing cycle and amount based on plan
            $billingCycle = fake()->randomElement([BillingCycle::MONTHLY, BillingCycle::YEARLY]);
            $amount = $this->getPlanAmount($plan->code, $billingCycle);

            // Determine status based on tenant status
            $status = $tenant->status === TenantStatus::SUSPENDED
                ? SubscriptionStatus::HALTED
                : SubscriptionStatus::ACTIVE;

            // Set period dates
            $periodStart = now()->subDays(fake()->numberBetween(1, 28));
            $periodEnd = $billingCycle === BillingCycle::YEARLY
                ? $periodStart->copy()->addYear()
                : $periodStart->copy()->addMonth();

            // Check if tenant is on trial
            $trialStart = null;
            $trialEnd = null;
            if ($tenant->trial_ends_at !== null && $tenant->trial_ends_at->isFuture()) {
                $trialStart = $periodStart;
                $trialEnd = $tenant->trial_ends_at;
            }

            Subscription::firstOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'plan_id' => $plan->id,
                    'status' => $status,
                    'billing_cycle' => $billingCycle,
                    'currency' => Currency::INR,
                    'amount' => $amount,
                    'razorpay_subscription_id' => 'sub_' . fake()->regexify('[A-Za-z0-9]{14}'),
                    'razorpay_customer_id' => 'cust_' . fake()->regexify('[A-Za-z0-9]{14}'),
                    'current_period_start' => $periodStart,
                    'current_period_end' => $periodEnd,
                    'trial_start' => $trialStart,
                    'trial_end' => $trialEnd,
                    'cancelled_at' => null,
                    'cancel_at_period_end' => false,
                    'ended_at' => null,
                    'metadata' => null,
                ]
            );
        }

        $this->command->info('Subscriptions seeded successfully.');
    }

    /**
     * Get plan amount based on plan code and billing cycle.
     */
    private function getPlanAmount(PlanCode $planCode, BillingCycle $billingCycle): float
    {
        $monthlyPrices = [
            PlanCode::FREE->value => 0,
            PlanCode::STARTER->value => 999,
            PlanCode::PROFESSIONAL->value => 2499,
            PlanCode::BUSINESS->value => 4999,
            PlanCode::ENTERPRISE->value => 9999,
        ];

        $monthlyPrice = $monthlyPrices[$planCode->value] ?? 999;

        if ($billingCycle === BillingCycle::YEARLY) {
            // 10 months price for yearly (2 months free)
            return $monthlyPrice * 10;
        }

        return $monthlyPrice;
    }
}
