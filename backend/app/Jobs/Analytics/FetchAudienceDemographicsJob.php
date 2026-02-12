<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use App\Models\Social\SocialAccount;
use App\Services\Analytics\AudienceDemographicsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * FetchAudienceDemographicsJob
 *
 * Fetches audience demographics from platform APIs for all active social accounts.
 * Scheduled to run daily. Creates demographic snapshots for tracking over time.
 */
final class FetchAudienceDemographicsJob implements ShouldQueue, ShouldBeUnique
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
        return 'fetch-demographics:' . Carbon::today()->format('Y-m-d');
    }

    /**
     * Execute the job.
     */
    public function handle(AudienceDemographicsService $demographicsService): void
    {
        Log::info('[FetchAudienceDemographicsJob] Starting demographics fetch');

        $socialAccounts = SocialAccount::where('status', 'active')->get();
        $processedCount = 0;
        $errorCount = 0;

        foreach ($socialAccounts as $account) {
            try {
                // In production, this would call platform-specific APIs.
                // For now, we create a snapshot with available data.
                $demographicsService->snapshot($account->id, [
                    'snapshot_date' => Carbon::today()->toDateString(),
                    'follower_count' => $account->follower_count ?? 0,
                    'age_ranges' => $account->metrics['demographics']['age_ranges'] ?? null,
                    'gender_split' => $account->metrics['demographics']['gender_split'] ?? null,
                    'top_countries' => $account->metrics['demographics']['top_countries'] ?? null,
                    'top_cities' => $account->metrics['demographics']['top_cities'] ?? null,
                ]);

                $processedCount++;
            } catch (\Throwable $e) {
                $errorCount++;
                Log::warning('[FetchAudienceDemographicsJob] Failed to fetch demographics', [
                    'social_account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[FetchAudienceDemographicsJob] Demographics fetch completed', [
            'processed' => $processedCount,
            'errors' => $errorCount,
            'total_accounts' => $socialAccounts->count(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('[FetchAudienceDemographicsJob] Job failed', [
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);
    }
}
