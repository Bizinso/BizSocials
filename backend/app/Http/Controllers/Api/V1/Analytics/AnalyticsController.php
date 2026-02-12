<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Data\Analytics\DashboardMetricsData;
use App\Data\Analytics\PlatformMetricsData;
use App\Data\Analytics\TrendDataPoint;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Workspace\Workspace;
use App\Services\Analytics\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
    ) {}

    /**
     * Get dashboard overview with key metrics.
     * GET /api/v1/workspaces/{workspace}/analytics/dashboard
     *
     * Query parameters:
     * - period: Time period (7d, 30d, 90d, 6m, 1y) - default: 30d
     *
     * @throws AuthorizationException
     */
    public function dashboard(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $period = $request->query('period', '30d');
        $dashboardData = $this->analyticsService->getDashboardMetrics($workspace->id, $period);

        $metrics = $dashboardData['metrics'];
        $comparison = $dashboardData['comparison']['metrics'] ?? [];

        $dashboardMetrics = new DashboardMetricsData(
            impressions: $metrics['impressions'] ?? 0,
            reach: $metrics['reach'] ?? 0,
            engagements: $metrics['engagements'] ?? 0,
            likes: $metrics['likes'] ?? 0,
            comments: $metrics['comments'] ?? 0,
            shares: $metrics['shares'] ?? 0,
            posts_published: $metrics['posts_count'] ?? 0,
            followers_total: $metrics['followers_current'] ?? 0,
            followers_gained: $metrics['followers_change'] ?? 0,
            engagement_rate: $metrics['engagement_rate'] ?? 0.0,
            impressions_change: $comparison['impressions']['percent_change'] ?? null,
            reach_change: $comparison['reach']['percent_change'] ?? null,
            engagement_change: $comparison['engagements']['percent_change'] ?? null,
            followers_change: $comparison['followers_change']['percent_change'] ?? null,
            period: $period,
            start_date: $dashboardData['period']['start'],
            end_date: $dashboardData['period']['end'],
        );

        return $this->success(
            $dashboardMetrics->toArray(),
            'Dashboard metrics retrieved successfully'
        );
    }

    /**
     * Get detailed metrics for a workspace.
     * GET /api/v1/workspaces/{workspace}/analytics/metrics
     *
     * Query parameters:
     * - period: Time period (7d, 30d, 90d, 6m, 1y) - default: 30d
     * - include_comparison: Include comparison with previous period (true/false) - default: true
     *
     * @throws AuthorizationException
     */
    public function metrics(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $period = $request->query('period', '30d');
        $includeComparison = $request->boolean('include_comparison', true);

        $dashboardData = $this->analyticsService->getDashboardMetrics($workspace->id, $period);

        $response = [
            'period' => $dashboardData['period'],
            'metrics' => $dashboardData['metrics'],
        ];

        if ($includeComparison) {
            $response['comparison'] = $dashboardData['comparison'];
        }

        return $this->success(
            $response,
            'Metrics retrieved successfully'
        );
    }

    /**
     * Get trend data for analytics charts.
     * GET /api/v1/workspaces/{workspace}/analytics/trends
     *
     * Query parameters:
     * - period: Time period (7d, 30d, 90d, 6m, 1y) - default: 30d
     * - metric: Metric to trend (engagements, followers, impressions, reach) - default: engagements
     *
     * @throws AuthorizationException
     */
    public function trends(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $period = $request->query('period', '30d');
        $metric = $request->query('metric', 'engagements');

        $dateRange = $this->analyticsService->parsePeriod($period);

        $trendData = match ($metric) {
            'followers' => $this->analyticsService->getFollowerGrowthTrend(
                $workspace->id,
                $dateRange['start'],
                $dateRange['end']
            ),
            default => $this->analyticsService->getEngagementTrend(
                $workspace->id,
                $dateRange['start'],
                $dateRange['end']
            ),
        };

        // Transform to TrendDataPoint format
        $transformedTrend = collect($trendData)->map(function (array $point, int $index) use ($metric, $trendData): array {
            $value = match ($metric) {
                'followers' => $point['followers'] ?? 0,
                'impressions' => $point['impressions'] ?? 0,
                'reach' => $point['reach'] ?? 0,
                default => $point['engagements'] ?? 0,
            };

            $previousValue = $index > 0
                ? match ($metric) {
                    'followers' => $trendData[$index - 1]['followers'] ?? 0,
                    'impressions' => $trendData[$index - 1]['impressions'] ?? 0,
                    'reach' => $trendData[$index - 1]['reach'] ?? 0,
                    default => $trendData[$index - 1]['engagements'] ?? 0,
                }
                : null;

            $changePercent = $previousValue !== null && $previousValue > 0
                ? round((($value - $previousValue) / $previousValue) * 100, 2)
                : null;

            return (new TrendDataPoint(
                date: $point['date'],
                value: $value,
                previous_value: $previousValue,
                change_percent: $changePercent,
            ))->toArray();
        })->values()->all();

        return $this->success(
            [
                'metric' => $metric,
                'period' => [
                    'start' => $dateRange['start']->toDateString(),
                    'end' => $dateRange['end']->toDateString(),
                ],
                'data' => $transformedTrend,
            ],
            'Trend data retrieved successfully'
        );
    }

    /**
     * Get platform-specific metrics breakdown.
     * GET /api/v1/workspaces/{workspace}/analytics/platforms
     *
     * Query parameters:
     * - period: Time period (7d, 30d, 90d, 6m, 1y) - default: 30d
     *
     * @throws AuthorizationException
     */
    public function platforms(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $period = $request->query('period', '30d');
        $dateRange = $this->analyticsService->parsePeriod($period);

        $platformData = $this->analyticsService->getMetricsByPlatform(
            $workspace->id,
            $dateRange['start'],
            $dateRange['end']
        );

        // Map platform codes to labels
        $platformLabels = [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'twitter' => 'Twitter',
            'linkedin' => 'LinkedIn',
            'whatsapp' => 'WhatsApp',
            'tiktok' => 'TikTok',
            'youtube' => 'YouTube',
            'pinterest' => 'Pinterest',
        ];

        $transformedPlatforms = collect($platformData)->map(function (array $metrics) use ($platformLabels): array {
            $platform = $metrics['platform'];

            return (new PlatformMetricsData(
                platform: $platform,
                platform_label: $platformLabels[$platform] ?? ucfirst($platform),
                impressions: $metrics['impressions'] ?? 0,
                reach: $metrics['reach'] ?? 0,
                engagements: $metrics['engagements'] ?? 0,
                likes: $metrics['likes'] ?? 0,
                comments: $metrics['comments'] ?? 0,
                shares: $metrics['shares'] ?? 0,
                posts_published: $metrics['posts_count'] ?? 0,
                followers_total: 0, // Will be populated from social accounts
                followers_gained: 0, // Will be populated from aggregates
                engagement_rate: $metrics['engagement_rate'] ?? 0.0,
                impressions_change: null,
                reach_change: null,
                engagement_change: null,
                followers_change: null,
            ))->toArray();
        })->values()->all();

        return $this->success(
            [
                'period' => [
                    'start' => $dateRange['start']->toDateString(),
                    'end' => $dateRange['end']->toDateString(),
                ],
                'platforms' => $transformedPlatforms,
            ],
            'Platform metrics retrieved successfully'
        );
    }
}
