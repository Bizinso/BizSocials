<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use App\Models\Social\SocialAccount;
use App\Services\Analytics\AnalyticsDataCollector;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Collect Social Analytics Job
 *
 * Fetches analytics data from social platforms and stores in database.
 */
final class CollectSocialAnalyticsJob implements ShouldQueue
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
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $socialAccountId,
        public readonly ?string $date = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AnalyticsDataCollector $collector): void
    {
        $account = SocialAccount::find($this->socialAccountId);

        if ($account === null) {
            Log::warning('Social account not found for analytics collection', [
                'account_id' => $this->socialAccountId,
            ]);

            return;
        }

        if (!$account->isConnected()) {
            Log::warning('Social account not connected, skipping analytics collection', [
                'account_id' => $this->socialAccountId,
                'platform' => $account->platform->value,
            ]);

            return;
        }

        $date = $this->date !== null ? Carbon::parse($this->date) : now()->subDay();

        $success = $collector->collectDailyAnalytics($account, $date);

        if (!$success) {
            Log::error('Analytics collection job failed', [
                'account_id' => $this->socialAccountId,
                'date' => $date->toDateString(),
            ]);

            throw new \RuntimeException('Failed to collect analytics');
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Analytics collection job failed permanently', [
            'account_id' => $this->socialAccountId,
            'date' => $this->date,
            'error' => $exception->getMessage(),
        ]);
    }
}
