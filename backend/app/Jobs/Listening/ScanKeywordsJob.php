<?php

declare(strict_types=1);

namespace App\Jobs\Listening;

use App\Models\Listening\MonitoredKeyword;
use App\Services\Listening\KeywordMonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ScanKeywordsJob implements ShouldQueue
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
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(KeywordMonitoringService $keywordMonitoringService): void
    {
        $keywords = MonitoredKeyword::active()->get();

        foreach ($keywords as $keyword) {
            try {
                // Placeholder for platform API scanning.
                // In a real implementation, this would call each platform's
                // search API to find mentions of the keyword and record them
                // using $keywordMonitoringService->recordMention().
                Log::info('Scanning keyword', [
                    'keyword_id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                    'platforms' => $keyword->platforms,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to scan keyword', [
                    'keyword_id' => $keyword->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
