<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantUsage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * Factory for TenantUsage model.
 *
 * @extends Factory<TenantUsage>
 */
final class TenantUsageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TenantUsage>
     */
    protected $model = TenantUsage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        return [
            'tenant_id' => Tenant::factory(),
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'metric_key' => fake()->randomElement(TenantUsage::METRIC_KEYS),
            'metric_value' => fake()->numberBetween(0, 1000),
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
     * Set to current billing period.
     */
    public function forCurrentPeriod(): static
    {
        return $this->state(fn (array $attributes): array => [
            'period_start' => Carbon::now()->startOfMonth()->toDateString(),
            'period_end' => Carbon::now()->endOfMonth()->toDateString(),
        ]);
    }

    /**
     * Set to previous billing period.
     */
    public function forPreviousPeriod(): static
    {
        return $this->state(fn (array $attributes): array => [
            'period_start' => Carbon::now()->subMonth()->startOfMonth()->toDateString(),
            'period_end' => Carbon::now()->subMonth()->endOfMonth()->toDateString(),
        ]);
    }

    /**
     * Set to a specific period.
     */
    public function forPeriod(Carbon $start, Carbon $end): static
    {
        return $this->state(fn (array $attributes): array => [
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
        ]);
    }

    /**
     * Set a specific metric key.
     */
    public function forMetric(string $metricKey): static
    {
        return $this->state(fn (array $attributes): array => [
            'metric_key' => $metricKey,
        ]);
    }

    /**
     * Set high usage values.
     */
    public function highUsage(): static
    {
        return $this->state(function (array $attributes): array {
            $metricKey = $attributes['metric_key'] ?? 'posts_published';

            $highValues = [
                'workspaces_count' => fake()->numberBetween(50, 100),
                'users_count' => fake()->numberBetween(100, 500),
                'social_accounts_count' => fake()->numberBetween(50, 200),
                'posts_published' => fake()->numberBetween(5000, 10000),
                'posts_scheduled' => fake()->numberBetween(1000, 5000),
                'storage_bytes_used' => fake()->numberBetween(50000000000, 100000000000), // 50-100 GB
                'api_calls' => fake()->numberBetween(100000, 500000),
                'ai_requests' => fake()->numberBetween(5000, 20000),
            ];

            return [
                'metric_value' => $highValues[$metricKey] ?? fake()->numberBetween(5000, 10000),
            ];
        });
    }

    /**
     * Set low usage values.
     */
    public function lowUsage(): static
    {
        return $this->state(function (array $attributes): array {
            $metricKey = $attributes['metric_key'] ?? 'posts_published';

            $lowValues = [
                'workspaces_count' => fake()->numberBetween(1, 3),
                'users_count' => fake()->numberBetween(1, 5),
                'social_accounts_count' => fake()->numberBetween(1, 5),
                'posts_published' => fake()->numberBetween(0, 50),
                'posts_scheduled' => fake()->numberBetween(0, 20),
                'storage_bytes_used' => fake()->numberBetween(1000000, 100000000), // 1-100 MB
                'api_calls' => fake()->numberBetween(0, 1000),
                'ai_requests' => fake()->numberBetween(0, 100),
            ];

            return [
                'metric_value' => $lowValues[$metricKey] ?? fake()->numberBetween(0, 50),
            ];
        });
    }

    /**
     * Set a specific value.
     */
    public function withValue(int $value): static
    {
        return $this->state(fn (array $attributes): array => [
            'metric_value' => $value,
        ]);
    }

    /**
     * Create usage for workspaces count.
     */
    public function workspacesCount(int $count = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'metric_key' => 'workspaces_count',
            'metric_value' => $count ?? fake()->numberBetween(1, 20),
        ]);
    }

    /**
     * Create usage for users count.
     */
    public function usersCount(int $count = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'metric_key' => 'users_count',
            'metric_value' => $count ?? fake()->numberBetween(1, 50),
        ]);
    }

    /**
     * Create usage for social accounts count.
     */
    public function socialAccountsCount(int $count = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'metric_key' => 'social_accounts_count',
            'metric_value' => $count ?? fake()->numberBetween(1, 30),
        ]);
    }

    /**
     * Create usage for posts published.
     */
    public function postsPublished(int $count = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'metric_key' => 'posts_published',
            'metric_value' => $count ?? fake()->numberBetween(0, 500),
        ]);
    }
}
