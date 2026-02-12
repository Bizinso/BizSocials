<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Audit\AuditLogSeeder;
use Database\Seeders\Audit\SecurityEventSeeder;
use Illuminate\Database\Seeder;

/**
 * Main seeder for Audit & Security domain.
 *
 * Calls all audit and security-related seeders in the correct order.
 */
final class AuditSecuritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Audit & Security seeders...');

        // 1. Audit logs
        $this->call(AuditLogSeeder::class);

        // 2. Security events
        $this->call(SecurityEventSeeder::class);

        $this->command->info('Audit & Security seeders completed successfully!');
    }
}
