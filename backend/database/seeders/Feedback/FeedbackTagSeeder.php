<?php

declare(strict_types=1);

namespace Database\Seeders\Feedback;

use App\Models\Feedback\FeedbackTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder for FeedbackTag.
 *
 * Creates common feedback tags.
 */
final class FeedbackTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Feedback Tags...');

        $tags = $this->getTags();

        foreach ($tags as $tag) {
            FeedbackTag::create([
                'name' => $tag['name'],
                'slug' => Str::slug($tag['name']),
                'color' => $tag['color'],
                'description' => $tag['description'],
                'usage_count' => 0,
            ]);
        }

        $this->command->info('Feedback Tags seeded successfully!');
    }

    /**
     * Get the list of tags to create.
     *
     * @return array<int, array<string, string>>
     */
    private function getTags(): array
    {
        return [
            [
                'name' => 'High Priority',
                'color' => '#EF4444',
                'description' => 'Critical issues or highly requested features',
            ],
            [
                'name' => 'Quick Win',
                'color' => '#10B981',
                'description' => 'Easy to implement with high impact',
            ],
            [
                'name' => 'UI/UX',
                'color' => '#8B5CF6',
                'description' => 'User interface and experience improvements',
            ],
            [
                'name' => 'Performance',
                'color' => '#F59E0B',
                'description' => 'Speed and performance related',
            ],
            [
                'name' => 'Mobile',
                'color' => '#3B82F6',
                'description' => 'Mobile app specific',
            ],
            [
                'name' => 'Enterprise',
                'color' => '#6366F1',
                'description' => 'Enterprise-tier feature requests',
            ],
            [
                'name' => 'Integration',
                'color' => '#EC4899',
                'description' => 'Third-party integrations',
            ],
            [
                'name' => 'Security',
                'color' => '#DC2626',
                'description' => 'Security related improvements',
            ],
            [
                'name' => 'Accessibility',
                'color' => '#14B8A6',
                'description' => 'Accessibility improvements',
            ],
            [
                'name' => 'Documentation',
                'color' => '#6B7280',
                'description' => 'Documentation improvements',
            ],
        ];
    }
}
