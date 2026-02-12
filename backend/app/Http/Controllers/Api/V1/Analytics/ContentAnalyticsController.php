<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Data\Analytics\ContentPerformanceData;
use App\Data\Analytics\TopPostData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Workspace\Workspace;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ContentAnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
    ) {}

    /**
     * Get content performance overview.
     * GET /api/v1/workspaces/{workspace}/analytics/content
     *
     * Query parameters:
     * - period: Time period (7d, 30d, 90d, 6m, 1y) - default: 30d
     *
     * @throws AuthorizationException
     */
    public function overview(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $period = $request->query('period', '30d');
        $dateRange = $this->analyticsService->parsePeriod($period);

        // Get published posts with their metrics
        $posts = Post::forWorkspace($workspace->id)
            ->published()
            ->whereBetween('published_at', [$dateRange['start'], $dateRange['end']])
            ->with('targets')
            ->get();

        $totalPosts = $posts->count();
        $totalImpressions = 0;
        $totalReach = 0;
        $totalEngagements = 0;
        $totalLikes = 0;
        $totalComments = 0;
        $totalShares = 0;

        foreach ($posts as $post) {
            foreach ($post->targets as $target) {
                $metrics = $target->metrics ?? [];
                $totalImpressions += $metrics['impressions'] ?? 0;
                $totalReach += $metrics['reach'] ?? 0;
                $totalLikes += $metrics['likes'] ?? 0;
                $totalComments += $metrics['comments'] ?? 0;
                $totalShares += $metrics['shares'] ?? 0;
            }
        }

        $totalEngagements = $totalLikes + $totalComments + $totalShares;
        $avgEngagementRate = $totalReach > 0
            ? round(($totalEngagements / $totalReach) * 100, 2)
            : 0.0;

        return $this->success(
            [
                'period' => [
                    'start' => $dateRange['start']->toDateString(),
                    'end' => $dateRange['end']->toDateString(),
                ],
                'overview' => [
                    'total_posts' => $totalPosts,
                    'total_impressions' => $totalImpressions,
                    'total_reach' => $totalReach,
                    'total_engagements' => $totalEngagements,
                    'total_likes' => $totalLikes,
                    'total_comments' => $totalComments,
                    'total_shares' => $totalShares,
                    'avg_impressions_per_post' => $totalPosts > 0 ? round($totalImpressions / $totalPosts, 2) : 0,
                    'avg_reach_per_post' => $totalPosts > 0 ? round($totalReach / $totalPosts, 2) : 0,
                    'avg_engagements_per_post' => $totalPosts > 0 ? round($totalEngagements / $totalPosts, 2) : 0,
                    'avg_engagement_rate' => $avgEngagementRate,
                ],
            ],
            'Content overview retrieved successfully'
        );
    }

    /**
     * Get top performing posts.
     * GET /api/v1/workspaces/{workspace}/analytics/content/top-posts
     *
     * Query parameters:
     * - period: Time period (7d, 30d, 90d, 6m, 1y) - default: 30d
     * - metric: Sorting metric (engagements, impressions, reach) - default: engagements
     * - limit: Number of posts to return (max 50) - default: 10
     *
     * @throws AuthorizationException
     */
    public function topPosts(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $period = $request->query('period', '30d');
        $metric = $request->query('metric', 'engagements');
        $limit = min((int) $request->query('limit', 10), 50);

        $dateRange = $this->analyticsService->parsePeriod($period);

        // Map platform codes to labels
        $platformLabels = [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'twitter' => 'Twitter',
            'linkedin' => 'LinkedIn',
            'tiktok' => 'TikTok',
            'youtube' => 'YouTube',
            'pinterest' => 'Pinterest',
        ];

        // Get posts with their targets and calculate metrics
        $posts = Post::forWorkspace($workspace->id)
            ->published()
            ->whereBetween('published_at', [$dateRange['start'], $dateRange['end']])
            ->with(['targets', 'media'])
            ->get();

        $postsWithMetrics = $posts->map(function (Post $post) use ($platformLabels) {
            $aggregatedMetrics = [
                'impressions' => 0,
                'reach' => 0,
                'engagements' => 0,
                'likes' => 0,
                'comments' => 0,
                'shares' => 0,
                'platform' => 'unknown',
                'platform_label' => 'Unknown',
            ];

            foreach ($post->targets as $target) {
                $metrics = $target->metrics ?? [];
                $aggregatedMetrics['impressions'] += $metrics['impressions'] ?? 0;
                $aggregatedMetrics['reach'] += $metrics['reach'] ?? 0;
                $aggregatedMetrics['likes'] += $metrics['likes'] ?? 0;
                $aggregatedMetrics['comments'] += $metrics['comments'] ?? 0;
                $aggregatedMetrics['shares'] += $metrics['shares'] ?? 0;

                // Use the first target's platform
                if ($aggregatedMetrics['platform'] === 'unknown') {
                    $platform = $target->platform_code ?? 'unknown';
                    $aggregatedMetrics['platform'] = $platform;
                    $aggregatedMetrics['platform_label'] = $platformLabels[$platform] ?? ucfirst($platform);
                }
            }

            $aggregatedMetrics['engagements'] = $aggregatedMetrics['likes']
                + $aggregatedMetrics['comments']
                + $aggregatedMetrics['shares'];

            $aggregatedMetrics['engagement_rate'] = $aggregatedMetrics['reach'] > 0
                ? round(($aggregatedMetrics['engagements'] / $aggregatedMetrics['reach']) * 100, 2)
                : 0.0;

            return [
                'post' => $post,
                'metrics' => $aggregatedMetrics,
            ];
        });

        // Sort by the specified metric
        $sortedPosts = $postsWithMetrics->sortByDesc(function (array $item) use ($metric) {
            return $item['metrics'][$metric] ?? 0;
        })->take($limit)->values();

        $topPostsData = TopPostData::fromCollection($sortedPosts);

        return $this->success(
            [
                'period' => [
                    'start' => $dateRange['start']->toDateString(),
                    'end' => $dateRange['end']->toDateString(),
                ],
                'sort_metric' => $metric,
                'posts' => $topPostsData,
            ],
            'Top posts retrieved successfully'
        );
    }

    /**
     * Get performance breakdown by content type.
     * GET /api/v1/workspaces/{workspace}/analytics/content/by-type
     *
     * Query parameters:
     * - period: Time period (7d, 30d, 90d, 6m, 1y) - default: 30d
     *
     * @throws AuthorizationException
     */
    public function byContentType(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $period = $request->query('period', '30d');
        $dateRange = $this->analyticsService->parsePeriod($period);

        // Get posts grouped by content type
        $posts = Post::forWorkspace($workspace->id)
            ->published()
            ->whereBetween('published_at', [$dateRange['start'], $dateRange['end']])
            ->with('targets')
            ->get();

        $contentTypeMetrics = [];
        $totalPosts = 0;
        $totalEngagements = 0;

        // Define content type labels
        $contentTypeLabels = [
            'text' => 'Text Post',
            'image' => 'Image Post',
            'video' => 'Video Post',
            'link' => 'Link Post',
            'carousel' => 'Carousel Post',
            'story' => 'Story',
            'reel' => 'Reel',
        ];

        foreach ($posts as $post) {
            $contentType = $post->content_type ?? 'text';
            $totalPosts++;

            if (!isset($contentTypeMetrics[$contentType])) {
                $contentTypeMetrics[$contentType] = [
                    'total_posts' => 0,
                    'total_impressions' => 0,
                    'total_reach' => 0,
                    'total_engagements' => 0,
                ];
            }

            $contentTypeMetrics[$contentType]['total_posts']++;

            foreach ($post->targets as $target) {
                $metrics = $target->metrics ?? [];
                $impressions = $metrics['impressions'] ?? 0;
                $reach = $metrics['reach'] ?? 0;
                $engagements = ($metrics['likes'] ?? 0)
                    + ($metrics['comments'] ?? 0)
                    + ($metrics['shares'] ?? 0);

                $contentTypeMetrics[$contentType]['total_impressions'] += $impressions;
                $contentTypeMetrics[$contentType]['total_reach'] += $reach;
                $contentTypeMetrics[$contentType]['total_engagements'] += $engagements;
                $totalEngagements += $engagements;
            }
        }

        // Transform to ContentPerformanceData
        $performanceData = collect($contentTypeMetrics)->map(function (array $metrics, string $contentType) use ($contentTypeLabels, $totalPosts, $totalEngagements): array {
            $avgImpressions = $metrics['total_posts'] > 0
                ? round($metrics['total_impressions'] / $metrics['total_posts'], 2)
                : 0.0;

            $avgReach = $metrics['total_posts'] > 0
                ? round($metrics['total_reach'] / $metrics['total_posts'], 2)
                : 0.0;

            $avgEngagements = $metrics['total_posts'] > 0
                ? round($metrics['total_engagements'] / $metrics['total_posts'], 2)
                : 0.0;

            $avgEngagementRate = $metrics['total_reach'] > 0
                ? round(($metrics['total_engagements'] / $metrics['total_reach']) * 100, 2)
                : 0.0;

            $shareOfPosts = $totalPosts > 0
                ? round(($metrics['total_posts'] / $totalPosts) * 100, 2)
                : 0.0;

            $shareOfEngagement = $totalEngagements > 0
                ? round(($metrics['total_engagements'] / $totalEngagements) * 100, 2)
                : 0.0;

            return (new ContentPerformanceData(
                content_type: $contentType,
                content_type_label: $contentTypeLabels[$contentType] ?? ucfirst($contentType),
                total_posts: $metrics['total_posts'],
                total_impressions: $metrics['total_impressions'],
                total_reach: $metrics['total_reach'],
                total_engagements: $metrics['total_engagements'],
                avg_impressions: $avgImpressions,
                avg_reach: $avgReach,
                avg_engagements: $avgEngagements,
                avg_engagement_rate: $avgEngagementRate,
                share_of_posts: $shareOfPosts,
                share_of_engagement: $shareOfEngagement,
            ))->toArray();
        })->values()->all();

        return $this->success(
            [
                'period' => [
                    'start' => $dateRange['start']->toDateString(),
                    'end' => $dateRange['end']->toDateString(),
                ],
                'content_types' => $performanceData,
            ],
            'Content type performance retrieved successfully'
        );
    }

    /**
     * Get best times to post based on historical engagement.
     * GET /api/v1/workspaces/{workspace}/analytics/content/best-times
     *
     * Query parameters:
     * - period: Time period (7d, 30d, 90d, 6m, 1y) - default: 90d
     * - platform: Filter by platform (optional)
     *
     * @throws AuthorizationException
     */
    public function bestTimes(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $period = $request->query('period', '90d');
        $platform = $request->query('platform');
        $dateRange = $this->analyticsService->parsePeriod($period);

        // Get published posts with their targets
        $postsQuery = Post::forWorkspace($workspace->id)
            ->published()
            ->whereBetween('published_at', [$dateRange['start'], $dateRange['end']])
            ->with('targets');

        $posts = $postsQuery->get();

        // Group engagement by day of week and hour
        $hourlyEngagement = [];
        $dayOfWeekEngagement = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $hourlyEngagement[$hour] = [
                'posts' => 0,
                'total_engagements' => 0,
            ];
        }

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        for ($day = 0; $day < 7; $day++) {
            $dayOfWeekEngagement[$day] = [
                'day_name' => $dayNames[$day],
                'posts' => 0,
                'total_engagements' => 0,
            ];
        }

        foreach ($posts as $post) {
            if ($post->published_at === null) {
                continue;
            }

            $hour = (int) $post->published_at->format('G');
            $dayOfWeek = (int) $post->published_at->format('w');

            foreach ($post->targets as $target) {
                // Filter by platform if specified
                if ($platform !== null && $target->platform_code !== $platform) {
                    continue;
                }

                $metrics = $target->metrics ?? [];
                $engagements = ($metrics['likes'] ?? 0)
                    + ($metrics['comments'] ?? 0)
                    + ($metrics['shares'] ?? 0);

                $hourlyEngagement[$hour]['posts']++;
                $hourlyEngagement[$hour]['total_engagements'] += $engagements;

                $dayOfWeekEngagement[$dayOfWeek]['posts']++;
                $dayOfWeekEngagement[$dayOfWeek]['total_engagements'] += $engagements;
            }
        }

        // Calculate averages and find best times
        $hourlyData = [];
        foreach ($hourlyEngagement as $hour => $data) {
            $avgEngagement = $data['posts'] > 0
                ? round($data['total_engagements'] / $data['posts'], 2)
                : 0.0;

            $hourlyData[] = [
                'hour' => $hour,
                'hour_label' => sprintf('%02d:00', $hour),
                'posts_count' => $data['posts'],
                'avg_engagements' => $avgEngagement,
            ];
        }

        $dailyData = [];
        foreach ($dayOfWeekEngagement as $day => $data) {
            $avgEngagement = $data['posts'] > 0
                ? round($data['total_engagements'] / $data['posts'], 2)
                : 0.0;

            $dailyData[] = [
                'day' => $day,
                'day_name' => $data['day_name'],
                'posts_count' => $data['posts'],
                'avg_engagements' => $avgEngagement,
            ];
        }

        // Find best hours (top 5)
        $bestHours = collect($hourlyData)
            ->filter(fn (array $data) => $data['posts_count'] >= 3)
            ->sortByDesc('avg_engagements')
            ->take(5)
            ->values()
            ->all();

        // Find best days
        $bestDays = collect($dailyData)
            ->filter(fn (array $data) => $data['posts_count'] >= 3)
            ->sortByDesc('avg_engagements')
            ->take(3)
            ->values()
            ->all();

        return $this->success(
            [
                'period' => [
                    'start' => $dateRange['start']->toDateString(),
                    'end' => $dateRange['end']->toDateString(),
                ],
                'platform' => $platform,
                'hourly_breakdown' => $hourlyData,
                'daily_breakdown' => $dailyData,
                'best_hours' => $bestHours,
                'best_days' => $bestDays,
                'recommendation' => $this->generateBestTimeRecommendation($bestHours, $bestDays),
            ],
            'Best posting times retrieved successfully'
        );
    }

    /**
     * Generate a human-readable recommendation for best posting times.
     *
     * @param array<int, array<string, mixed>> $bestHours
     * @param array<int, array<string, mixed>> $bestDays
     */
    private function generateBestTimeRecommendation(array $bestHours, array $bestDays): ?string
    {
        if (empty($bestHours) || empty($bestDays)) {
            return 'Not enough data to generate recommendations. Keep posting to improve accuracy.';
        }

        $topHour = $bestHours[0]['hour_label'] ?? '12:00';
        $topDay = $bestDays[0]['day_name'] ?? 'weekdays';

        return sprintf(
            'Based on your historical engagement, the best time to post is around %s on %s.',
            $topHour,
            $topDay
        );
    }
}
