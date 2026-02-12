<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Content\Post;
use App\Services\BaseService;
use Carbon\Carbon;

/**
 * BestTimeService
 *
 * Analyzes historical post data to determine optimal posting times.
 * Generates heatmaps and recommended time slots based on engagement patterns.
 */
final class BestTimeService extends BaseService
{
    /**
     * Industry average best times (fallback when insufficient data).
     *
     * @var array<int, array<int, float>>
     */
    private const INDUSTRY_AVERAGES = [
        1 => [9 => 0.8, 12 => 0.7, 17 => 0.6], // Monday
        2 => [9 => 0.85, 13 => 0.75, 17 => 0.65], // Tuesday
        3 => [9 => 0.9, 11 => 0.8, 15 => 0.7], // Wednesday
        4 => [9 => 0.8, 12 => 0.75, 16 => 0.65], // Thursday
        5 => [9 => 0.7, 11 => 0.65, 14 => 0.6], // Friday
        6 => [10 => 0.5, 14 => 0.45], // Saturday
        0 => [10 => 0.45, 14 => 0.4], // Sunday
    ];

    /**
     * Analyze posting times and return a heatmap of engagement scores.
     *
     * Returns an array of {day: 0-6, hour: 0-23, score: float} entries.
     *
     * @return array<int, array{day: int, hour: int, score: float}>
     */
    public function analyze(string $workspaceId, ?string $platform = null): array
    {
        $posts = $this->getPublishedPosts($workspaceId, $platform);

        // Fall back to industry averages if insufficient data
        if ($posts->count() < 10) {
            return $this->getIndustryAverageHeatmap();
        }

        // Build engagement matrix: day x hour
        $matrix = [];
        for ($day = 0; $day < 7; $day++) {
            for ($hour = 0; $hour < 24; $hour++) {
                $matrix[$day][$hour] = ['total_engagement' => 0, 'count' => 0, 'recency_weight' => 0];
            }
        }

        $now = Carbon::now();

        foreach ($posts as $post) {
            if ($post->published_at === null) {
                continue;
            }

            $dayOfWeek = (int) $post->published_at->format('w'); // 0=Sunday
            $hour = (int) $post->published_at->format('G');

            // Calculate engagement from post targets
            $totalEngagement = 0;
            foreach ($post->targets as $target) {
                $metrics = $target->metrics ?? [];
                $totalEngagement += ($metrics['likes'] ?? 0)
                    + ($metrics['comments'] ?? 0)
                    + ($metrics['shares'] ?? 0);
            }

            // Apply recency weighting (more recent posts carry more weight)
            $daysAgo = $post->published_at->diffInDays($now);
            $recencyWeight = max(0.1, 1.0 - ($daysAgo / 365));

            $matrix[$dayOfWeek][$hour]['total_engagement'] += $totalEngagement * $recencyWeight;
            $matrix[$dayOfWeek][$hour]['count']++;
            $matrix[$dayOfWeek][$hour]['recency_weight'] += $recencyWeight;
        }

        // Calculate scores
        $heatmap = [];
        $maxScore = 0;

        for ($day = 0; $day < 7; $day++) {
            for ($hour = 0; $hour < 24; $hour++) {
                $entry = $matrix[$day][$hour];
                $score = $entry['count'] > 0
                    ? $entry['total_engagement'] / $entry['count']
                    : 0;
                $maxScore = max($maxScore, $score);
                $heatmap[] = [
                    'day' => $day,
                    'hour' => $hour,
                    'score' => $score,
                ];
            }
        }

        // Normalize scores to 0-1 range
        if ($maxScore > 0) {
            $heatmap = array_map(function (array $entry) use ($maxScore): array {
                $entry['score'] = round($entry['score'] / $maxScore, 4);

                return $entry;
            }, $heatmap);
        }

        $this->log('Best time analysis completed', [
            'workspace_id' => $workspaceId,
            'platform' => $platform,
            'posts_analyzed' => $posts->count(),
        ]);

        return $heatmap;
    }

    /**
     * Get top N recommended time slots.
     *
     * @return array<int, array{day: int, hour: int, score: float, day_name: string, hour_label: string}>
     */
    public function getRecommendedSlots(string $workspaceId, ?string $platform = null, int $count = 5): array
    {
        $heatmap = $this->analyze($workspaceId, $platform);

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        // Sort by score descending and take top N
        usort($heatmap, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        $topSlots = array_slice($heatmap, 0, $count);

        return array_map(function (array $slot) use ($dayNames): array {
            $slot['day_name'] = $dayNames[$slot['day']];
            $slot['hour_label'] = sprintf('%02d:00', $slot['hour']);

            return $slot;
        }, $topSlots);
    }

    /**
     * Get published posts with metrics for analysis.
     */
    private function getPublishedPosts(string $workspaceId, ?string $platform = null): \Illuminate\Support\Collection
    {
        $query = Post::forWorkspace($workspaceId)
            ->published()
            ->where('published_at', '>=', Carbon::now()->subMonths(6))
            ->with('targets');

        if ($platform !== null) {
            $query->whereHas('targets', function ($q) use ($platform): void {
                $q->where('platform_code', $platform);
            });
        }

        return $query->get();
    }

    /**
     * Generate industry average heatmap as fallback.
     *
     * @return array<int, array{day: int, hour: int, score: float}>
     */
    private function getIndustryAverageHeatmap(): array
    {
        $heatmap = [];

        for ($day = 0; $day < 7; $day++) {
            for ($hour = 0; $hour < 24; $hour++) {
                $score = self::INDUSTRY_AVERAGES[$day][$hour] ?? 0.0;
                $heatmap[] = [
                    'day' => $day,
                    'hour' => $hour,
                    'score' => $score,
                ];
            }
        }

        return $heatmap;
    }
}
