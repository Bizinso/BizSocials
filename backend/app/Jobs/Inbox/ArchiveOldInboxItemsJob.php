<?php

declare(strict_types=1);

namespace App\Jobs\Inbox;

use App\Enums\Inbox\InboxItemStatus;
use App\Models\Inbox\InboxItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ArchiveOldInboxItemsJob
 *
 * Runs weekly to archive old inbox items. This helps keep the active
 * inbox manageable while preserving historical data.
 *
 * Features:
 * - Archives resolved inbox items older than 90 days
 * - Archives read (but not resolved) items older than 180 days
 * - Uses chunked processing for performance
 * - Logs archival statistics per workspace
 */
final class ArchiveOldInboxItemsJob implements ShouldQueue
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
    public int $timeout = 900;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $backoff = 120;

    /**
     * Number of days after which resolved items are archived.
     */
    private int $resolvedRetentionDays;

    /**
     * Number of days after which read items are archived.
     */
    private int $readRetentionDays;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $resolvedRetentionDays = 90,
        int $readRetentionDays = 180,
    ) {
        $this->onQueue('maintenance');
        $this->resolvedRetentionDays = $resolvedRetentionDays;
        $this->readRetentionDays = $readRetentionDays;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[ArchiveOldInboxItemsJob] Starting inbox archival', [
            'resolved_retention_days' => $this->resolvedRetentionDays,
            'read_retention_days' => $this->readRetentionDays,
        ]);

        $resolvedCutoff = now()->subDays($this->resolvedRetentionDays);
        $readCutoff = now()->subDays($this->readRetentionDays);

        $archivedResolvedCount = 0;
        $archivedReadCount = 0;

        // Archive old resolved items
        $archivedResolvedCount = $this->archiveItems(
            InboxItem::query()
                ->where('status', InboxItemStatus::RESOLVED)
                ->where('resolved_at', '<', $resolvedCutoff)
        );

        // Archive very old read items
        $archivedReadCount = $this->archiveItems(
            InboxItem::query()
                ->where('status', InboxItemStatus::READ)
                ->where('created_at', '<', $readCutoff)
        );

        $totalArchived = $archivedResolvedCount + $archivedReadCount;

        Log::info('[ArchiveOldInboxItemsJob] Archival completed', [
            'archived_resolved' => $archivedResolvedCount,
            'archived_read' => $archivedReadCount,
            'total_archived' => $totalArchived,
        ]);

        // Log per-workspace statistics
        $this->logWorkspaceStatistics();
    }

    /**
     * Archive items matching the query in chunks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<InboxItem>  $query
     */
    private function archiveItems($query): int
    {
        $totalArchived = 0;
        $chunkSize = 500;

        $query->chunkById($chunkSize, function ($items) use (&$totalArchived) {
            $ids = $items->pluck('id')->toArray();

            $updated = InboxItem::query()
                ->whereIn('id', $ids)
                ->update([
                    'status' => InboxItemStatus::ARCHIVED,
                    'updated_at' => now(),
                ]);

            $totalArchived += $updated;

            Log::debug('[ArchiveOldInboxItemsJob] Archived batch', [
                'batch_size' => count($ids),
                'updated' => $updated,
            ]);
        });

        return $totalArchived;
    }

    /**
     * Log statistics about inbox items per workspace.
     */
    private function logWorkspaceStatistics(): void
    {
        $statistics = DB::table('inbox_items')
            ->select([
                'workspace_id',
                DB::raw('COUNT(*) as total_items'),
                DB::raw("SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) as unread_count"),
                DB::raw("SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count"),
                DB::raw("SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count"),
                DB::raw("SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_count"),
            ])
            ->groupBy('workspace_id')
            ->get();

        foreach ($statistics as $stat) {
            Log::debug('[ArchiveOldInboxItemsJob] Workspace inbox statistics', [
                'workspace_id' => $stat->workspace_id,
                'total' => $stat->total_items,
                'unread' => $stat->unread_count,
                'read' => $stat->read_count,
                'resolved' => $stat->resolved_count,
                'archived' => $stat->archived_count,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('[ArchiveOldInboxItemsJob] Job failed', [
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);
    }
}
