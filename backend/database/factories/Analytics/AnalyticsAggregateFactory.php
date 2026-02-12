<?php

declare(strict_types=1);

namespace Database\Factories\Analytics;

use App\Enums\Analytics\PeriodType;
use App\Models\Analytics\AnalyticsAggregate;
use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for AnalyticsAggregate model.
 *
 * @extends Factory<AnalyticsAggregate>
 */
final class AnalyticsAggregateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AnalyticsAggregate>
     */
    protected $model = AnalyticsAggregate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $impressions = fake()->numberBetween(1000, 100000);
        $reach = (int) ($impressions * fake()->randomFloat(2, 0.4, 0.9));
        $engagements = (int) ($reach * fake()->randomFloat(4, 0.02, 0.15));
        $likes = (int) ($engagements * 0.5);
        $comments = (int) ($engagements * 0.2);
        $shares = (int) ($engagements * 0.15);
        $saves = (int) ($engagements * 0.15);
        $followersStart = fake()->numberBetween(1000, 50000);
        $followersChange = fake()->numberBetween(-100, 500);

        return [
            'workspace_id' => Workspace::factory(),
            'social_account_id' => null,
            'date' => fake()->dateTimeBetween('-90 days', 'now'),
            'period_type' => PeriodType::DAILY,
            'impressions' => $impressions,
            'reach' => $reach,
            'engagements' => $engagements,
            'likes' => $likes,
            'comments' => $comments,
            'shares' => $shares,
            'saves' => $saves,
            'clicks' => fake()->numberBetween(50, 2000),
            'video_views' => fake()->numberBetween(0, 5000),
            'posts_count' => fake()->numberBetween(1, 10),
            'engagement_rate' => $reach > 0 ? round(($engagements / $reach) * 100, 4) : 0,
            'followers_start' => $followersStart,
            'followers_end' => $followersStart + $followersChange,
            'followers_change' => $followersChange,
        ];
    }

    /**
     * Associate with a specific workspace.
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes): array => [
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Associate with a specific social account.
     */
    public function forSocialAccount(SocialAccount $socialAccount): static
    {
        return $this->state(fn (array $attributes): array => [
            'workspace_id' => $socialAccount->workspace_id,
            'social_account_id' => $socialAccount->id,
        ]);
    }

    /**
     * Set as workspace-level totals (no social account).
     */
    public function workspaceTotals(): static
    {
        return $this->state(fn (array $attributes): array => [
            'social_account_id' => null,
        ]);
    }

    /**
     * Set period type to daily.
     */
    public function daily(): static
    {
        return $this->state(fn (array $attributes): array => [
            'period_type' => PeriodType::DAILY,
        ]);
    }

    /**
     * Set period type to weekly.
     */
    public function weekly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'period_type' => PeriodType::WEEKLY,
        ]);
    }

    /**
     * Set period type to monthly.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'period_type' => PeriodType::MONTHLY,
        ]);
    }

    /**
     * Set a specific date.
     */
    public function forDate(\DateTimeInterface $date): static
    {
        return $this->state(fn (array $attributes): array => [
            'date' => $date,
        ]);
    }

    /**
     * Create aggregates for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes): array => [
            'date' => now()->toDateString(),
        ]);
    }

    /**
     * Create aggregates for yesterday.
     */
    public function yesterday(): static
    {
        return $this->state(fn (array $attributes): array => [
            'date' => now()->subDay()->toDateString(),
        ]);
    }

    /**
     * Create aggregates with high engagement.
     */
    public function highEngagement(): static
    {
        return $this->state(function (array $attributes): array {
            $impressions = fake()->numberBetween(50000, 500000);
            $reach = (int) ($impressions * 0.85);
            $engagements = (int) ($reach * 0.12);

            return [
                'impressions' => $impressions,
                'reach' => $reach,
                'engagements' => $engagements,
                'engagement_rate' => round(($engagements / $reach) * 100, 4),
            ];
        });
    }

    /**
     * Create aggregates with low engagement.
     */
    public function lowEngagement(): static
    {
        return $this->state(function (array $attributes): array {
            $impressions = fake()->numberBetween(100, 1000);
            $reach = (int) ($impressions * 0.5);
            $engagements = (int) ($reach * 0.01);

            return [
                'impressions' => $impressions,
                'reach' => $reach,
                'engagements' => $engagements,
                'engagement_rate' => round(($engagements / max($reach, 1)) * 100, 4),
            ];
        });
    }

    /**
     * Create aggregates with follower growth.
     */
    public function withFollowerGrowth(int $growth = 100): static
    {
        return $this->state(fn (array $attributes): array => [
            'followers_change' => abs($growth),
            'followers_end' => ($attributes['followers_start'] ?? 1000) + abs($growth),
        ]);
    }

    /**
     * Create aggregates with follower decline.
     */
    public function withFollowerDecline(int $decline = 50): static
    {
        return $this->state(fn (array $attributes): array => [
            'followers_change' => -abs($decline),
            'followers_end' => max(0, ($attributes['followers_start'] ?? 1000) - abs($decline)),
        ]);
    }

    /**
     * Create aggregates with no posts.
     */
    public function noPosts(): static
    {
        return $this->state(fn (array $attributes): array => [
            'posts_count' => 0,
            'impressions' => 0,
            'reach' => 0,
            'engagements' => 0,
            'likes' => 0,
            'comments' => 0,
            'shares' => 0,
            'saves' => 0,
            'clicks' => 0,
            'video_views' => 0,
            'engagement_rate' => 0,
        ]);
    }

    /**
     * Create aggregates with specific metrics.
     *
     * @param  array<string, int>  $metrics
     */
    public function withMetrics(array $metrics): static
    {
        return $this->state(fn (array $attributes): array => $metrics);
    }
}
