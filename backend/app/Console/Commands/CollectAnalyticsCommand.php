<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Analytics\AggregateAnalyticsJob;
use App\Jobs\Analytics\CollectSocialAnalyticsJob;
use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Collect Analytics Command
 *
 * Dispatches jobs to collect and aggregate analytics data.
 */
final class CollectAnalyticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:collect
                            {--workspace= : Specific workspace ID to collect for}
                            {--account= : Specific social account ID to collect for}
                            {--date= : Specific date to collect (YYYY-MM-DD)}
                            {--backfill= : Number of days to backfill}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collect analytics data from social platforms';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $workspaceId = $this->option('workspace');
        $accountId = $this->option('account');
        $date = $this->option('date');
        $backfillDays = $this->option('backfill');

        if ($date !== null) {
            $date = Carbon::parse($date);
        } else {
            $date = now()->subDay(); // Collect yesterday's data by default
        }

        $this->info("Collecting analytics for {$date->toDateString()}");

        if ($accountId !== null) {
            // Collect for specific account
            $this->collectForAccount($accountId, $date, (int) $backfillDays);
        } elseif ($workspaceId !== null) {
            // Collect for specific workspace
            $this->collectForWorkspace($workspaceId, $date, (int) $backfillDays);
        } else {
            // Collect for all workspaces
            $this->collectForAllWorkspaces($date, (int) $backfillDays);
        }

        $this->info('Analytics collection jobs dispatched successfully');

        return self::SUCCESS;
    }

    /**
     * Collect analytics for a specific account.
     */
    private function collectForAccount(string $accountId, Carbon $date, int $backfillDays): void
    {
        $account = SocialAccount::find($accountId);

        if ($account === null) {
            $this->error("Account {$accountId} not found");

            return;
        }

        if (!$account->isConnected()) {
            $this->warn("Account {$accountId} is not connected, skipping");

            return;
        }

        if ($backfillDays > 0) {
            $this->info("Backfilling {$backfillDays} days for account {$accountId}");

            for ($i = $backfillDays - 1; $i >= 0; $i--) {
                $backfillDate = $date->copy()->subDays($i);
                CollectSocialAnalyticsJob::dispatch($accountId, $backfillDate->toDateString());
            }
        } else {
            CollectSocialAnalyticsJob::dispatch($accountId, $date->toDateString());
        }

        $this->info("Dispatched collection job for account {$accountId}");
    }

    /**
     * Collect analytics for a specific workspace.
     */
    private function collectForWorkspace(string $workspaceId, Carbon $date, int $backfillDays): void
    {
        $workspace = Workspace::find($workspaceId);

        if ($workspace === null) {
            $this->error("Workspace {$workspaceId} not found");

            return;
        }

        $accounts = SocialAccount::query()
            ->forWorkspace($workspaceId)
            ->connected()
            ->get();

        if ($accounts->isEmpty()) {
            $this->warn("No connected accounts found for workspace {$workspaceId}");

            return;
        }

        $this->info("Found {$accounts->count()} connected accounts");

        foreach ($accounts as $account) {
            if ($backfillDays > 0) {
                for ($i = $backfillDays - 1; $i >= 0; $i--) {
                    $backfillDate = $date->copy()->subDays($i);
                    CollectSocialAnalyticsJob::dispatch($account->id, $backfillDate->toDateString());
                }
            } else {
                CollectSocialAnalyticsJob::dispatch($account->id, $date->toDateString());
            }
        }

        // Dispatch aggregation job
        AggregateAnalyticsJob::dispatch($workspaceId, $date->toDateString());

        $this->info("Dispatched collection jobs for workspace {$workspaceId}");
    }

    /**
     * Collect analytics for all workspaces.
     */
    private function collectForAllWorkspaces(Carbon $date, int $backfillDays): void
    {
        $workspaces = Workspace::all();

        if ($workspaces->isEmpty()) {
            $this->warn('No workspaces found');

            return;
        }

        $this->info("Found {$workspaces->count()} workspaces");

        $bar = $this->output->createProgressBar($workspaces->count());
        $bar->start();

        foreach ($workspaces as $workspace) {
            $accounts = SocialAccount::query()
                ->forWorkspace($workspace->id)
                ->connected()
                ->get();

            foreach ($accounts as $account) {
                if ($backfillDays > 0) {
                    for ($i = $backfillDays - 1; $i >= 0; $i--) {
                        $backfillDate = $date->copy()->subDays($i);
                        CollectSocialAnalyticsJob::dispatch($account->id, $backfillDate->toDateString());
                    }
                } else {
                    CollectSocialAnalyticsJob::dispatch($account->id, $date->toDateString());
                }
            }

            // Dispatch aggregation job
            AggregateAnalyticsJob::dispatch($workspace->id, $date->toDateString());

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
