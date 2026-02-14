<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content\Post;
use App\Models\Workspace\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Calendar Service
 *
 * Provides calendar view functionality for content scheduling.
 * Retrieves posts from database filtered by date range and platform.
 */
class CalendarService
{
    /**
     * Get posts for calendar view filtered by date range and platform
     *
     * @param Workspace $workspace
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters Additional filters (platforms, status, etc.)
     * @return Collection
     */
    public function getCalendarPosts(
        Workspace $workspace,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): Collection {
        $query = Post::query()
            ->forWorkspace($workspace->id)
            ->with(['author', 'targets', 'media', 'category'])
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->orderBy('scheduled_at', 'asc');

        // Filter by platforms if specified
        if (!empty($filters['platforms'])) {
            $query->whereHas('targets', function ($q) use ($filters) {
                $q->whereIn('platform_code', $filters['platforms']);
            });
        }

        // Filter by status if specified
        if (!empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        // Filter by author if specified
        if (!empty($filters['author_id'])) {
            $query->where('created_by_user_id', $filters['author_id']);
        }

        // Filter by category if specified
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->get();
    }

    /**
     * Get posts grouped by date for calendar display
     *
     * @param Workspace $workspace
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters
     * @return array
     */
    public function getCalendarPostsByDate(
        Workspace $workspace,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): array {
        $posts = $this->getCalendarPosts($workspace, $startDate, $endDate, $filters);

        // Group posts by date
        $grouped = $posts->groupBy(function ($post) {
            return $post->scheduled_at?->format('Y-m-d');
        });

        return $grouped->toArray();
    }

    /**
     * Reschedule a post (drag-and-drop functionality)
     *
     * @param Post $post
     * @param Carbon $newScheduledAt
     * @param string|null $timezone
     * @return Post
     */
    public function reschedulePost(
        Post $post,
        Carbon $newScheduledAt,
        ?string $timezone = null
    ): Post {
        // Use the PostService reschedule method which handles all the logic
        $postService = app(PostService::class);
        return $postService->reschedule($post, $newScheduledAt, $timezone);
    }

    /**
     * Get calendar statistics for a date range
     *
     * @param Workspace $workspace
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getCalendarStats(
        Workspace $workspace,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $posts = $this->getCalendarPosts($workspace, $startDate, $endDate);

        return [
            'total_posts' => $posts->count(),
            'scheduled' => $posts->where('status', 'scheduled')->count(),
            'published' => $posts->where('status', 'published')->count(),
            'failed' => $posts->where('status', 'failed')->count(),
            'by_platform' => $this->getPostsByPlatform($posts),
            'by_date' => $this->getPostsByDate($posts),
        ];
    }

    /**
     * Get posts count by platform
     *
     * @param Collection $posts
     * @return array
     */
    private function getPostsByPlatform(Collection $posts): array
    {
        $platformCounts = [];

        foreach ($posts as $post) {
            foreach ($post->targets as $target) {
                $platform = $target->platform_code;
                if (!isset($platformCounts[$platform])) {
                    $platformCounts[$platform] = 0;
                }
                $platformCounts[$platform]++;
            }
        }

        return $platformCounts;
    }

    /**
     * Get posts count by date
     *
     * @param Collection $posts
     * @return array
     */
    private function getPostsByDate(Collection $posts): array
    {
        return $posts->groupBy(function ($post) {
            return $post->scheduled_at?->format('Y-m-d');
        })->map(function ($group) {
            return $group->count();
        })->toArray();
    }
}
