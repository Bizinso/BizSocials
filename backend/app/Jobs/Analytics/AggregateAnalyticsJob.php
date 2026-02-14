<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use App\Enums\Analytics\PeriodType;
use App\Models\Workspace\Workspace;
use App\Services\Analytics\AnalyticsAggregationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Aggregate Analytics Job
 *
 * Aggregates daily analytics into weekly and monthly summaries.
 */
final class AggregateAnalyticsJob implements ShouldQueue
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
        public readonly string $workspaceId,
        public readonly ?string $date = null
    ) {
        $this->onQueue('analytics');
    }

    /**
     * Execute the job.
     */
    public function handle(AnalyticsAggregationService $aggregationService): void
    {
        $workspace = Workspace::find($this->workspaceId);

        if ($workspace === null) {
            Log::warning('Workspace not found for analytics aggregation', [
                'workspace_id' => $this->workspaceId,
            ]);

            return;
        }

        $date = $this->date !== null ? Carbon::parse($this->date) : now()->subDay();

        $results = $aggregationService->aggregateAllPeriods($workspace, $date);

        Log::info('Analytics aggregation completed', [
            'workspace_id' => $this->workspaceId,
            'date' => $date->toDateString(),
            'results' => $results,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Analytics aggregation job failed permanently', [
            'workspace_id' => $this->workspaceId,
            'date' => $this->date,
            'error' => $exception->getMessage(),
        ]);
    }
}
