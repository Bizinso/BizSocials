<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Privacy\ProcessDataDeletionJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcessPendingDeletionsCommand
 *
 * Artisan command that processes approved data deletion requests that have
 * passed their grace period. This command is designed to be run by the
 * scheduler daily to handle GDPR/CCPA data deletion requests.
 *
 * Usage:
 *   php artisan privacy:process-deletions
 *   php artisan privacy:process-deletions --request=uuid
 *   php artisan privacy:process-deletions --dry-run
 *
 * Features:
 * - Finds approved deletion requests past grace period
 * - Checks for cancellation before processing
 * - Dispatches ProcessDataDeletionJob for eligible requests
 * - Supports dry-run mode for testing
 * - Logs job dispatch for compliance tracking
 *
 * Grace Period:
 * - Default grace period is 14 days after approval
 * - During grace period, users can cancel the deletion
 * - After grace period, deletion proceeds automatically
 */
final class ProcessPendingDeletionsCommand extends Command
{
    /**
     * Default grace period in days after approval before deletion proceeds.
     */
    private const DEFAULT_GRACE_PERIOD_DAYS = 14;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'privacy:process-deletions
                            {--request= : Process a specific deletion request by UUID}
                            {--dry-run : Show what would be processed without dispatching jobs}
                            {--force : Process immediately, ignoring grace period}
                            {--grace-period=14 : Grace period in days after approval}
                            {--limit=50 : Maximum number of requests to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process approved data deletion requests that have passed the grace period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $requestId = $this->option('request');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $gracePeriodDays = (int) $this->option('grace-period');
        $limit = (int) $this->option('limit');

        if ($requestId !== null) {
            return $this->processSingleRequest($requestId, $dryRun, $force, $gracePeriodDays);
        }

        return $this->processAllEligibleRequests($dryRun, $force, $gracePeriodDays, $limit);
    }

    /**
     * Process a single deletion request.
     */
    private function processSingleRequest(
        string $requestId,
        bool $dryRun,
        bool $force,
        int $gracePeriodDays,
    ): int {
        $request = DB::table('data_deletion_requests')
            ->where('id', $requestId)
            ->first();

        if ($request === null) {
            $this->error("Deletion request not found: {$requestId}");

            return self::FAILURE;
        }

        // Check status
        if ($request->status !== 'approved') {
            $this->warn("Deletion request is not approved (status: {$request->status})");

            return self::FAILURE;
        }

        // Check if cancelled
        if ($request->cancelled_at !== null) {
            $this->warn('Deletion request has been cancelled by the user.');

            return self::FAILURE;
        }

        // Check grace period (unless forced)
        $approvedAt = new \DateTimeImmutable($request->approved_at);
        $gracePeriodEnd = $approvedAt->modify("+{$gracePeriodDays} days");
        $now = new \DateTimeImmutable;

        if (! $force && $now < $gracePeriodEnd) {
            $remainingDays = $now->diff($gracePeriodEnd)->days;
            $this->warn("Grace period has not ended. {$remainingDays} days remaining.");
            $this->info('Use --force to process immediately.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info('[DRY RUN] Would process deletion request:');
            $this->displayRequestInfo($request, $gracePeriodDays);

            return self::SUCCESS;
        }

        if ($force && $now < $gracePeriodEnd) {
            $this->warn('WARNING: Forcing deletion before grace period ends!');

            if (! $this->confirm('Are you sure you want to proceed?')) {
                $this->info('Aborted.');

                return self::SUCCESS;
            }
        }

        $this->info('Processing deletion request...');
        $this->displayRequestInfo($request, $gracePeriodDays);

        ProcessDataDeletionJob::dispatch($request->id);

        Log::info('[ProcessPendingDeletionsCommand] Dispatched single deletion job', [
            'request_id' => $request->id,
            'user_id' => $request->user_id,
            'forced' => $force,
        ]);

        $this->info('Deletion job dispatched successfully.');

        return self::SUCCESS;
    }

    /**
     * Process all eligible deletion requests.
     */
    private function processAllEligibleRequests(
        bool $dryRun,
        bool $force,
        int $gracePeriodDays,
        int $limit,
    ): int {
        $this->info('Finding approved deletion requests past grace period...');

        $gracePeriodCutoff = now()->subDays($gracePeriodDays);

        $query = DB::table('data_deletion_requests')
            ->where('status', 'approved')
            ->whereNull('cancelled_at');

        // Only apply grace period filter if not forced
        if (! $force) {
            $query->where('approved_at', '<=', $gracePeriodCutoff);
        }

        $eligibleRequests = $query
            ->orderBy('approved_at', 'asc')
            ->limit($limit)
            ->get();

        if ($eligibleRequests->isEmpty()) {
            $this->info('No eligible deletion requests found.');
            Log::debug('[ProcessPendingDeletionsCommand] No eligible deletion requests');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info('[DRY RUN] Would process the following deletion requests:');
            $this->newLine();
        }

        if ($force && ! $dryRun) {
            $this->warn('WARNING: Processing deletions with --force flag!');

            if (! $this->confirm('Are you sure you want to proceed with all deletions?')) {
                $this->info('Aborted.');

                return self::SUCCESS;
            }
        }

        $count = 0;

        foreach ($eligibleRequests as $request) {
            if ($dryRun) {
                $this->displayRequestInfo($request, $gracePeriodDays);
                $this->newLine();
            } else {
                ProcessDataDeletionJob::dispatch($request->id);

                Log::info('[ProcessPendingDeletionsCommand] Dispatched deletion job', [
                    'request_id' => $request->id,
                    'user_id' => $request->user_id,
                ]);

                $this->line("  - Dispatched deletion for request: {$request->id}");
            }

            $count++;
        }

        if (! $dryRun) {
            Log::info('[ProcessPendingDeletionsCommand] Dispatched deletion jobs', [
                'request_count' => $count,
                'forced' => $force,
            ]);
        }

        $this->newLine();

        if ($dryRun) {
            $this->info("[DRY RUN] Would process {$count} deletion requests.");
        } else {
            $this->info("Dispatched {$count} deletion jobs.");
        }

        return self::SUCCESS;
    }

    /**
     * Display information about a deletion request.
     */
    private function displayRequestInfo(object $request, int $gracePeriodDays): void
    {
        $approvedAt = new \DateTimeImmutable($request->approved_at);
        $gracePeriodEnd = $approvedAt->modify("+{$gracePeriodDays} days");
        $now = new \DateTimeImmutable;
        $pastGracePeriod = $now >= $gracePeriodEnd;

        $this->table(
            ['Field', 'Value'],
            [
                ['Request ID', $request->id],
                ['User ID', $request->user_id],
                ['Status', $request->status],
                ['Reason', $request->reason ?? 'Not specified'],
                ['Approved At', $request->approved_at],
                ['Grace Period Ends', $gracePeriodEnd->format('Y-m-d H:i:s')],
                ['Past Grace Period', $pastGracePeriod ? 'Yes' : 'No'],
                ['Cancelled', $request->cancelled_at !== null ? 'Yes' : 'No'],
            ]
        );
    }
}
