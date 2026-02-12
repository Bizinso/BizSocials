<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use App\Models\Content\Post;
use App\Models\Workspace\Workspace;
use App\Services\Analytics\HashtagTrackingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * TrackHashtagPerformanceJob
 *
 * Analyzes recently published posts, extracts hashtags, and updates
 * performance metrics in the hashtag_performance table.
 * Scheduled to run daily.
 */
final class TrackHashtagPerformanceJob implements ShouldQueue, ShouldBeUnique
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
    public int $timeout = 600;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 120;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('analytics');
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'track-hashtags:' . Carbon::today()->format('Y-m-d');
    }

    /**
     * Execute the job.
     */
    public function handle(HashtagTrackingService $hashtagService): void
    {
        Log::info('[TrackHashtagPerformanceJob] Starting hashtag tracking');

        $yesterday = Carbon::yesterday();
        $today = Carbon::today();

        // Get all workspaces with recently published posts
        $workspaces = Workspace::whereHas('posts', function ($query) use ($yesterday, $today): void {
            $query->published()
                ->whereBetween('published_at', [$yesterday->startOfDay(), $today->endOfDay()]);
        })->get();

        $totalHashtags = 0;

        foreach ($workspaces as $workspace) {
            $posts = Post::forWorkspace($workspace->id)
                ->published()
                ->whereBetween('published_at', [$yesterday->startOfDay(), $today->endOfDay()])
                ->with('targets')
                ->get();

            foreach ($posts as $post) {
                $hashtags = $this->extractHashtags($post->content ?? '');

                if (empty($hashtags)) {
                    continue;
                }

                foreach ($post->targets as $target) {
                    $metrics = $target->metrics ?? [];
                    $engagement = ($metrics['likes'] ?? 0)
                        + ($metrics['comments'] ?? 0)
                        + ($metrics['shares'] ?? 0);

                    $platformCode = $target->platform_code ?? 'unknown';

                    foreach ($hashtags as $hashtag) {
                        try {
                            $hashtagService->trackUsage($workspace->id, $hashtag, $platformCode, [
                                'reach' => $metrics['reach'] ?? 0,
                                'engagement' => $engagement,
                                'impressions' => $metrics['impressions'] ?? 0,
                            ]);
                            $totalHashtags++;
                        } catch (\Throwable $e) {
                            Log::warning('[TrackHashtagPerformanceJob] Failed to track hashtag', [
                                'hashtag' => $hashtag,
                                'workspace_id' => $workspace->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        }

        Log::info('[TrackHashtagPerformanceJob] Hashtag tracking completed', [
            'workspaces_processed' => $workspaces->count(),
            'total_hashtags_tracked' => $totalHashtags,
        ]);
    }

    /**
     * Extract hashtags from post content.
     *
     * @return array<int, string>
     */
    private function extractHashtags(string $content): array
    {
        preg_match_all('/#[\w\p{L}]+/u', $content, $matches);

        return array_unique($matches[0] ?? []);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('[TrackHashtagPerformanceJob] Job failed', [
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);
    }
}
