<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Social\SocialAccountStatus;
use App\Jobs\Inbox\SyncInboxJob;
use App\Models\Workspace\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * SyncAllInboxCommand
 *
 * Artisan command that dispatches inbox sync jobs for all active workspaces.
 * This command is designed to be run by the scheduler every 15 minutes to
 * keep the unified inbox up to date with social platform engagement.
 *
 * Usage:
 *   php artisan inbox:sync-all
 *   php artisan inbox:sync-all --workspace=uuid
 *
 * Features:
 * - Finds all workspaces with connected social accounts
 * - Dispatches SyncInboxJob for each workspace
 * - Optionally sync a single workspace
 * - Logs job dispatch for monitoring
 */
final class SyncAllInboxCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inbox:sync-all
                            {--workspace= : Sync a specific workspace by UUID}
                            {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch inbox sync jobs for all active workspaces';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $workspaceId = $this->option('workspace');
        $force = $this->option('force');

        if ($workspaceId !== null) {
            return $this->syncSingleWorkspace($workspaceId);
        }

        return $this->syncAllWorkspaces();
    }

    /**
     * Sync a single workspace.
     */
    private function syncSingleWorkspace(string $workspaceId): int
    {
        $workspace = Workspace::find($workspaceId);

        if ($workspace === null) {
            $this->error("Workspace not found: {$workspaceId}");

            return self::FAILURE;
        }

        $this->info("Dispatching inbox sync for workspace: {$workspace->name}");

        SyncInboxJob::dispatch($workspace->id);

        Log::info('[SyncAllInboxCommand] Dispatched single workspace sync', [
            'workspace_id' => $workspace->id,
            'workspace_name' => $workspace->name,
        ]);

        $this->info('Inbox sync job dispatched successfully.');

        return self::SUCCESS;
    }

    /**
     * Sync all active workspaces with connected social accounts.
     */
    private function syncAllWorkspaces(): int
    {
        $this->info('Finding workspaces with connected social accounts...');

        $workspaces = Workspace::query()
            ->whereHas('socialAccounts', function ($query): void {
                $query->where('status', SocialAccountStatus::CONNECTED);
            })
            ->active()
            ->get();

        if ($workspaces->isEmpty()) {
            $this->info('No workspaces with connected social accounts found.');
            Log::debug('[SyncAllInboxCommand] No workspaces to sync');

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($workspaces as $workspace) {
            SyncInboxJob::dispatch($workspace->id);
            $count++;

            $this->line("  - Dispatched sync for: {$workspace->name}");
        }

        Log::info('[SyncAllInboxCommand] Dispatched inbox sync jobs', [
            'workspace_count' => $count,
        ]);

        $this->newLine();
        $this->info("Dispatched inbox sync for {$count} workspaces.");

        return self::SUCCESS;
    }
}
