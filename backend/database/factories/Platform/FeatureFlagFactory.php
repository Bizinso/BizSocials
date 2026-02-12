<?php

declare(strict_types=1);

namespace Database\Factories\Platform;

use App\Enums\Platform\PlanCode;
use App\Models\Platform\FeatureFlag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for FeatureFlag model.
 *
 * @extends Factory<FeatureFlag>
 */
final class FeatureFlagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<FeatureFlag>
     */
    protected $model = FeatureFlag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'feature.' . fake()->unique()->slug(2),
            'name' => ucwords(fake()->words(3, true)),
            'description' => fake()->optional(0.7)->sentence(),
            // 30% chance of being enabled
            'is_enabled' => fake()->boolean(30),
            'rollout_percentage' => fake()->numberBetween(0, 100),
            'allowed_plans' => null,
            'allowed_tenants' => null,
            'metadata' => null,
        ];
    }

    /**
     * Indicate the feature is enabled.
     */
    public function enabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_enabled' => true,
        ]);
    }

    /**
     * Indicate the feature is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_enabled' => false,
        ]);
    }

    /**
     * Set rollout to 100%.
     */
    public function fullRollout(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_enabled' => true,
            'rollout_percentage' => 100,
        ]);
    }

    /**
     * Set a specific rollout percentage.
     */
    public function rollout(int $percentage): static
    {
        return $this->state(fn (array $attributes): array => [
            'rollout_percentage' => min(100, max(0, $percentage)),
        ]);
    }

    /**
     * Restrict to specific plans.
     *
     * @param  array<PlanCode>  $plans
     */
    public function forPlans(array $plans): static
    {
        return $this->state(fn (array $attributes): array => [
            'allowed_plans' => array_map(fn (PlanCode $plan) => $plan->value, $plans),
        ]);
    }

    /**
     * Restrict to paid plans only.
     */
    public function paidPlansOnly(): static
    {
        return $this->forPlans([
            PlanCode::STARTER,
            PlanCode::PROFESSIONAL,
            PlanCode::BUSINESS,
            PlanCode::ENTERPRISE,
        ]);
    }

    /**
     * Restrict to enterprise plan only.
     */
    public function enterpriseOnly(): static
    {
        return $this->forPlans([PlanCode::ENTERPRISE]);
    }

    /**
     * Allow specific tenants.
     *
     * @param  array<string>  $tenantIds
     */
    public function forTenants(array $tenantIds): static
    {
        return $this->state(fn (array $attributes): array => [
            'allowed_tenants' => $tenantIds,
        ]);
    }

    /**
     * Set specific metadata.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes): array => [
            'metadata' => $metadata,
        ]);
    }
}
