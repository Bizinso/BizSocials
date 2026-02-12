<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Social\SocialAccountSeeder as SocialAccountDataSeeder;
use Illuminate\Database\Seeder;

/**
 * Main seeder for Social Account domain.
 *
 * Calls all social account-related seeders in the correct order.
 * Order matters due to foreign key relationships.
 */
final class SocialAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Social Account seeders...');

        // 1. Social Accounts (depends on workspaces and users)
        $this->call(SocialAccountDataSeeder::class);

        $this->command->info('Social Account seeders completed successfully!');
    }
}
