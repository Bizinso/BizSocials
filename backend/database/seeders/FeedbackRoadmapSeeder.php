<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Feedback\FeedbackSeeder;
use Database\Seeders\Feedback\FeedbackTagSeeder;
use Database\Seeders\Feedback\ReleaseNoteSeeder;
use Database\Seeders\Feedback\RoadmapItemSeeder;
use Illuminate\Database\Seeder;

/**
 * Main seeder for Feedback & Roadmap domain.
 *
 * Calls all feedback-related seeders in the correct order.
 * Order matters due to foreign key relationships.
 */
final class FeedbackRoadmapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Feedback & Roadmap seeders...');

        // 1. Tags first (feedback references tags)
        $this->call(FeedbackTagSeeder::class);

        // 2. Roadmap items (feedback can link to roadmap)
        $this->call(RoadmapItemSeeder::class);

        // 3. Feedback (depends on tags and optionally roadmap)
        $this->call(FeedbackSeeder::class);

        // 4. Release notes (can reference roadmap items)
        $this->call(ReleaseNoteSeeder::class);

        $this->command->info('Feedback & Roadmap seeders completed successfully!');
    }
}
