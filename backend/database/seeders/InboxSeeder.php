<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Inbox\InboxItemSeeder;
use Database\Seeders\Inbox\InboxReplySeeder;
use Database\Seeders\Inbox\PostMetricSnapshotSeeder;
use Illuminate\Database\Seeder;

/**
 * Main seeder for Inbox domain.
 *
 * Calls all inbox-related seeders in the correct order.
 * Order matters due to foreign key relationships.
 */
final class InboxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Inbox seeders...');

        // 1. Inbox Items (comments and mentions)
        $this->call(InboxItemSeeder::class);

        // 2. Inbox Replies (replies to comments)
        $this->call(InboxReplySeeder::class);

        // 3. Post Metric Snapshots (engagement metrics)
        $this->call(PostMetricSnapshotSeeder::class);

        $this->command->info('Inbox seeders completed successfully!');
    }
}
