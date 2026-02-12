<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Content\PostSeeder;
use Illuminate\Database\Seeder;

/**
 * Main seeder for Content domain.
 *
 * Calls all content-related seeders in the correct order.
 * Order matters due to foreign key relationships.
 */
final class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Content seeders...');

        // 1. Posts (with targets, media, and approval decisions)
        $this->call(PostSeeder::class);

        $this->command->info('Content seeders completed successfully!');
    }
}
