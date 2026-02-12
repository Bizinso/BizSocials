<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use App\Enums\Content\PostStatus;
use App\Enums\Social\SocialAccountStatus;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Services\Social\SocialPlatformAdapterFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FetchPostMetricsJob
 *
 * Fetches engagement metrics for all published posts in a workspace.
 * This job queries social platform APIs to get current metrics like
 * likes, comments, shares, and impressions, then stores snapshots
 * in the post_metric_snapshots table for analytics.
 *
 * Features:
 * - Fetches metrics for all published posts in the workspace
 * - Creates PostMetricSnapshot records for historical tracking
 * - Handles platform API rate limits gracefully
 * - Skips posts with disconnected social accounts
 */
final class FetchPostMetricsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 600;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [60, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $workspaceId,
    ) {
        $this->onQueue('analytics');
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "fetch-metrics-{$this->workspaceId}";
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[FetchPostMetricsJob] Starting metrics fetch', [
            'workspace_id' => $this->workspaceId,
        ]);

        // Get all published posts with their targets
        $posts = Post::query()
            ->where('workspace_id', $this->workspaceId)
            ->where('status', PostStatus::PUBLISHED)
            ->whereNotNull('published_at')
            ->with(['targets.socialAccount'])
            ->get();

        if ($posts->isEmpty()) {
            Log::debug('[FetchPostMetricsJob] No published posts found', [
                'workspace_id' => $this->workspaceId,
            ]);
            return;
        }

        $successCount = 0;
        $failCount = 0;
        $skippedCount = 0;

        foreach ($posts as $post) {
            foreach ($post->targets as $target) {
                try {
                    $result = $this->fetchTargetMetrics($target);

                    if ($result === null) {
                        $skippedCount++;
                        continue;
                    }

                    $this->saveMetricSnapshot($target, $result);
                    $successCount++;
                } catch (\Throwable $e) {
                    $failCount++;
                    Log::error('[FetchPostMetricsJob] Failed to fetch metrics for target', [
                        'target_id' => $target->id,
                        'post_id' => $post->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::info('[FetchPostMetricsJob] Completed metrics fetch', [
            'workspace_id' => $this->workspaceId,
            'posts_processed' => $posts->count(),
            'success' => $successCount,
            'failed' => $failCount,
            'skipped' => $skippedCount,
        ]);
    }

    /**
     * Fetch metrics for a specific post target.
     *
     * @return array<string, int>|null
     */
    private function fetchTargetMetrics(PostTarget $target): ?array
    {
        // Check if social account is connected
        $socialAccount = $target->socialAccount;

        if ($socialAccount === null) {
            Log::debug('[FetchPostMetricsJob] Social account not found', [
                'target_id' => $target->id,
            ]);
            return null;
        }

        if ($socialAccount->status !== SocialAccountStatus::CONNECTED) {
            Log::debug('[FetchPostMetricsJob] Social account not connected', [
                'target_id' => $target->id,
                'account_status' => $socialAccount->status->value,
            ]);
            return null;
        }

        // Check if target has a platform post ID
        if ($target->external_post_id === null) {
            Log::debug('[FetchPostMetricsJob] Target has no platform post ID', [
                'target_id' => $target->id,
            ]);
            return null;
        }

        // Fetch metrics from the platform
        // NOTE: This is a stub implementation. Actual platform integration
        // will be implemented using platform-specific API adapters.
        return $this->fetchFromPlatform($socialAccount, $target->external_post_id);
    }

    /**
     * Fetch metrics from the social platform API.
     *
     * @return array<string, int>
     */
    private function fetchFromPlatform(SocialAccount $account, string $platformPostId): array
    {
        Log::debug('[FetchPostMetricsJob] Fetching metrics from platform', [
            'account_id' => $account->id,
            'platform' => $account->platform->value,
            'platform_post_id' => $platformPostId,
        ]);

        $factory = app(SocialPlatformAdapterFactory::class);
        $adapter = $factory->create($account->platform);

        return $adapter->fetchPostMetrics($account, $platformPostId);
    }

    /**
     * Save a metric snapshot to the database.
     *
     * @param  array<string, int>  $metrics
     */
    private function saveMetricSnapshot(PostTarget $target, array $metrics): void
    {
        DB::table('post_metric_snapshots')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'post_target_id' => $target->id,
            'captured_at' => now(),
            'impressions_count' => $metrics['impressions'] ?? 0,
            'reach_count' => $metrics['reach'] ?? 0,
            'likes_count' => $metrics['likes'] ?? 0,
            'comments_count' => $metrics['comments'] ?? 0,
            'shares_count' => $metrics['shares'] ?? 0,
            'clicks_count' => $metrics['clicks'] ?? 0,
            'engagement_rate' => null, // Will be calculated after
            'raw_response' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::debug('[FetchPostMetricsJob] Saved metric snapshot', [
            'target_id' => $target->id,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('[FetchPostMetricsJob] Job failed', [
            'workspace_id' => $this->workspaceId,
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);
    }
}
