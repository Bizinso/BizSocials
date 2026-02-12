<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\Content\PostStatus;
use App\Enums\Content\PostType;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ContentPerformanceService
 *
 * Analyzes content performance across posts and platforms.
 * Handles:
 * - Content performance overview and metrics
 * - Top performing posts identification
 * - Performance breakdown by content type
 * - Platform-specific performance analysis
 * - Best posting times optimization
 */
final class ContentPerformanceService extends BaseService
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
    ) {}

    /**
     * Get content performance overview for a workspace.
     *
     * Returns comprehensive metrics about published content including:
     * - Total posts and publishing statistics
     * - Engagement metrics and rates
     * - Content type distribution
     * - Platform distribution
     *
     * @param string $workspaceId The workspace UUID
     * @param string $period Period string (e.g., '7d', '30d', '90d')
     * @return array<string, mixed> Performance overview data
     */
    public function getPerformanceOverview(string $workspaceId, string $period = '30d'): array
    {
        $dateRange = $this->analyticsService->parsePeriod($period);
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        // Get published posts in the period
        $posts = Post::forWorkspace($workspaceId)
            ->published()
            ->whereBetween('published_at', [$start, $end])
            ->with(['targets', 'media'])
            ->get();

        // Calculate post statistics
        $totalPosts = $posts->count();
        $postsWithMedia = $posts->filter(fn ($post) => $post->media->isNotEmpty())->count();
        $postsWithLinks = $posts->filter(fn ($post) => $post->link_url !== null)->count();

        // Aggregate engagement metrics
        $totalMetrics = [
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

        foreach ($posts as $post) {
            foreach ($post->targets as $target) {
                $metrics = $target->metrics ?? [];
                $totalMetrics['impressions'] += $metrics['impressions'] ?? 0;
                $totalMetrics['reach'] += $metrics['reach'] ?? 0;
                $totalMetrics['likes'] += $metrics['likes'] ?? 0;
                $totalMetrics['comments'] += $metrics['comments'] ?? 0;
                $totalMetrics['shares'] += $metrics['shares'] ?? 0;
                $totalMetrics['saves'] += $metrics['saves'] ?? 0;
                $totalMetrics['clicks'] += $metrics['clicks'] ?? 0;
                $totalMetrics['video_views'] += $metrics['video_views'] ?? 0;
            }
        }

        $totalMetrics['engagements'] = $totalMetrics['likes'] + $totalMetrics['comments']
            + $totalMetrics['shares'] + $totalMetrics['saves'];

        // Calculate rates and averages
        $engagementRate = $totalMetrics['reach'] > 0
            ? round(($totalMetrics['engagements'] / $totalMetrics['reach']) * 100, 2)
            : 0.0;

        $avgEngagementsPerPost = $totalPosts > 0
            ? round($totalMetrics['engagements'] / $totalPosts, 2)
            : 0.0;

        $clickThroughRate = $totalMetrics['impressions'] > 0
            ? round(($totalMetrics['clicks'] / $totalMetrics['impressions']) * 100, 2)
            : 0.0;

        // Content type distribution
        $contentTypeDistribution = $posts->groupBy('post_type')
            ->map(fn ($group) => $group->count())
            ->toArray();

        // Platform distribution
        $platformDistribution = [];
        foreach ($posts as $post) {
            foreach ($post->targets as $target) {
                $platform = $target->platform_code;
                $platformDistribution[$platform] = ($platformDistribution[$platform] ?? 0) + 1;
            }
        }

        $this->log('Content performance overview retrieved', [
            'workspace_id' => $workspaceId,
            'period' => $period,
            'total_posts' => $totalPosts,
        ]);

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'posts' => [
                'total' => $totalPosts,
                'with_media' => $postsWithMedia,
                'with_links' => $postsWithLinks,
                'avg_posts_per_day' => $totalPosts > 0
                    ? round($totalPosts / max($start->diffInDays($end), 1), 2)
                    : 0.0,
            ],
            'metrics' => $totalMetrics,
            'rates' => [
                'engagement_rate' => $engagementRate,
                'click_through_rate' => $clickThroughRate,
                'avg_engagements_per_post' => $avgEngagementsPerPost,
            ],
            'distribution' => [
                'by_content_type' => $contentTypeDistribution,
                'by_platform' => $platformDistribution,
            ],
        ];
    }

    /**
     * Get top performing posts for a workspace.
     *
     * Returns posts sorted by engagement metrics.
     *
     * @param string $workspaceId The workspace UUID
     * @param int $limit Maximum number of posts to return
     * @param string $sortBy Metric to sort by ('engagement', 'impressions', 'reach', 'likes', 'comments', 'shares')
     * @return Collection<int, Post> Collection of top performing posts
     */
    public function getTopPosts(string $workspaceId, int $limit = 10, string $sortBy = 'engagement'): Collection
    {
        $posts = Post::forWorkspace($workspaceId)
            ->published()
            ->with(['targets', 'media', 'author'])
            ->get();

        // Calculate total engagement for each post
        $postsWithMetrics = $posts->map(function (Post $post) {
            $metrics = [
                'impressions' => 0,
                'reach' => 0,
                'likes' => 0,
                'comments' => 0,
                'shares' => 0,
                'saves' => 0,
                'clicks' => 0,
            ];

            foreach ($post->targets as $target) {
                $targetMetrics = $target->metrics ?? [];
                $metrics['impressions'] += $targetMetrics['impressions'] ?? 0;
                $metrics['reach'] += $targetMetrics['reach'] ?? 0;
                $metrics['likes'] += $targetMetrics['likes'] ?? 0;
                $metrics['comments'] += $targetMetrics['comments'] ?? 0;
                $metrics['shares'] += $targetMetrics['shares'] ?? 0;
                $metrics['saves'] += $targetMetrics['saves'] ?? 0;
                $metrics['clicks'] += $targetMetrics['clicks'] ?? 0;
            }

            $metrics['engagement'] = $metrics['likes'] + $metrics['comments']
                + $metrics['shares'] + $metrics['saves'];

            $post->calculated_metrics = $metrics;

            return $post;
        });

        // Sort by the specified metric
        $sortField = match ($sortBy) {
            'impressions' => 'impressions',
            'reach' => 'reach',
            'likes' => 'likes',
            'comments' => 'comments',
            'shares' => 'shares',
            default => 'engagement',
        };

        $sorted = $postsWithMetrics->sortByDesc(
            fn (Post $post) => $post->calculated_metrics[$sortField] ?? 0
        );

        $this->log('Top posts retrieved', [
            'workspace_id' => $workspaceId,
            'limit' => $limit,
            'sort_by' => $sortBy,
        ]);

        return $sorted->take($limit)->values();
    }

    /**
     * Get performance metrics grouped by content type.
     *
     * Returns metrics for each content type (text, image, video, etc.).
     *
     * @param string $workspaceId The workspace UUID
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return array<string, array<string, mixed>> Metrics grouped by content type
     */
    public function getPerformanceByContentType(string $workspaceId, Carbon $start, Carbon $end): array
    {
        $posts = Post::forWorkspace($workspaceId)
            ->published()
            ->whereBetween('published_at', [$start, $end])
            ->with('targets')
            ->get();

        $typeMetrics = [];

        foreach ($posts as $post) {
            $type = $post->post_type->value;

            if (!isset($typeMetrics[$type])) {
                $typeMetrics[$type] = [
                    'content_type' => $type,
                    'label' => $post->post_type->label(),
                    'posts_count' => 0,
                    'impressions' => 0,
                    'reach' => 0,
                    'engagements' => 0,
                    'likes' => 0,
                    'comments' => 0,
                    'shares' => 0,
                    'saves' => 0,
                    'clicks' => 0,
                ];
            }

            $typeMetrics[$type]['posts_count']++;

            foreach ($post->targets as $target) {
                $metrics = $target->metrics ?? [];
                $typeMetrics[$type]['impressions'] += $metrics['impressions'] ?? 0;
                $typeMetrics[$type]['reach'] += $metrics['reach'] ?? 0;
                $typeMetrics[$type]['likes'] += $metrics['likes'] ?? 0;
                $typeMetrics[$type]['comments'] += $metrics['comments'] ?? 0;
                $typeMetrics[$type]['shares'] += $metrics['shares'] ?? 0;
                $typeMetrics[$type]['saves'] += $metrics['saves'] ?? 0;
                $typeMetrics[$type]['clicks'] += $metrics['clicks'] ?? 0;
            }
        }

        // Calculate engagement totals and rates
        foreach ($typeMetrics as $type => $metrics) {
            $engagements = $metrics['likes'] + $metrics['comments']
                + $metrics['shares'] + $metrics['saves'];

            $typeMetrics[$type]['engagements'] = $engagements;
            $typeMetrics[$type]['engagement_rate'] = $metrics['reach'] > 0
                ? round(($engagements / $metrics['reach']) * 100, 2)
                : 0.0;
            $typeMetrics[$type]['avg_engagements_per_post'] = $metrics['posts_count'] > 0
                ? round($engagements / $metrics['posts_count'], 2)
                : 0.0;
        }

        return array_values($typeMetrics);
    }

    /**
     * Get performance metrics grouped by platform.
     *
     * Returns metrics for each social platform.
     *
     * @param string $workspaceId The workspace UUID
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return array<string, array<string, mixed>> Metrics grouped by platform
     */
    public function getPerformanceByPlatform(string $workspaceId, Carbon $start, Carbon $end): array
    {
        return $this->analyticsService->getMetricsByPlatform($workspaceId, $start, $end);
    }

    /**
     * Get best posting times based on historical engagement data.
     *
     * Analyzes past posts to determine optimal posting times by:
     * - Day of week
     * - Hour of day
     * - Platform-specific recommendations
     *
     * @param string $workspaceId The workspace UUID
     * @return array<string, mixed> Best posting times analysis
     */
    public function getBestPostingTimes(string $workspaceId): array
    {
        // Get posts from the last 90 days with engagement data
        $posts = Post::forWorkspace($workspaceId)
            ->published()
            ->where('published_at', '>=', now()->subDays(90))
            ->with('targets')
            ->get();

        // Initialize time analysis arrays
        $byDayOfWeek = [];
        $byHourOfDay = [];
        $byPlatform = [];

        foreach ($posts as $post) {
            if ($post->published_at === null) {
                continue;
            }

            $dayOfWeek = $post->published_at->dayOfWeek;
            $hourOfDay = $post->published_at->hour;

            // Calculate total engagement for this post
            $postEngagement = 0;
            foreach ($post->targets as $target) {
                $metrics = $target->metrics ?? [];
                $postEngagement += ($metrics['likes'] ?? 0) + ($metrics['comments'] ?? 0)
                    + ($metrics['shares'] ?? 0) + ($metrics['saves'] ?? 0);

                // Track by platform
                $platform = $target->platform_code;
                if (!isset($byPlatform[$platform])) {
                    $byPlatform[$platform] = [
                        'by_day' => array_fill(0, 7, ['posts' => 0, 'engagement' => 0]),
                        'by_hour' => array_fill(0, 24, ['posts' => 0, 'engagement' => 0]),
                    ];
                }

                $targetEngagement = ($metrics['likes'] ?? 0) + ($metrics['comments'] ?? 0)
                    + ($metrics['shares'] ?? 0) + ($metrics['saves'] ?? 0);

                $byPlatform[$platform]['by_day'][$dayOfWeek]['posts']++;
                $byPlatform[$platform]['by_day'][$dayOfWeek]['engagement'] += $targetEngagement;
                $byPlatform[$platform]['by_hour'][$hourOfDay]['posts']++;
                $byPlatform[$platform]['by_hour'][$hourOfDay]['engagement'] += $targetEngagement;
            }

            // Track overall by day and hour
            if (!isset($byDayOfWeek[$dayOfWeek])) {
                $byDayOfWeek[$dayOfWeek] = ['posts' => 0, 'engagement' => 0];
            }
            $byDayOfWeek[$dayOfWeek]['posts']++;
            $byDayOfWeek[$dayOfWeek]['engagement'] += $postEngagement;

            if (!isset($byHourOfDay[$hourOfDay])) {
                $byHourOfDay[$hourOfDay] = ['posts' => 0, 'engagement' => 0];
            }
            $byHourOfDay[$hourOfDay]['posts']++;
            $byHourOfDay[$hourOfDay]['engagement'] += $postEngagement;
        }

        // Calculate average engagement per post for each time slot
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $bestDays = [];
        foreach ($byDayOfWeek as $day => $data) {
            $avgEngagement = $data['posts'] > 0 ? round($data['engagement'] / $data['posts'], 2) : 0;
            $bestDays[] = [
                'day' => $day,
                'day_name' => $dayNames[$day],
                'posts_count' => $data['posts'],
                'total_engagement' => $data['engagement'],
                'avg_engagement' => $avgEngagement,
            ];
        }

        // Sort by average engagement
        usort($bestDays, fn ($a, $b) => $b['avg_engagement'] <=> $a['avg_engagement']);

        $bestHours = [];
        foreach ($byHourOfDay as $hour => $data) {
            $avgEngagement = $data['posts'] > 0 ? round($data['engagement'] / $data['posts'], 2) : 0;
            $bestHours[] = [
                'hour' => $hour,
                'hour_label' => sprintf('%02d:00', $hour),
                'posts_count' => $data['posts'],
                'total_engagement' => $data['engagement'],
                'avg_engagement' => $avgEngagement,
            ];
        }

        // Sort by average engagement
        usort($bestHours, fn ($a, $b) => $b['avg_engagement'] <=> $a['avg_engagement']);

        // Process platform-specific data
        $platformRecommendations = [];
        foreach ($byPlatform as $platform => $data) {
            $platformBestDays = [];
            foreach ($data['by_day'] as $day => $dayData) {
                if ($dayData['posts'] > 0) {
                    $platformBestDays[] = [
                        'day' => $day,
                        'day_name' => $dayNames[$day],
                        'avg_engagement' => round($dayData['engagement'] / $dayData['posts'], 2),
                    ];
                }
            }
            usort($platformBestDays, fn ($a, $b) => $b['avg_engagement'] <=> $a['avg_engagement']);

            $platformBestHours = [];
            foreach ($data['by_hour'] as $hour => $hourData) {
                if ($hourData['posts'] > 0) {
                    $platformBestHours[] = [
                        'hour' => $hour,
                        'hour_label' => sprintf('%02d:00', $hour),
                        'avg_engagement' => round($hourData['engagement'] / $hourData['posts'], 2),
                    ];
                }
            }
            usort($platformBestHours, fn ($a, $b) => $b['avg_engagement'] <=> $a['avg_engagement']);

            $platformRecommendations[$platform] = [
                'best_days' => array_slice($platformBestDays, 0, 3),
                'best_hours' => array_slice($platformBestHours, 0, 5),
            ];
        }

        $this->log('Best posting times analyzed', [
            'workspace_id' => $workspaceId,
            'posts_analyzed' => $posts->count(),
        ]);

        return [
            'posts_analyzed' => $posts->count(),
            'analysis_period' => '90 days',
            'overall' => [
                'best_days' => array_slice($bestDays, 0, 3),
                'best_hours' => array_slice($bestHours, 0, 5),
            ],
            'by_platform' => $platformRecommendations,
        ];
    }
}
