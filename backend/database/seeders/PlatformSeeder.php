<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Platform\FeatureFlagSeeder;
use Database\Seeders\Platform\PlanDefinitionSeeder;
use Database\Seeders\Platform\PlanLimitSeeder;
use Database\Seeders\Platform\PlatformConfigSeeder;
use Database\Seeders\Platform\SuperAdminUserSeeder;
use Illuminate\Database\Seeder;

/**
 * Main seeder for Platform domain.
 *
 * Calls all platform-related seeders in the correct order.
 * Order matters due to foreign key relationships.
 */
final class PlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Platform seeders...');

        // 1. Super Admin Users first (required for platform_configs foreign key)
        $this->call(SuperAdminUserSeeder::class);

        // 2. Platform Configs (depends on super_admin_users)
        $this->call(PlatformConfigSeeder::class);

        // 3. Feature Flags (independent)
        $this->call(FeatureFlagSeeder::class);

        // 4. Plan Definitions (independent)
        $this->call(PlanDefinitionSeeder::class);

        // 5. Plan Limits (depends on plan_definitions)
        $this->call(PlanLimitSeeder::class);

        $this->command->info('Platform seeders completed successfully!');
    }
}
