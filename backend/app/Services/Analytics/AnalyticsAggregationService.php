<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\Analytics\PeriodType;
use App\Models\Analytics\AnalyticsAggregate;
use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Analytics Aggregation Service
 *
 * Aggregates daily analytics data into weekly and monthly summaries.
 * Calculates engagement rates, reach, and other derived metrics.
 */
final class AnalyticsAggregationService
{
    /**
     * Aggregate daily analytics into weekly summary.
     *
     * @param Workspace $workspace
     * @param SocialAccount|null $account
     * @param Carbon $weekStart Start of the week (Monday)
     * @return bool Success status
     */
    public function aggregateWeekly(
        Workspace $workspace,
        ?SocialAccount $account,
        Carbon $weekStart
    ): bool {
        try {
            $weekEnd = $weekStart->copy()->endOfWeek();

            // Get daily aggregates for the week
            $query = AnalyticsAggregate::query()
                ->forWorkspace($workspace->id)
                ->daily()
                ->inDateRange($weekStart, $weekEnd);

            if ($account !== null) {
                $query->forSocialAccount($account->id);
            } else {
                $query->workspaceTotals();
            }

            $dailyAggregates = $query->get();

            if ($dailyAggregates->isEmpty()) {
                Log::info('No daily data to aggregate for week', [
                    'workspace_id' => $workspace->id,
                    'account_id' => $account?->id,
                    'week_start' => $weekStart->toDateString(),
                ]);

                return false;
            }

            // Sum up metrics
            $metrics = $this->sumMetrics($dailyAggregates);

            // Get follower counts from first and last day
            $firstDay = $dailyAggregates->sortBy('date')->first();
            $lastDay = $dailyAggregates->sortByDesc('date')->first();

            $metrics['followers_start'] = $firstDay->followers_start;
            $metrics['followers_end'] = $lastDay->followers_end;
            $metrics['followers_change'] = $metrics['followers_end'] - $metrics['followers_start'];

            // Calculate engagement rate
            $metrics['engagement_rate'] = $metrics['reach'] > 0
                ? round(($metrics['engagements'] / $metrics['reach']) * 100, 2)
                : 0.0;

            // Store weekly aggregate
            AnalyticsAggregate::upsertAggregate(
                $workspace->id,
                $account?->id,
                $weekStart,
                PeriodType::WEEKLY,
                $metrics
            );

            Log::info('Weekly analytics aggregated', [
                'workspace_id' => $workspace->id,
                'account_id' => $account?->id,
                'week_start' => $weekStart->toDateString(),
            ]);

            // Clear analytics cache for this workspace
            $this->clearWorkspaceCache($workspace->id);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to aggregate weekly analytics', [
                'workspace_id' => $workspace->id,
                'account_id' => $account?->id,
                'week_start' => $weekStart->toDateString(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Aggregate daily analytics into monthly summary.
     *
     * @param Workspace $workspace
     * @param SocialAccount|null $account
     * @param Carbon $monthStart Start of the month
     * @return bool Success status
     */
    public function aggregateMonthly(
        Workspace $workspace,
        ?SocialAccount $account,
        Carbon $monthStart
    ): bool {
        try {
            $monthEnd = $monthStart->copy()->endOfMonth();

            // Get daily aggregates for the month
            $query = AnalyticsAggregate::query()
                ->forWorkspace($workspace->id)
                ->daily()
                ->inDateRange($monthStart, $monthEnd);

            if ($account !== null) {
                $query->forSocialAccount($account->id);
            } else {
                $query->workspaceTotals();
            }

            $dailyAggregates = $query->get();

            if ($dailyAggregates->isEmpty()) {
                Log::info('No daily data to aggregate for month', [
                    'workspace_id' => $workspace->id,
                    'account_id' => $account?->id,
                    'month_start' => $monthStart->toDateString(),
                ]);

                return false;
            }

            // Sum up metrics
            $metrics = $this->sumMetrics($dailyAggregates);

            // Get follower counts from first and last day
            $firstDay = $dailyAggregates->sortBy('date')->first();
            $lastDay = $dailyAggregates->sortByDesc('date')->first();

            $metrics['followers_start'] = $firstDay->followers_start;
            $metrics['followers_end'] = $lastDay->followers_end;
            $metrics['followers_change'] = $metrics['followers_end'] - $metrics['followers_start'];

            // Calculate engagement rate
            $metrics['engagement_rate'] = $metrics['reach'] > 0
                ? round(($metrics['engagements'] / $metrics['reach']) * 100, 2)
                : 0.0;

            // Store monthly aggregate
            AnalyticsAggregate::upsertAggregate(
                $workspace->id,
                $account?->id,
                $monthStart,
                PeriodType::MONTHLY,
                $metrics
            );

            Log::info('Monthly analytics aggregated', [
                'workspace_id' => $workspace->id,
                'account_id' => $account?->id,
                'month_start' => $monthStart->toDateString(),
            ]);

            // Clear analytics cache for this workspace
            $this->clearWorkspaceCache($workspace->id);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to aggregate monthly analytics', [
                'workspace_id' => $workspace->id,
                'account_id' => $account?->id,
                'month_start' => $monthStart->toDateString(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Aggregate workspace-level totals from account-level data.
     *
     * @param Workspace $workspace
     * @param Carbon $date
     * @param PeriodType $periodType
     * @return bool Success status
     */
    public function aggregateWorkspaceTotals(
        Workspace $workspace,
        Carbon $date,
        PeriodType $periodType
    ): bool {
        try {
            // Get all account aggregates for this period
            $accountAggregates = AnalyticsAggregate::query()
                ->forWorkspace($workspace->id)
                ->forPeriod($periodType)
                ->whereDate('date', $date)
                ->whereNotNull('social_account_id')
                ->get();

            if ($accountAggregates->isEmpty()) {
                Log::info('No account data to aggregate for workspace', [
                    'workspace_id' => $workspace->id,
                    'date' => $date->toDateString(),
                    'period_type' => $periodType->value,
                ]);

                return false;
            }

            // Sum up metrics across all accounts
            $metrics = $this->sumMetrics($accountAggregates);

            // Get follower counts from first and last account
            $firstAccount = $accountAggregates->sortBy('date')->first();
            $lastAccount = $accountAggregates->sortByDesc('date')->first();

            // For workspace totals, sum all follower counts
            $metrics['followers_start'] = $accountAggregates->sum('followers_start');
            $metrics['followers_end'] = $accountAggregates->sum('followers_end');
            $metrics['followers_change'] = $accountAggregates->sum('followers_change');

            // Calculate engagement rate
            $metrics['engagement_rate'] = $metrics['reach'] > 0
                ? round(($metrics['engagements'] / $metrics['reach']) * 100, 2)
                : 0.0;

            // Store workspace-level aggregate
            AnalyticsAggregate::upsertAggregate(
                $workspace->id,
                null, // null for workspace totals
                $date,
                $periodType,
                $metrics
            );

            Log::info('Workspace totals aggregated', [
                'workspace_id' => $workspace->id,
                'date' => $date->toDateString(),
                'period_type' => $periodType->value,
            ]);

            // Clear analytics cache for this workspace
            $this->clearWorkspaceCache($workspace->id);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to aggregate workspace totals', [
                'workspace_id' => $workspace->id,
                'date' => $date->toDateString(),
                'period_type' => $periodType->value,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Aggregate all periods for a workspace.
     *
     * @param Workspace $workspace
     * @param Carbon $date
     * @return array{daily: bool, weekly: bool, monthly: bool}
     */
    public function aggregateAllPeriods(Workspace $workspace, Carbon $date): array
    {
        $results = [
            'daily' => false,
            'weekly' => false,
            'monthly' => false,
        ];

        // Aggregate daily workspace totals
        $results['daily'] = $this->aggregateWorkspaceTotals(
            $workspace,
            $date,
            PeriodType::DAILY
        );

        // Aggregate weekly if it's the end of the week
        if ($date->isEndOfWeek()) {
            $weekStart = $date->copy()->startOfWeek();

            // Aggregate for each account
            $accounts = SocialAccount::query()
                ->forWorkspace($workspace->id)
                ->connected()
                ->get();

            foreach ($accounts as $account) {
                $this->aggregateWeekly($workspace, $account, $weekStart);
            }

            // Aggregate workspace totals
            $results['weekly'] = $this->aggregateWorkspaceTotals(
                $workspace,
                $weekStart,
                PeriodType::WEEKLY
            );
        }

        // Aggregate monthly if it's the end of the month
        if ($date->isLastOfMonth()) {
            $monthStart = $date->copy()->startOfMonth();

            // Aggregate for each account
            $accounts = SocialAccount::query()
                ->forWorkspace($workspace->id)
                ->connected()
                ->get();

            foreach ($accounts as $account) {
                $this->aggregateMonthly($workspace, $account, $monthStart);
            }

            // Aggregate workspace totals
            $results['monthly'] = $this->aggregateWorkspaceTotals(
                $workspace,
                $monthStart,
                PeriodType::MONTHLY
            );
        }

        return $results;
    }

    /**
     * Sum metrics from a collection of aggregates.
     *
     * @param \Illuminate\Support\Collection<int, AnalyticsAggregate> $aggregates
     * @return array<string, int|float>
     */
    private function sumMetrics($aggregates): array
    {
        return [
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
    }

    /**
     * Calculate engagement rate from metrics.
     *
     * @param int $engagements
     * @param int $reach
     * @return float
     */
    private function calculateEngagementRate(int $engagements, int $reach): float
    {
        if ($reach === 0) {
            return 0.0;
        }

        return round(($engagements / $reach) * 100, 2);
    }

    /**
     * Get analytics summary for a workspace.
     *
     * @param Workspace $workspace
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param PeriodType $periodType
     * @return array<string, mixed>
     */
    public function getAnalyticsSummary(
        Workspace $workspace,
        Carbon $startDate,
        Carbon $endDate,
        PeriodType $periodType = PeriodType::DAILY
    ): array {
        $aggregates = AnalyticsAggregate::query()
            ->forWorkspace($workspace->id)
            ->workspaceTotals()
            ->forPeriod($periodType)
            ->inDateRange($startDate, $endDate)
            ->orderBy('date')
            ->get();

        if ($aggregates->isEmpty()) {
            return [
                'total_impressions' => 0,
                'total_reach' => 0,
                'total_engagements' => 0,
                'total_likes' => 0,
                'total_comments' => 0,
                'total_shares' => 0,
                'total_clicks' => 0,
                'total_video_views' => 0,
                'avg_engagement_rate' => 0.0,
                'follower_growth' => 0,
                'data_points' => [],
            ];
        }

        $metrics = $this->sumMetrics($aggregates);
        $firstAggregate = $aggregates->first();
        $lastAggregate = $aggregates->last();

        return [
            'total_impressions' => $metrics['impressions'],
            'total_reach' => $metrics['reach'],
            'total_engagements' => $metrics['engagements'],
            'total_likes' => $metrics['likes'],
            'total_comments' => $metrics['comments'],
            'total_shares' => $metrics['shares'],
            'total_clicks' => $metrics['clicks'],
            'total_video_views' => $metrics['video_views'],
            'avg_engagement_rate' => $aggregates->avg('engagement_rate'),
            'follower_growth' => $lastAggregate->followers_end - $firstAggregate->followers_start,
            'data_points' => $aggregates->map(fn ($agg) => [
                'date' => $agg->date->toDateString(),
                'impressions' => $agg->impressions,
                'reach' => $agg->reach,
                'engagements' => $agg->engagements,
                'engagement_rate' => $agg->engagement_rate,
                'followers' => $agg->followers_end,
            ])->toArray(),
        ];
    }

    /**
     * Clear analytics cache for a workspace.
     *
     * @param string $workspaceId
     * @return void
     */
    private function clearWorkspaceCache(string $workspaceId): void
    {
        // Clear dashboard cache for all periods
        $periods = ['7d', '30d', '90d', '6m', '1y'];
        foreach ($periods as $period) {
            Cache::forget("analytics:dashboard:{$workspaceId}:{$period}");
        }

        // Note: For trend and platform caches, we'd need to track all date ranges
        // For simplicity, we're clearing the most common ones
        // In production, consider using Cache::tags() with Redis for better cache management
    }
}
