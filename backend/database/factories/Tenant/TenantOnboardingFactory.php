<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantOnboarding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for TenantOnboarding model.
 *
 * @extends Factory<TenantOnboarding>
 */
final class TenantOnboardingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TenantOnboarding>
     */
    protected $model = TenantOnboarding::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $completedCount = fake()->numberBetween(1, 5);
        $stepsCompleted = array_slice(TenantOnboarding::STEPS, 0, $completedCount);
        $currentStep = TenantOnboarding::STEPS[$completedCount] ?? TenantOnboarding::STEPS[0];

        return [
            'tenant_id' => Tenant::factory(),
            'current_step' => $currentStep,
            'steps_completed' => $stepsCompleted,
            'started_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'completed_at' => null,
            'abandoned_at' => null,
            'metadata' => [
                'referral_source' => fake()->randomElement(['google', 'referral', 'social', 'direct']),
                'signup_device' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            ],
        ];
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
     * Set to just started state (only account_created completed).
     */
    public function justStarted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_step' => 'email_verified',
            'steps_completed' => ['account_created'],
            'started_at' => now(),
            'completed_at' => null,
            'abandoned_at' => null,
        ]);
    }

    /**
     * Set to in progress state (partially completed).
     */
    public function inProgress(int $completedSteps = 5): static
    {
        $completedSteps = min($completedSteps, count(TenantOnboarding::STEPS) - 1);
        $stepsCompleted = array_slice(TenantOnboarding::STEPS, 0, $completedSteps);
        $currentStep = TenantOnboarding::STEPS[$completedSteps] ?? TenantOnboarding::STEPS[0];

        return $this->state(fn (array $attributes): array => [
            'current_step' => $currentStep,
            'steps_completed' => $stepsCompleted,
            'completed_at' => null,
            'abandoned_at' => null,
        ]);
    }

    /**
     * Set to completed state (all steps done).
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_step' => 'tour_completed',
            'steps_completed' => TenantOnboarding::STEPS,
            'completed_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'abandoned_at' => null,
        ]);
    }

    /**
     * Set to abandoned state.
     */
    public function abandoned(int $completedSteps = 3): static
    {
        $stepsCompleted = array_slice(TenantOnboarding::STEPS, 0, $completedSteps);
        $currentStep = TenantOnboarding::STEPS[$completedSteps] ?? TenantOnboarding::STEPS[0];

        return $this->state(fn (array $attributes): array => [
            'current_step' => $currentStep,
            'steps_completed' => $stepsCompleted,
            'completed_at' => null,
            'abandoned_at' => fake()->dateTimeBetween('-14 days', '-1 day'),
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
            'metadata' => array_merge($attributes['metadata'] ?? [], $metadata),
        ]);
    }
}
