<?php

declare(strict_types=1);

namespace Database\Seeders\KnowledgeBase;

use App\Models\KnowledgeBase\KBTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder for KB Tags.
 *
 * Creates common tags for article classification.
 */
final class KBTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding KB Tags...');

        $tags = $this->getTags();

        foreach ($tags as $tagData) {
            KBTag::create([
                'name' => $tagData['name'],
                'slug' => Str::slug($tagData['name']),
                'usage_count' => $tagData['usage_count'] ?? 0,
            ]);
        }

        $this->command->info('KB Tags seeded successfully!');
    }

    /**
     * Get the list of tags to create.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getTags(): array
    {
        return [
            // Feature tags
            ['name' => 'Scheduling', 'usage_count' => 15],
            ['name' => 'Publishing', 'usage_count' => 12],
            ['name' => 'Analytics', 'usage_count' => 10],
            ['name' => 'Reporting', 'usage_count' => 8],
            ['name' => 'Automation', 'usage_count' => 6],
            ['name' => 'Content Calendar', 'usage_count' => 5],
            ['name' => 'Media Library', 'usage_count' => 4],
            ['name' => 'Approval Workflow', 'usage_count' => 3],

            // Platform tags
            ['name' => 'Facebook', 'usage_count' => 20],
            ['name' => 'Instagram', 'usage_count' => 18],
            ['name' => 'Twitter', 'usage_count' => 15],
            ['name' => 'LinkedIn', 'usage_count' => 12],
            ['name' => 'TikTok', 'usage_count' => 8],
            ['name' => 'YouTube', 'usage_count' => 6],

            // Content type tags
            ['name' => 'Images', 'usage_count' => 10],
            ['name' => 'Video', 'usage_count' => 8],
            ['name' => 'Stories', 'usage_count' => 6],
            ['name' => 'Reels', 'usage_count' => 5],
            ['name' => 'Carousels', 'usage_count' => 4],

            // User type tags
            ['name' => 'Beginner', 'usage_count' => 25],
            ['name' => 'Advanced', 'usage_count' => 10],
            ['name' => 'Enterprise', 'usage_count' => 5],
            ['name' => 'Agency', 'usage_count' => 4],

            // Topic tags
            ['name' => 'Best Practices', 'usage_count' => 12],
            ['name' => 'Tips & Tricks', 'usage_count' => 10],
            ['name' => 'Troubleshooting', 'usage_count' => 8],
            ['name' => 'Integration', 'usage_count' => 6],
            ['name' => 'Security', 'usage_count' => 4],
            ['name' => 'Billing', 'usage_count' => 3],
            ['name' => 'API', 'usage_count' => 5],
        ];
    }
}
