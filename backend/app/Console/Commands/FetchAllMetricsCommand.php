<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Content\PostStatus;
use App\Jobs\Analytics\FetchPostMetricsJob;
use App\Models\Workspace\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * FetchAllMetricsCommand
 *
 * Artisan command that dispatches metrics fetch jobs for all active workspaces.
 * This command is designed to be run by the scheduler every 6 hours to
 * keep post analytics up to date with engagement metrics from social platforms.
 *
 * Usage:
 *   php artisan analytics:fetch-metrics
 *   php artisan analytics:fetch-metrics --workspace=uuid
 *
 * Features:
 * - Finds all workspaces with published posts
 * - Dispatches FetchPostMetricsJob for each workspace
 * - Optionally fetch metrics for a single workspace
 * - Logs job dispatch for monitoring
 */
final class FetchAllMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:fetch-metrics
                            {--workspace= : Fetch metrics for a specific workspace by UUID}
                            {--recent-only : Only fetch metrics for posts published in the last 30 days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch metrics fetch jobs for all active workspaces';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $workspaceId = $this->option('workspace');

        if ($workspaceId !== null) {
            return $this->fetchMetricsForWorkspace($workspaceId);
        }

        return $this->fetchMetricsForAllWorkspaces();
    }

    /**
     * Fetch metrics for a single workspace.
     */
    private function fetchMetricsForWorkspace(string $workspaceId): int
    {
        $workspace = Workspace::find($workspaceId);

        if ($workspace === null) {
            $this->error("Workspace not found: {$workspaceId}");

            return self::FAILURE;
        }

        $this->info("Dispatching metrics fetch for workspace: {$workspace->name}");

        FetchPostMetricsJob::dispatch($workspace->id);

        Log::info('[FetchAllMetricsCommand] Dispatched single workspace metrics fetch', [
            'workspace_id' => $workspace->id,
            'workspace_name' => $workspace->name,
        ]);

        $this->info('Metrics fetch job dispatched successfully.');

        return self::SUCCESS;
    }

    /**
     * Fetch metrics for all workspaces with published posts.
     */
    private function fetchMetricsForAllWorkspaces(): int
    {
        $this->info('Finding workspaces with published posts...');

        $recentOnly = $this->option('recent-only');

        $workspacesQuery = Workspace::query()
            ->whereHas('posts', function ($query) use ($recentOnly): void {
                $query->where('status', PostStatus::PUBLISHED);

                if ($recentOnly) {
                    $query->where('published_at', '>=', now()->subDays(30));
                }
            })
            ->active();

        $workspaces = $workspacesQuery->get();

        if ($workspaces->isEmpty()) {
            $this->info('No workspaces with published posts found.');
            Log::debug('[FetchAllMetricsCommand] No workspaces with published posts');

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($workspaces as $workspace) {
            FetchPostMetricsJob::dispatch($workspace->id);
            $count++;

            $this->line("  - Dispatched metrics fetch for: {$workspace->name}");
        }

        Log::info('[FetchAllMetricsCommand] Dispatched metrics fetch jobs', [
            'workspace_count' => $count,
            'recent_only' => $recentOnly,
        ]);

        $this->newLine();
        $this->info("Dispatched metrics fetch for {$count} workspaces.");

        return self::SUCCESS;
    }
}
