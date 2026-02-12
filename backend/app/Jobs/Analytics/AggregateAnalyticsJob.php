<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use App\Enums\Analytics\PeriodType;
use App\Models\Analytics\AnalyticsAggregate;
use App\Models\Inbox\PostMetricSnapshot;
use App\Models\Social\SocialAccount;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AggregateAnalyticsJob
 *
 * Aggregates analytics data for a workspace on a scheduled basis.
 * Runs daily/weekly/monthly to consolidate post metrics into aggregate tables.
 *
 * Features:
 * - Aggregates metrics by workspace and social account
 * - Supports daily, weekly, and monthly periods
 * - Uses upsert for idempotent operations
 * - Calculates engagement rates and follower changes
 */
final class AggregateAnalyticsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $workspaceId,
        public readonly PeriodType $periodType,
        public readonly Carbon $date,
    ) {
        $this->onQueue('analytics');
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return sprintf(
            'aggregate:%s:%s:%s',
            $this->workspaceId,
            $this->periodType->value,
            $this->date->format('Y-m-d')
        );
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[AggregateAnalyticsJob] Starting aggregation', [
            'workspace_id' => $this->workspaceId,
            'period_type' => $this->periodType->value,
            'date' => $this->date->format('Y-m-d'),
        ]);

        $dateRange = $this->getDateRange();

        // Get all social accounts for the workspace
        $socialAccounts = SocialAccount::where('workspace_id', $this->workspaceId)->get();

        $aggregatedCount = 0;

        // Aggregate for each social account
        foreach ($socialAccounts as $account) {
            $this->aggregateForAccount($account, $dateRange);
            $aggregatedCount++;
        }

        // Also aggregate workspace-level totals (null social_account_id)
        $this->aggregateForWorkspace($dateRange);

        Log::info('[AggregateAnalyticsJob] Aggregation completed', [
            'workspace_id' => $this->workspaceId,
            'accounts_processed' => $aggregatedCount,
        ]);
    }

    /**
     * Get the date range for the period.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    private function getDateRange(): array
    {
        return match ($this->periodType) {
            PeriodType::DAILY => [
                'start' => $this->date->copy()->startOfDay(),
                'end' => $this->date->copy()->endOfDay(),
            ],
            PeriodType::WEEKLY => [
                'start' => $this->date->copy()->startOfWeek(),
                'end' => $this->date->copy()->endOfWeek(),
            ],
            PeriodType::MONTHLY => [
                'start' => $this->date->copy()->startOfMonth(),
                'end' => $this->date->copy()->endOfMonth(),
            ],
        };
    }

    /**
     * Aggregate metrics for a specific social account.
     *
     * @param array{start: Carbon, end: Carbon} $dateRange
     */
    private function aggregateForAccount(SocialAccount $account, array $dateRange): void
    {
        $metrics = PostMetricSnapshot::query()
            ->join('post_targets', 'post_metric_snapshots.post_target_id', '=', 'post_targets.id')
            ->where('post_targets.social_account_id', $account->id)
            ->whereBetween('post_metric_snapshots.captured_at', [$dateRange['start'], $dateRange['end']])
            ->select([
                DB::raw('COALESCE(SUM(post_metric_snapshots.impressions_count), 0) as impressions'),
                DB::raw('COALESCE(SUM(post_metric_snapshots.reach_count), 0) as reach'),
                DB::raw('COALESCE(SUM(post_metric_snapshots.likes_count), 0) + COALESCE(SUM(post_metric_snapshots.comments_count), 0) + COALESCE(SUM(post_metric_snapshots.shares_count), 0) as engagements'),
                DB::raw('COALESCE(SUM(post_metric_snapshots.likes_count), 0) as likes'),
                DB::raw('COALESCE(SUM(post_metric_snapshots.comments_count), 0) as comments'),
                DB::raw('COALESCE(SUM(post_metric_snapshots.shares_count), 0) as shares'),
                DB::raw('0 as saves'),
                DB::raw('COALESCE(SUM(post_metric_snapshots.clicks_count), 0) as clicks'),
                DB::raw('0 as video_views'),
                DB::raw('COUNT(DISTINCT post_targets.id) as posts_count'),
            ])
            ->first();

        if ($metrics === null) {
            return;
        }

        // Calculate engagement rate
        $engagementRate = 0;
        if ($metrics->impressions > 0) {
            $engagementRate = ($metrics->engagements / $metrics->impressions) * 100;
        }

        // Get follower data (simplified - using current follower count)
        $followersEnd = $account->follower_count ?? 0;
        $followersStart = $followersEnd; // In a real scenario, we'd track this historically
        $followersChange = 0;

        AnalyticsAggregate::upsertAggregate(
            workspaceId: $this->workspaceId,
            date: $this->date,
            periodType: $this->periodType,
            socialAccountId: $account->id,
            metrics: [
                'impressions' => (int) $metrics->impressions,
                'reach' => (int) $metrics->reach,
                'engagements' => (int) $metrics->engagements,
                'likes' => (int) $metrics->likes,
                'comments' => (int) $metrics->comments,
                'shares' => (int) $metrics->shares,
                'saves' => (int) $metrics->saves,
                'clicks' => (int) $metrics->clicks,
                'video_views' => (int) $metrics->video_views,
                'posts_count' => (int) $metrics->posts_count,
                'engagement_rate' => round($engagementRate, 4),
                'followers_start' => $followersStart,
                'followers_end' => $followersEnd,
                'followers_change' => $followersChange,
            ]
        );
    }

    /**
     * Aggregate workspace-level totals.
     *
     * @param array{start: Carbon, end: Carbon} $dateRange
     */
    private function aggregateForWorkspace(array $dateRange): void
    {
        $metrics = PostMetricSnapshot::query()
            ->join('post_targets', 'post_metric_snapshots.post_target_id', '=', 'post_targets.id')
            ->join('posts', 'post_targets.post_id', '=', 'posts.id')
            ->where('posts.workspace_id', $this->workspaceId)
            ->whereBetween('post_metric_snapshots.captured_at', [$dateRange['start'], $dateRange['end']])
            ->select([
                DB::raw('COALESCE(SUM(post_metric_snapshots.impressions_count), 0) as impressions'),
                DB::raw('COALESCE(SUM(post_metric_snapshots.reach_count), 0) as reach'),
                DB::raw('COALESCE(SUM(post_metric_snapshots.likes_count), 0) + COALESCE(SUM(post_metric_snapshots.comments_count), 0) + COALESCE(SUM(post_metric_snapshots.shares_count), 0) as engagements'),
                DB::raw('COALESCE(SUM(post_metric_snapshots.likes_count), 0) as likes'),
                DB::raw('COALESCE(SUM(post_metric_snapshots.comments_count), 0) as comments'),
                DB::raw('COALESCE(SUM(post_metric_snapshots.shares_count), 0) as shares'),
                DB::raw('0 as saves'),
                DB::raw('COALESCE(SUM(post_metric_snapshots.clicks_count), 0) as clicks'),
                DB::raw('0 as video_views'),
                DB::raw('COUNT(DISTINCT post_targets.id) as posts_count'),
            ])
            ->first();

        if ($metrics === null) {
            return;
        }

        // Calculate engagement rate
        $engagementRate = 0;
        if ($metrics->impressions > 0) {
            $engagementRate = ($metrics->engagements / $metrics->impressions) * 100;
        }

        // Get total followers for workspace
        $totalFollowers = SocialAccount::where('workspace_id', $this->workspaceId)
            ->sum('follower_count') ?? 0;

        AnalyticsAggregate::upsertAggregate(
            workspaceId: $this->workspaceId,
            date: $this->date,
            periodType: $this->periodType,
            socialAccountId: null,
            metrics: [
                'impressions' => (int) $metrics->impressions,
                'reach' => (int) $metrics->reach,
                'engagements' => (int) $metrics->engagements,
                'likes' => (int) $metrics->likes,
                'comments' => (int) $metrics->comments,
                'shares' => (int) $metrics->shares,
                'saves' => (int) $metrics->saves,
                'clicks' => (int) $metrics->clicks,
                'video_views' => (int) $metrics->video_views,
                'posts_count' => (int) $metrics->posts_count,
                'engagement_rate' => round($engagementRate, 4),
                'followers_start' => (int) $totalFollowers,
                'followers_end' => (int) $totalFollowers,
                'followers_change' => 0,
            ]
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('[AggregateAnalyticsJob] Job failed', [
            'workspace_id' => $this->workspaceId,
            'period_type' => $this->periodType->value,
            'date' => $this->date->format('Y-m-d'),
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);
    }
}
