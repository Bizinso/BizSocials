<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\Tenant\TenantStatus;
use App\Enums\Tenant\TenantType;
use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for Tenant model.
 *
 * @extends Factory<Tenant>
 */
final class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Tenant>
     */
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();
        $type = fake()->randomElement(TenantType::cases());

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'type' => $type,
            'status' => fake()->randomElement([
                TenantStatus::ACTIVE,
                TenantStatus::ACTIVE,
                TenantStatus::ACTIVE, // Weight toward active
                TenantStatus::PENDING,
            ]),
            'owner_user_id' => null,
            'plan_id' => null,
            'trial_ends_at' => fake()->boolean(30) ? now()->addDays(fake()->numberBetween(1, 14)) : null,
            'settings' => [
                'timezone' => fake()->timezone(),
                'language' => 'en',
                'notifications' => [
                    'email' => true,
                    'in_app' => true,
                    'digest' => 'daily',
                ],
                'branding' => [
                    'logo_url' => null,
                    'primary_color' => fake()->hexColor(),
                ],
                'security' => [
                    'require_mfa' => false,
                    'session_timeout_minutes' => 60,
                ],
            ],
            'onboarding_completed_at' => fake()->boolean(60) ? fake()->dateTimeBetween('-30 days', 'now') : null,
            'metadata' => null,
        ];
    }

    /**
     * Set tenant status to pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TenantStatus::PENDING,
            'onboarding_completed_at' => null,
        ]);
    }

    /**
     * Set tenant status to active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TenantStatus::ACTIVE,
        ]);
    }

    /**
     * Set tenant status to suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TenantStatus::SUSPENDED,
            'metadata' => [
                'suspension_reason' => 'Payment failed',
                'suspended_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Set tenant status to terminated.
     */
    public function terminated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TenantStatus::TERMINATED,
            'metadata' => [
                'terminated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Set tenant to be on trial.
     */
    public function onTrial(int $days = 14): static
    {
        return $this->state(fn (array $attributes): array => [
            'trial_ends_at' => now()->addDays($days),
        ]);
    }

    /**
     * Set tenant to have expired trial.
     */
    public function trialExpired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'trial_ends_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Set tenant type to B2B Enterprise.
     */
    public function b2bEnterprise(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => TenantType::B2B_ENTERPRISE,
        ]);
    }

    /**
     * Set tenant type to B2B SMB.
     */
    public function b2bSmb(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => TenantType::B2B_SMB,
        ]);
    }

    /**
     * Set tenant type to B2C Brand.
     */
    public function b2cBrand(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => TenantType::B2C_BRAND,
        ]);
    }

    /**
     * Set tenant type to Individual.
     */
    public function individual(): static
    {
        $name = fake()->name();

        return $this->state(fn (array $attributes): array => [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'type' => TenantType::INDIVIDUAL,
        ]);
    }

    /**
     * Set tenant type to Influencer.
     */
    public function influencer(): static
    {
        $name = fake()->name();

        return $this->state(fn (array $attributes): array => [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'type' => TenantType::INFLUENCER,
        ]);
    }

    /**
     * Set tenant type to Non-Profit.
     */
    public function nonProfit(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => TenantType::NON_PROFIT,
            'name' => fake()->company() . ' Foundation',
        ]);
    }

    /**
     * Associate with a specific plan.
     */
    public function withPlan(PlanDefinition $plan): static
    {
        return $this->state(fn (array $attributes): array => [
            'plan_id' => $plan->id,
        ]);
    }

    /**
     * Mark as having completed onboarding.
     */
    public function onboardingCompleted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'onboarding_completed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Mark as not having completed onboarding.
     */
    public function onboardingNotCompleted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'onboarding_completed_at' => null,
        ]);
    }

    /**
     * Set specific settings.
     *
     * @param  array<string, mixed>  $settings
     */
    public function withSettings(array $settings): static
    {
        return $this->state(fn (array $attributes): array => [
            'settings' => array_merge($attributes['settings'] ?? [], $settings),
        ]);
    }
}
