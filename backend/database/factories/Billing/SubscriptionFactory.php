<?php

declare(strict_types=1);

namespace Database\Factories\Billing;

use App\Enums\Billing\BillingCycle;
use App\Enums\Billing\Currency;
use App\Enums\Billing\SubscriptionStatus;
use App\Models\Billing\Subscription;
use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for Subscription model.
 *
 * @extends Factory<Subscription>
 */
final class SubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Subscription>
     */
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $billingCycle = fake()->randomElement(BillingCycle::cases());
        $amount = $billingCycle === BillingCycle::YEARLY
            ? fake()->randomFloat(2, 5000, 50000)
            : fake()->randomFloat(2, 500, 5000);

        $periodStart = now()->subDays(fake()->numberBetween(1, 28));
        $periodEnd = $billingCycle === BillingCycle::YEARLY
            ? $periodStart->copy()->addYear()
            : $periodStart->copy()->addMonth();

        return [
            'tenant_id' => Tenant::factory(),
            'plan_id' => PlanDefinition::factory(),
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => $billingCycle,
            'currency' => Currency::INR,
            'amount' => $amount,
            'razorpay_subscription_id' => 'sub_' . fake()->regexify('[A-Za-z0-9]{14}'),
            'razorpay_customer_id' => 'cust_' . fake()->regexify('[A-Za-z0-9]{14}'),
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'trial_start' => null,
            'trial_end' => null,
            'cancelled_at' => null,
            'cancel_at_period_end' => false,
            'ended_at' => null,
            'metadata' => null,
        ];
    }

    /**
     * Set status to created.
     */
    public function created(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::CREATED,
            'current_period_start' => null,
            'current_period_end' => null,
        ]);
    }

    /**
     * Set status to authenticated.
     */
    public function authenticated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::AUTHENTICATED,
        ]);
    }

    /**
     * Set status to active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::ACTIVE,
        ]);
    }

    /**
     * Set status to pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::PENDING,
        ]);
    }

    /**
     * Set status to halted.
     */
    public function halted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::HALTED,
        ]);
    }

    /**
     * Set status to cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::CANCELLED,
            'cancelled_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'ended_at' => now()->subDays(fake()->numberBetween(0, 7)),
        ]);
    }

    /**
     * Set status to completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::COMPLETED,
            'ended_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Set status to expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::EXPIRED,
            'ended_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Set subscription to be on trial.
     */
    public function onTrial(int $days = 14): static
    {
        return $this->state(fn (array $attributes): array => [
            'trial_start' => now(),
            'trial_end' => now()->addDays($days),
        ]);
    }

    /**
     * Set billing cycle to monthly.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'billing_cycle' => BillingCycle::MONTHLY,
            'amount' => fake()->randomFloat(2, 500, 5000),
        ]);
    }

    /**
     * Set billing cycle to yearly.
     */
    public function yearly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'billing_cycle' => BillingCycle::YEARLY,
            'amount' => fake()->randomFloat(2, 5000, 50000),
        ]);
    }

    /**
     * Set currency to INR.
     */
    public function inr(): static
    {
        return $this->state(fn (array $attributes): array => [
            'currency' => Currency::INR,
        ]);
    }

    /**
     * Set currency to USD.
     */
    public function usd(): static
    {
        return $this->state(fn (array $attributes): array => [
            'currency' => Currency::USD,
        ]);
    }

    /**
     * Associate with a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes): array => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Associate with a specific plan.
     */
    public function forPlan(PlanDefinition $plan): static
    {
        return $this->state(fn (array $attributes): array => [
            'plan_id' => $plan->id,
        ]);
    }
}
