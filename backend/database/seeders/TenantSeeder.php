<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Tenant\TenantOnboardingSeeder;
use Database\Seeders\Tenant\TenantProfileSeeder;
use Database\Seeders\Tenant\TenantSeeder as TenantDataSeeder;
use Database\Seeders\Tenant\TenantUsageSeeder;
use Illuminate\Database\Seeder;

/**
 * Main seeder for Tenant domain.
 *
 * Calls all tenant-related seeders in the correct order.
 * Order matters due to foreign key relationships.
 */
final class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Tenant seeders...');

        // 1. Tenants first (base entity)
        $this->call(TenantDataSeeder::class);

        // 2. Tenant Profiles (depends on tenants)
        $this->call(TenantProfileSeeder::class);

        // 3. Tenant Onboarding (depends on tenants)
        $this->call(TenantOnboardingSeeder::class);

        // 4. Tenant Usage (depends on tenants)
        $this->call(TenantUsageSeeder::class);

        $this->command->info('Tenant seeders completed successfully!');
    }
}
