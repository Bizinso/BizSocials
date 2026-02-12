<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use App\Enums\Analytics\ReportStatus;
use App\Models\Analytics\AnalyticsReport;
use App\Models\Analytics\UserActivityLog;
use App\Models\Inbox\PostMetricSnapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * CleanupAnalyticsDataJob
 *
 * Cleans up old analytics data to manage database size and storage.
 * Runs on a scheduled basis to remove stale data.
 *
 * Features:
 * - Cleans up old activity logs (older than 90 days)
 * - Cleans up old metric snapshots that have been aggregated
 * - Cleans up expired reports and their files
 * - Uses chunked deletion for performance
 */
final class CleanupAnalyticsDataJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 1800;

    /**
     * Days to retain activity logs.
     */
    private int $activityLogRetentionDays;

    /**
     * Days to retain metric snapshots.
     */
    private int $metricSnapshotRetentionDays;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $activityLogRetentionDays = 90,
        int $metricSnapshotRetentionDays = 30,
    ) {
        $this->onQueue('maintenance');
        $this->activityLogRetentionDays = $activityLogRetentionDays;
        $this->metricSnapshotRetentionDays = $metricSnapshotRetentionDays;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'cleanup-analytics-data';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[CleanupAnalyticsDataJob] Starting analytics data cleanup', [
            'activity_log_retention_days' => $this->activityLogRetentionDays,
            'metric_snapshot_retention_days' => $this->metricSnapshotRetentionDays,
        ]);

        $deletedActivityLogs = $this->cleanupActivityLogs();
        $deletedMetricSnapshots = $this->cleanupMetricSnapshots();
        $deletedReports = $this->cleanupExpiredReports();

        Log::info('[CleanupAnalyticsDataJob] Cleanup completed', [
            'deleted_activity_logs' => $deletedActivityLogs,
            'deleted_metric_snapshots' => $deletedMetricSnapshots,
            'deleted_reports' => $deletedReports,
        ]);
    }

    /**
     * Clean up old activity logs.
     */
    private function cleanupActivityLogs(): int
    {
        $cutoff = now()->subDays($this->activityLogRetentionDays);

        return $this->deleteInChunks(
            UserActivityLog::query()->where('created_at', '<', $cutoff)
        );
    }

    /**
     * Clean up old metric snapshots that have been aggregated.
     */
    private function cleanupMetricSnapshots(): int
    {
        $cutoff = now()->subDays($this->metricSnapshotRetentionDays);

        return $this->deleteInChunks(
            PostMetricSnapshot::query()->where('captured_at', '<', $cutoff)
        );
    }

    /**
     * Clean up expired reports and their files.
     */
    private function cleanupExpiredReports(): int
    {
        $expiredReports = AnalyticsReport::query()
            ->where('status', ReportStatus::COMPLETED)
            ->where('expires_at', '<', now())
            ->get();

        $deletedCount = 0;

        foreach ($expiredReports as $report) {
            // Delete the file if it exists
            if ($report->file_path !== null && Storage::disk('local')->exists($report->file_path)) {
                Storage::disk('local')->delete($report->file_path);
            }

            // Mark as expired
            $report->update([
                'status' => ReportStatus::EXPIRED,
                'file_path' => null,
                'file_size_bytes' => null,
            ]);

            $deletedCount++;
        }

        // Also delete very old failed reports (older than 30 days)
        $oldFailedCutoff = now()->subDays(30);
        $deletedFailed = AnalyticsReport::query()
            ->where('status', ReportStatus::FAILED)
            ->where('created_at', '<', $oldFailedCutoff)
            ->delete();

        return $deletedCount + $deletedFailed;
    }

    /**
     * Delete records in chunks to avoid memory issues.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function deleteInChunks($query): int
    {
        $totalDeleted = 0;
        $chunkSize = 1000;

        do {
            $deleted = $query->clone()
                ->limit($chunkSize)
                ->delete();

            $totalDeleted += $deleted;

            // Log progress for large deletions
            if ($totalDeleted > 0 && $totalDeleted % 10000 === 0) {
                Log::debug('[CleanupAnalyticsDataJob] Deletion progress', [
                    'deleted_so_far' => $totalDeleted,
                ]);
            }
        } while ($deleted === $chunkSize);

        return $totalDeleted;
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('[CleanupAnalyticsDataJob] Job failed', [
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);
    }
}
