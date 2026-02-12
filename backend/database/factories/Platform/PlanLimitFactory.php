<?php

declare(strict_types=1);

namespace Database\Factories\Platform;

use App\Models\Platform\PlanDefinition;
use App\Models\Platform\PlanLimit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for PlanLimit model.
 *
 * @extends Factory<PlanLimit>
 */
final class PlanLimitFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PlanLimit>
     */
    protected $model = PlanLimit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_id' => PlanDefinition::factory(),
            'limit_key' => fake()->randomElement(PlanLimit::LIMIT_KEYS),
            'limit_value' => fake()->randomElement([
                fake()->numberBetween(1, 100),
                fake()->numberBetween(100, 1000),
                PlanLimit::UNLIMITED,
            ]),
        ];
    }

    /**
     * Set the plan.
     */
    public function forPlan(PlanDefinition $plan): static
    {
        return $this->state(fn (array $attributes): array => [
            'plan_id' => $plan->id,
        ]);
    }

    /**
     * Set a specific limit key and value.
     */
    public function limit(string $key, int $value): static
    {
        return $this->state(fn (array $attributes): array => [
            'limit_key' => $key,
            'limit_value' => $value,
        ]);
    }

    /**
     * Set the limit as unlimited.
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes): array => [
            'limit_value' => PlanLimit::UNLIMITED,
        ]);
    }

    /**
     * Create max_workspaces limit.
     */
    public function maxWorkspaces(int $value): static
    {
        return $this->limit('max_workspaces', $value);
    }

    /**
     * Create max_users limit.
     */
    public function maxUsers(int $value): static
    {
        return $this->limit('max_users', $value);
    }

    /**
     * Create max_social_accounts limit.
     */
    public function maxSocialAccounts(int $value): static
    {
        return $this->limit('max_social_accounts', $value);
    }

    /**
     * Create max_posts_per_month limit.
     */
    public function maxPostsPerMonth(int $value): static
    {
        return $this->limit('max_posts_per_month', $value);
    }

    /**
     * Create max_scheduled_posts limit.
     */
    public function maxScheduledPosts(int $value): static
    {
        return $this->limit('max_scheduled_posts', $value);
    }

    /**
     * Create max_storage_gb limit.
     * Note: Since the column is integer, store as MB or use fractional approach.
     */
    public function maxStorageGb(int $value): static
    {
        return $this->limit('max_storage_gb', $value);
    }

    /**
     * Create ai_requests_per_month limit.
     */
    public function aiRequestsPerMonth(int $value): static
    {
        return $this->limit('ai_requests_per_month', $value);
    }

    /**
     * Create analytics_history_days limit.
     */
    public function analyticsHistoryDays(int $value): static
    {
        return $this->limit('analytics_history_days', $value);
    }
}
