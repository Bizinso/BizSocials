<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\Analytics\PeriodType;
use App\Models\Analytics\AnalyticsAggregate;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * AnalyticsService
 *
 * Main analytics service for dashboard metrics, trends, and aggregations.
 * Handles:
 * - Dashboard metrics retrieval and comparison
 * - Daily metrics aggregation
 * - Engagement and follower growth trends
 * - Platform-specific metrics breakdown
 */
final class AnalyticsService extends BaseService
{
    /**
     * Get dashboard metrics for a workspace.
     *
     * Returns aggregated metrics for the specified period including:
     * - Total impressions, reach, engagements
     * - Engagement breakdown (likes, comments, shares, saves)
     * - Follower statistics
     * - Post counts and performance averages
     *
     * @param string $workspaceId The workspace UUID
     * @param string $period Period string (e.g., '7d', '30d', '90d', '1y')
     * @return array<string, mixed> Dashboard metrics data
     */
    public function getDashboardMetrics(string $workspaceId, string $period = '30d'): array
    {
        $dateRange = $this->parsePeriod($period);
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        // Get aggregated metrics for the period
        $aggregates = AnalyticsAggregate::forWorkspace($workspaceId)
            ->workspaceTotals()
            ->daily()
            ->inDateRange($start, $end)
            ->get();

        // Calculate totals
        $totals = [
            'impressions' => $aggregates->sum('impressions'),
            'reach' => $aggregates->sum('reach'),
            'engagements' => $aggregates->sum('engagements'),
            'likes' => $aggregates->sum('likes'),
            'comments' => $aggregates->sum('comments'),
            'shares' => $aggregates->sum('shares'),
            'saves' => $aggregates->sum('saves'),
            'clicks' => $aggregates->sum('clicks'),
            'video_views' => $aggregates->sum('video_views'),
            'posts_count' => $aggregates->sum('posts_count'),
        ];

        // Calculate engagement rate
        $totals['engagement_rate'] = $totals['reach'] > 0
            ? round(($totals['engagements'] / $totals['reach']) * 100, 2)
            : 0.0;

        // Calculate averages
        $dayCount = max($aggregates->count(), 1);
        $totals['avg_daily_engagements'] = round($totals['engagements'] / $dayCount, 2);
        $totals['avg_engagements_per_post'] = $totals['posts_count'] > 0
            ? round($totals['engagements'] / $totals['posts_count'], 2)
            : 0.0;

        // Get follower data from the most recent aggregate
        $latestAggregate = $aggregates->sortByDesc('date')->first();
        $firstAggregate = $aggregates->sortBy('date')->first();

        $totals['followers_current'] = $latestAggregate?->followers_end ?? 0;
        $totals['followers_change'] = $latestAggregate && $firstAggregate
            ? $latestAggregate->followers_end - $firstAggregate->followers_start
            : 0;
        $totals['followers_growth_rate'] = $firstAggregate && $firstAggregate->followers_start > 0
            ? round(($totals['followers_change'] / $firstAggregate->followers_start) * 100, 2)
            : 0.0;

        // Get comparison with previous period
        $comparison = $this->getMetricsComparison($workspaceId, $start, $end);

        $this->log('Dashboard metrics retrieved', [
            'workspace_id' => $workspaceId,
            'period' => $period,
            'days' => $dayCount,
        ]);

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'days' => $start->diffInDays($end) + 1,
            ],
            'metrics' => $totals,
            'comparison' => $comparison,
        ];
    }

    /**
     * Get metrics comparison with the previous period.
     *
     * Compares current period metrics with the same duration in the previous period.
     * Returns percentage changes for key metrics.
     *
     * @param string $workspaceId The workspace UUID
     * @param Carbon $currentStart Current period start date
     * @param Carbon $currentEnd Current period end date
     * @return array<string, mixed> Comparison data with percentage changes
     */
    public function getMetricsComparison(string $workspaceId, Carbon $currentStart, Carbon $currentEnd): array
    {
        $periodLength = $currentStart->diffInDays($currentEnd) + 1;
        $previousStart = $currentStart->copy()->subDays($periodLength);
        $previousEnd = $currentStart->copy()->subDay();

        // Get current period aggregates
        $currentAggregates = AnalyticsAggregate::forWorkspace($workspaceId)
            ->workspaceTotals()
            ->daily()
            ->inDateRange($currentStart, $currentEnd)
            ->get();

        // Get previous period aggregates
        $previousAggregates = AnalyticsAggregate::forWorkspace($workspaceId)
            ->workspaceTotals()
            ->daily()
            ->inDateRange($previousStart, $previousEnd)
            ->get();

        $currentTotals = [
            'impressions' => $currentAggregates->sum('impressions'),
            'reach' => $currentAggregates->sum('reach'),
            'engagements' => $currentAggregates->sum('engagements'),
            'posts_count' => $currentAggregates->sum('posts_count'),
        ];

        $previousTotals = [
            'impressions' => $previousAggregates->sum('impressions'),
            'reach' => $previousAggregates->sum('reach'),
            'engagements' => $previousAggregates->sum('engagements'),
            'posts_count' => $previousAggregates->sum('posts_count'),
        ];

        $comparison = [];
        foreach ($currentTotals as $metric => $currentValue) {
            $previousValue = $previousTotals[$metric];
            $change = $currentValue - $previousValue;
            $percentChange = $previousValue > 0
                ? round(($change / $previousValue) * 100, 2)
                : ($currentValue > 0 ? 100.0 : 0.0);

            $comparison[$metric] = [
                'current' => $currentValue,
                'previous' => $previousValue,
                'change' => $change,
                'percent_change' => $percentChange,
                'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
            ];
        }

        // Calculate follower comparison
        $currentLatest = $currentAggregates->sortByDesc('date')->first();
        $currentFirst = $currentAggregates->sortBy('date')->first();
        $previousLatest = $previousAggregates->sortByDesc('date')->first();
        $previousFirst = $previousAggregates->sortBy('date')->first();

        $currentFollowerChange = $currentLatest && $currentFirst
            ? $currentLatest->followers_end - $currentFirst->followers_start
            : 0;
        $previousFollowerChange = $previousLatest && $previousFirst
            ? $previousLatest->followers_end - $previousFirst->followers_start
            : 0;

        $followerDiff = $currentFollowerChange - $previousFollowerChange;
        $comparison['followers_change'] = [
            'current' => $currentFollowerChange,
            'previous' => $previousFollowerChange,
            'change' => $followerDiff,
            'percent_change' => $previousFollowerChange != 0
                ? round(($followerDiff / abs($previousFollowerChange)) * 100, 2)
                : ($currentFollowerChange > 0 ? 100.0 : 0.0),
            'trend' => $currentFollowerChange > $previousFollowerChange ? 'up'
                : ($currentFollowerChange < $previousFollowerChange ? 'down' : 'stable'),
        ];

        return [
            'previous_period' => [
                'start' => $previousStart->toDateString(),
                'end' => $previousEnd->toDateString(),
            ],
            'metrics' => $comparison,
        ];
    }

    /**
     * Aggregate daily metrics for a workspace.
     *
     * Collects and aggregates all metrics from posts and social accounts
     * for a specific date. Creates or updates the AnalyticsAggregate record.
     *
     * @param string $workspaceId The workspace UUID
     * @param Carbon $date The date to aggregate metrics for
     * @return AnalyticsAggregate The created or updated aggregate
     */
    public function aggregateDailyMetrics(string $workspaceId, Carbon $date): AnalyticsAggregate
    {
        return $this->transaction(function () use ($workspaceId, $date): AnalyticsAggregate {
            $startOfDay = $date->copy()->startOfDay();
            $endOfDay = $date->copy()->endOfDay();

            // Get posts published on this date
            $publishedPosts = Post::forWorkspace($workspaceId)
                ->published()
                ->whereBetween('published_at', [$startOfDay, $endOfDay])
                ->with('targets')
                ->get();

            // Aggregate metrics from post targets
            $metrics = [
                'impressions' => 0,
                'reach' => 0,
                'engagements' => 0,
                'likes' => 0,
                'comments' => 0,
                'shares' => 0,
                'saves' => 0,
                'clicks' => 0,
                'video_views' => 0,
                'posts_count' => $publishedPosts->count(),
            ];

            foreach ($publishedPosts as $post) {
                foreach ($post->targets as $target) {
                    $targetMetrics = $target->metrics ?? [];
                    $metrics['impressions'] += $targetMetrics['impressions'] ?? 0;
                    $metrics['reach'] += $targetMetrics['reach'] ?? 0;
                    $metrics['likes'] += $targetMetrics['likes'] ?? 0;
                    $metrics['comments'] += $targetMetrics['comments'] ?? 0;
                    $metrics['shares'] += $targetMetrics['shares'] ?? 0;
                    $metrics['saves'] += $targetMetrics['saves'] ?? 0;
                    $metrics['clicks'] += $targetMetrics['clicks'] ?? 0;
                    $metrics['video_views'] += $targetMetrics['video_views'] ?? 0;
                }
            }

            // Calculate total engagements
            $metrics['engagements'] = $metrics['likes'] + $metrics['comments']
                + $metrics['shares'] + $metrics['saves'];

            // Calculate engagement rate
            $metrics['engagement_rate'] = $metrics['reach'] > 0
                ? round(($metrics['engagements'] / $metrics['reach']) * 100, 2)
                : 0.0;

            // Get follower counts from social accounts
            $socialAccounts = SocialAccount::whereHas('workspace', function ($query) use ($workspaceId): void {
                $query->where('id', $workspaceId);
            })->get();

            $totalFollowers = $socialAccounts->sum(fn ($account) => $account->metrics['followers'] ?? 0);

            // Get previous day's follower count for comparison
            $previousAggregate = AnalyticsAggregate::forWorkspace($workspaceId)
                ->workspaceTotals()
                ->daily()
                ->where('date', $date->copy()->subDay()->toDateString())
                ->first();

            $followersStart = $previousAggregate?->followers_end ?? $totalFollowers;
            $metrics['followers_start'] = $followersStart;
            $metrics['followers_end'] = $totalFollowers;
            $metrics['followers_change'] = $totalFollowers - $followersStart;

            $aggregate = AnalyticsAggregate::upsertAggregate(
                $workspaceId,
                null,
                $date,
                PeriodType::DAILY,
                $metrics
            );

            $this->log('Daily metrics aggregated', [
                'workspace_id' => $workspaceId,
                'date' => $date->toDateString(),
                'posts_count' => $metrics['posts_count'],
            ]);

            return $aggregate;
        });
    }

    /**
     * Get engagement trend over a date range.
     *
     * Returns daily engagement data points for charting.
     *
     * @param string $workspaceId The workspace UUID
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return array<int, array<string, mixed>> Array of daily engagement data
     */
    public function getEngagementTrend(string $workspaceId, Carbon $start, Carbon $end): array
    {
        $aggregates = AnalyticsAggregate::forWorkspace($workspaceId)
            ->workspaceTotals()
            ->daily()
            ->inDateRange($start, $end)
            ->orderBy('date')
            ->get();

        $trend = [];
        $currentDate = $start->copy();

        while ($currentDate <= $end) {
            $dateString = $currentDate->toDateString();
            $aggregate = $aggregates->firstWhere('date', $currentDate->copy());

            $trend[] = [
                'date' => $dateString,
                'engagements' => $aggregate?->engagements ?? 0,
                'likes' => $aggregate?->likes ?? 0,
                'comments' => $aggregate?->comments ?? 0,
                'shares' => $aggregate?->shares ?? 0,
                'saves' => $aggregate?->saves ?? 0,
                'engagement_rate' => $aggregate?->engagement_rate ?? 0.0,
            ];

            $currentDate->addDay();
        }

        return $trend;
    }

    /**
     * Get follower growth trend over a date range.
     *
     * Returns daily follower data points for charting.
     *
     * @param string $workspaceId The workspace UUID
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return array<int, array<string, mixed>> Array of daily follower data
     */
    public function getFollowerGrowthTrend(string $workspaceId, Carbon $start, Carbon $end): array
    {
        $aggregates = AnalyticsAggregate::forWorkspace($workspaceId)
            ->workspaceTotals()
            ->daily()
            ->inDateRange($start, $end)
            ->orderBy('date')
            ->get();

        $trend = [];
        $previousFollowers = null;

        foreach ($aggregates as $aggregate) {
            $trend[] = [
                'date' => $aggregate->date->toDateString(),
                'followers' => $aggregate->followers_end,
                'change' => $aggregate->followers_change,
                'growth_rate' => $aggregate->getFollowerGrowthRate(),
            ];
            $previousFollowers = $aggregate->followers_end;
        }

        return $trend;
    }

    /**
     * Get metrics breakdown by platform.
     *
     * Returns aggregated metrics grouped by social platform.
     *
     * @param string $workspaceId The workspace UUID
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return array<string, array<string, mixed>> Metrics grouped by platform
     */
    public function getMetricsByPlatform(string $workspaceId, Carbon $start, Carbon $end): array
    {
        // Get all post targets with metrics for the period
        $targets = PostTarget::whereHas('post', function ($query) use ($workspaceId, $start, $end): void {
            $query->forWorkspace($workspaceId)
                ->published()
                ->whereBetween('published_at', [$start, $end]);
        })->get();

        $platformMetrics = [];

        foreach ($targets as $target) {
            $platform = $target->platform_code;

            if (!isset($platformMetrics[$platform])) {
                $platformMetrics[$platform] = [
                    'platform' => $platform,
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
                ];
            }

            $metrics = $target->metrics ?? [];
            $platformMetrics[$platform]['posts_count']++;
            $platformMetrics[$platform]['impressions'] += $metrics['impressions'] ?? 0;
            $platformMetrics[$platform]['reach'] += $metrics['reach'] ?? 0;
            $platformMetrics[$platform]['likes'] += $metrics['likes'] ?? 0;
            $platformMetrics[$platform]['comments'] += $metrics['comments'] ?? 0;
            $platformMetrics[$platform]['shares'] += $metrics['shares'] ?? 0;
            $platformMetrics[$platform]['saves'] += $metrics['saves'] ?? 0;
            $platformMetrics[$platform]['clicks'] += $metrics['clicks'] ?? 0;
            $platformMetrics[$platform]['video_views'] += $metrics['video_views'] ?? 0;
        }

        // Calculate engagements and rates for each platform
        foreach ($platformMetrics as $platform => $metrics) {
            $engagements = $metrics['likes'] + $metrics['comments']
                + $metrics['shares'] + $metrics['saves'];
            $platformMetrics[$platform]['engagements'] = $engagements;
            $platformMetrics[$platform]['engagement_rate'] = $metrics['reach'] > 0
                ? round(($engagements / $metrics['reach']) * 100, 2)
                : 0.0;
            $platformMetrics[$platform]['avg_engagements_per_post'] = $metrics['posts_count'] > 0
                ? round($engagements / $metrics['posts_count'], 2)
                : 0.0;
        }

        return array_values($platformMetrics);
    }

    /**
     * Parse a period string into start and end Carbon dates.
     *
     * Supported formats:
     * - '7d' - Last 7 days
     * - '30d' - Last 30 days
     * - '90d' - Last 90 days
     * - '1y' - Last 1 year
     * - '6m' - Last 6 months
     *
     * @param string $period Period string
     * @return array{start: Carbon, end: Carbon} Array with start and end dates
     */
    public function parsePeriod(string $period): array
    {
        $end = Carbon::today();
        $start = match (true) {
            str_ends_with($period, 'd') => $end->copy()->subDays((int) rtrim($period, 'd') - 1),
            str_ends_with($period, 'w') => $end->copy()->subWeeks((int) rtrim($period, 'w'))->addDay(),
            str_ends_with($period, 'm') => $end->copy()->subMonths((int) rtrim($period, 'm'))->addDay(),
            str_ends_with($period, 'y') => $end->copy()->subYears((int) rtrim($period, 'y'))->addDay(),
            default => $end->copy()->subDays(29), // Default to 30 days
        };

        return [
            'start' => $start->startOfDay(),
            'end' => $end->endOfDay(),
        ];
    }
}
