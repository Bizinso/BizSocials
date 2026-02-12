<?php

declare(strict_types=1);

namespace App\Jobs\Content;

use App\Services\Content\PublishingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * PublishScheduledPostsJob
 *
 * Runs every minute via the scheduler to find posts that are scheduled
 * and ready to be published. For each post found, it dispatches a
 * PublishPostJob to handle the actual publishing process.
 *
 * This job acts as a coordinator and should be lightweight, delegating
 * the actual publishing work to individual PublishPostJob instances.
 */
final class PublishScheduledPostsJob implements ShouldQueue
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
    public int $timeout = 120;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('content');
    }

    /**
     * Execute the job.
     */
    public function handle(PublishingService $publishingService): void
    {
        Log::info('[PublishScheduledPostsJob] Starting scheduled posts check');

        $publishingService->publishScheduled();

        Log::info('[PublishScheduledPostsJob] Completed scheduled posts check');
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('[PublishScheduledPostsJob] Job failed', [
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);
    }
}
