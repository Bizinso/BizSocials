<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Privacy\ProcessDataExportJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcessPendingExportsCommand
 *
 * Artisan command that processes pending data export requests.
 * This command is designed to be run by the scheduler daily to
 * handle GDPR/CCPA data export requests from users.
 *
 * Usage:
 *   php artisan privacy:process-exports
 *   php artisan privacy:process-exports --request=uuid
 *   php artisan privacy:process-exports --dry-run
 *
 * Features:
 * - Finds all pending export requests
 * - Dispatches ProcessDataExportJob for each request
 * - Supports dry-run mode for testing
 * - Optionally process a single request
 * - Logs job dispatch for compliance tracking
 */
final class ProcessPendingExportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'privacy:process-exports
                            {--request= : Process a specific export request by UUID}
                            {--dry-run : Show what would be processed without dispatching jobs}
                            {--limit=100 : Maximum number of requests to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending data export requests for GDPR/CCPA compliance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $requestId = $this->option('request');
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        if ($requestId !== null) {
            return $this->processSingleRequest($requestId, $dryRun);
        }

        return $this->processAllPendingRequests($dryRun, $limit);
    }

    /**
     * Process a single export request.
     */
    private function processSingleRequest(string $requestId, bool $dryRun): int
    {
        $request = DB::table('data_export_requests')
            ->where('id', $requestId)
            ->first();

        if ($request === null) {
            $this->error("Export request not found: {$requestId}");

            return self::FAILURE;
        }

        if ($request->status !== 'pending') {
            $this->warn("Export request is not pending (status: {$request->status})");

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info('[DRY RUN] Would process export request:');
            $this->displayRequestInfo($request);

            return self::SUCCESS;
        }

        $this->info('Processing export request...');
        $this->displayRequestInfo($request);

        ProcessDataExportJob::dispatch($request->id);

        Log::info('[ProcessPendingExportsCommand] Dispatched single export job', [
            'request_id' => $request->id,
            'user_id' => $request->user_id,
        ]);

        $this->info('Export job dispatched successfully.');

        return self::SUCCESS;
    }

    /**
     * Process all pending export requests.
     */
    private function processAllPendingRequests(bool $dryRun, int $limit): int
    {
        $this->info('Finding pending data export requests...');

        $pendingRequests = DB::table('data_export_requests')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        if ($pendingRequests->isEmpty()) {
            $this->info('No pending export requests found.');
            Log::debug('[ProcessPendingExportsCommand] No pending export requests');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info('[DRY RUN] Would process the following export requests:');
            $this->newLine();
        }

        $count = 0;

        foreach ($pendingRequests as $request) {
            if ($dryRun) {
                $this->displayRequestInfo($request);
                $this->newLine();
            } else {
                ProcessDataExportJob::dispatch($request->id);

                Log::info('[ProcessPendingExportsCommand] Dispatched export job', [
                    'request_id' => $request->id,
                    'user_id' => $request->user_id,
                ]);

                $this->line("  - Dispatched export for request: {$request->id}");
            }

            $count++;
        }

        if (! $dryRun) {
            Log::info('[ProcessPendingExportsCommand] Dispatched export jobs', [
                'request_count' => $count,
            ]);
        }

        $this->newLine();

        if ($dryRun) {
            $this->info("[DRY RUN] Would process {$count} export requests.");
        } else {
            $this->info("Dispatched {$count} export jobs.");
        }

        return self::SUCCESS;
    }

    /**
     * Display information about an export request.
     */
    private function displayRequestInfo(object $request): void
    {
        $this->table(
            ['Field', 'Value'],
            [
                ['Request ID', $request->id],
                ['User ID', $request->user_id],
                ['Status', $request->status],
                ['Format', $request->format ?? 'json'],
                ['Created At', $request->created_at],
            ]
        );
    }
}
