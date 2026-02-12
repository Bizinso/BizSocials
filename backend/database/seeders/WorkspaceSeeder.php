<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Workspace\WorkspaceMembershipSeeder;
use Database\Seeders\Workspace\WorkspaceSeeder as WorkspaceDataSeeder;
use Illuminate\Database\Seeder;

/**
 * Main seeder for Workspace domain.
 *
 * Calls all workspace-related seeders in the correct order.
 * Order matters due to foreign key relationships.
 */
final class WorkspaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Workspace seeders...');

        // 1. Workspaces first (base entity)
        $this->call(WorkspaceDataSeeder::class);

        // 2. Workspace Memberships (depends on workspaces and users)
        $this->call(WorkspaceMembershipSeeder::class);

        $this->command->info('Workspace seeders completed successfully!');
    }
}
