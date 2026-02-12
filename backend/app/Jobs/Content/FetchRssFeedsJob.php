<?php

declare(strict_types=1);

namespace App\Jobs\Content;

use App\Models\Content\RssFeed;
use App\Services\Content\RssFeedService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class FetchRssFeedsJob implements ShouldQueue
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
    public $tries = 1;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 300;

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
    public function handle(RssFeedService $rssFeedService): void
    {
        $feeds = RssFeed::active()
            ->needsFetch()
            ->get();

        foreach ($feeds as $feed) {
            try {
                $rssFeedService->fetchItems($feed);
            } catch (\Exception $e) {
                // Log error but continue with other feeds
                \Log::error('Failed to fetch RSS feed', [
                    'feed_id' => $feed->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
