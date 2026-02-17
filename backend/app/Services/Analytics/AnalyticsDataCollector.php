<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Analytics\AnalyticsAggregate;
use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Analytics Data Collector Service
 *
 * Collects and stores analytics data from social platforms.
 * Stores raw analytics data in the database for aggregation.
 */
final class AnalyticsDataCollector
{
    public function __construct(
        private readonly PlatformAnalyticsFetcher $platformFetcher
    ) {}

    /**
     * Collect analytics for a social account and store in database.
     *
     * @param SocialAccount $account
     * @param Carbon $date
     * @return bool Success status
     */
    public function collectDailyAnalytics(SocialAccount $account, Carbon $date): bool
    {
        try {
            // Fetch analytics from platform
            $analytics = $this->platformFetcher->fetchAnalytics(
                $account,
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay()
            );

            if (empty($analytics)) {
                Log::warning('No analytics data fetched', [
                    'account_id' => $account->id,
                    'platform' => $account->platform->value,
                    'date' => $date->toDateString(),
                ]);

                return false;
            }

            // Get follower count
            $followersEnd = $this->platformFetcher->fetchFollowerCount($account);

            // Get previous day's follower count for comparison
            $previousAggregate = AnalyticsAggregate::query()
                ->forSocialAccount($account->id)
                ->daily()
                ->whereDate('date', $date->copy()->subDay())
                ->first();

            $followersStart = $previousAggregate?->followers_end ?? $followersEnd;
            $followersChange = $followersEnd - $followersStart;

            // Calculate engagement rate
            $engagementRate = $analytics['reach'] > 0
                ? ($analytics['engagements'] / $analytics['reach']) * 100
                : 0.0;

            // Store in database
            $data = [
                'impressions' => $analytics['impressions'],
                'reach' => $analytics['reach'],
                'engagements' => $analytics['engagements'],
                'likes' => $analytics['likes'],
                'comments' => $analytics['comments'],
                'shares' => $analytics['shares'],
                'saves' => $analytics['saves'],
                'clicks' => $analytics['clicks'],
                'video_views' => $analytics['video_views'],
                'posts_count' => 0, // Will be calculated from posts table
                'engagement_rate' => round($engagementRate, 2),
                'followers_start' => $followersStart,
                'followers_end' => $followersEnd,
                'followers_change' => $followersChange,
            ];

            AnalyticsAggregate::upsertAggregate(
                $account->workspace_id,
                $account->id,
                $date,
                \App\Enums\Analytics\PeriodType::DAILY,
                $data
            );

            Log::info('Analytics collected successfully', [
                'account_id' => $account->id,
                'platform' => $account->platform->value,
                'date' => $date->toDateString(),
            ]);

            // Clear analytics cache for this workspace
            $this->clearWorkspaceCache($account->workspace_id);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to collect analytics', [
                'account_id' => $account->id,
                'platform' => $account->platform->value,
                'date' => $date->toDateString(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Collect analytics for all social accounts in a workspace.
     *
     * @param Workspace $workspace
     * @param Carbon $date
     * @return array{success: int, failed: int}
     */
    public function collectWorkspaceAnalytics(Workspace $workspace, Carbon $date): array
    {
        $accounts = SocialAccount::query()
            ->forWorkspace($workspace->id)
            ->connected()
            ->get();

        $success = 0;
        $failed = 0;

        foreach ($accounts as $account) {
            if ($this->collectDailyAnalytics($account, $date)) {
                $success++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
        ];
    }

    /**
     * Collect analytics for a date range.
     *
     * @param SocialAccount $account
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int Number of days collected
     */
    public function collectAnalyticsRange(
        SocialAccount $account,
        Carbon $startDate,
        Carbon $endDate
    ): int {
        $collected = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            if ($this->collectDailyAnalytics($account, $currentDate)) {
                $collected++;
            }

            $currentDate->addDay();
        }

        return $collected;
    }

    /**
     * Backfill analytics for missing dates.
     *
     * @param SocialAccount $account
     * @param int $days Number of days to backfill
     * @return int Number of days backfilled
     */
    public function backfillAnalytics(SocialAccount $account, int $days = 30): int
    {
        $endDate = now()->subDay(); // Yesterday
        $startDate = $endDate->copy()->subDays($days - 1);

        // Get existing dates
        $existingDates = AnalyticsAggregate::query()
            ->forSocialAccount($account->id)
            ->daily()
            ->inDateRange($startDate, $endDate)
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->toArray();

        $backfilled = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->toDateString();

            if (!in_array($dateString, $existingDates, true)) {
                if ($this->collectDailyAnalytics($account, $currentDate)) {
                    $backfilled++;
                }
            }

            $currentDate->addDay();
        }

        return $backfilled;
    }

    /**
     * Get the last collection date for an account.
     *
     * @param SocialAccount $account
     * @return Carbon|null
     */
    public function getLastCollectionDate(SocialAccount $account): ?Carbon
    {
        $aggregate = AnalyticsAggregate::query()
            ->forSocialAccount($account->id)
            ->daily()
            ->orderByDesc('date')
            ->first();

        return $aggregate?->date;
    }

    /**
     * Check if analytics need to be collected for today.
     *
     * @param SocialAccount $account
     * @return bool
     */
    public function needsCollection(SocialAccount $account): bool
    {
        $lastCollection = $this->getLastCollectionDate($account);

        if ($lastCollection === null) {
            return true;
        }

        // Collect if last collection was before yesterday
        return $lastCollection->lt(now()->subDay()->startOfDay());
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
    }
}
