<?php

declare(strict_types=1);

namespace App\Jobs\Notification;

use App\Models\Notification\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CleanupOldNotificationsJob
 *
 * Runs daily to clean up old notifications from the database.
 * Deletes notifications that are older than 90 days to prevent
 * the notifications table from growing unbounded.
 *
 * Features:
 * - Runs as a scheduled job (daily)
 * - Deletes read notifications older than 90 days
 * - Keeps unread notifications longer (configurable)
 * - Uses chunked deletion for performance
 * - Logs cleanup statistics
 */
final class CleanupOldNotificationsJob implements ShouldQueue
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
     * @var int
     */
    public int $backoff = 60;

    /**
     * The number of days after which read notifications are deleted.
     */
    private int $readNotificationRetentionDays;

    /**
     * The number of days after which unread notifications are deleted.
     */
    private int $unreadNotificationRetentionDays;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $readRetentionDays = 90,
        int $unreadRetentionDays = 180,
    ) {
        $this->onQueue('maintenance');
        $this->readNotificationRetentionDays = $readRetentionDays;
        $this->unreadNotificationRetentionDays = $unreadRetentionDays;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[CleanupOldNotificationsJob] Starting notification cleanup', [
            'read_retention_days' => $this->readNotificationRetentionDays,
            'unread_retention_days' => $this->unreadNotificationRetentionDays,
        ]);

        $readCutoff = now()->subDays($this->readNotificationRetentionDays);
        $unreadCutoff = now()->subDays($this->unreadNotificationRetentionDays);

        $deletedReadCount = 0;
        $deletedUnreadCount = 0;

        // Delete old read notifications in chunks
        $deletedReadCount = $this->deleteInChunks(
            Notification::query()
                ->whereNotNull('read_at')
                ->where('created_at', '<', $readCutoff)
        );

        // Delete very old unread notifications in chunks
        $deletedUnreadCount = $this->deleteInChunks(
            Notification::query()
                ->whereNull('read_at')
                ->where('created_at', '<', $unreadCutoff)
        );

        // Also delete failed notifications older than 30 days
        $deletedFailedCount = $this->deleteInChunks(
            Notification::query()
                ->whereNotNull('failed_at')
                ->where('created_at', '<', now()->subDays(30))
        );

        $totalDeleted = $deletedReadCount + $deletedUnreadCount + $deletedFailedCount;

        Log::info('[CleanupOldNotificationsJob] Cleanup completed', [
            'deleted_read' => $deletedReadCount,
            'deleted_unread' => $deletedUnreadCount,
            'deleted_failed' => $deletedFailedCount,
            'total_deleted' => $totalDeleted,
        ]);
    }

    /**
     * Delete records in chunks to avoid memory issues.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Notification>  $query
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
                Log::debug('[CleanupOldNotificationsJob] Deletion progress', [
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
        Log::error('[CleanupOldNotificationsJob] Job failed', [
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);
    }
}
