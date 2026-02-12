<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Support\SupportCannedResponseSeeder;
use Database\Seeders\Support\SupportCategorySeeder;
use Database\Seeders\Support\SupportTicketSeeder;
use Database\Seeders\Support\SupportTicketTagSeeder;
use Illuminate\Database\Seeder;

/**
 * Main seeder for Support domain.
 *
 * Calls all support-related seeders in the correct order.
 * Order matters due to foreign key relationships.
 */
final class SupportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Support seeders...');

        // 1. Categories first (tickets reference categories)
        $this->call(SupportCategorySeeder::class);

        // 2. Tags (tickets can have tags)
        $this->call(SupportTicketTagSeeder::class);

        // 3. Canned responses (depends on super admin)
        $this->call(SupportCannedResponseSeeder::class);

        // 4. Tickets (depends on categories, tags, users)
        $this->call(SupportTicketSeeder::class);

        $this->command->info('Support seeders completed successfully!');
    }
}
