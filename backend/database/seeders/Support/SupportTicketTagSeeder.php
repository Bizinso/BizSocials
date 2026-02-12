<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Models\Support\SupportTicketTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder for SupportTicketTag.
 *
 * Creates default support ticket tags.
 */
final class SupportTicketTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Support Ticket Tags...');

        $tags = $this->getTags();

        foreach ($tags as $tagData) {
            SupportTicketTag::create([
                ...$tagData,
                'slug' => Str::slug($tagData['name']),
            ]);
        }

        $this->command->info('Support Ticket Tags seeded successfully!');
    }

    /**
     * Get the list of tags to create.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getTags(): array
    {
        return [
            [
                'name' => 'Urgent',
                'description' => 'Requires immediate attention',
                'color' => '#EF4444',
            ],
            [
                'name' => 'High Priority',
                'description' => 'High priority issue',
                'color' => '#F59E0B',
            ],
            [
                'name' => 'Bug',
                'description' => 'Software bug or defect',
                'color' => '#DC2626',
            ],
            [
                'name' => 'Feature Request',
                'description' => 'Request for new feature',
                'color' => '#8B5CF6',
            ],
            [
                'name' => 'Billing',
                'description' => 'Billing related issue',
                'color' => '#10B981',
            ],
            [
                'name' => 'Account',
                'description' => 'Account related issue',
                'color' => '#6366F1',
            ],
            [
                'name' => 'Integration',
                'description' => 'Third-party integration issue',
                'color' => '#EC4899',
            ],
            [
                'name' => 'Mobile',
                'description' => 'Mobile app related',
                'color' => '#14B8A6',
            ],
            [
                'name' => 'API',
                'description' => 'API related issue',
                'color' => '#F97316',
            ],
            [
                'name' => 'Documentation',
                'description' => 'Documentation issue or question',
                'color' => '#06B6D4',
            ],
            [
                'name' => 'Quick Win',
                'description' => 'Can be resolved quickly',
                'color' => '#84CC16',
            ],
            [
                'name' => 'Needs Investigation',
                'description' => 'Requires further investigation',
                'color' => '#A855F7',
            ],
            [
                'name' => 'Escalated',
                'description' => 'Escalated to senior support',
                'color' => '#EF4444',
            ],
            [
                'name' => 'Waiting on Third Party',
                'description' => 'Waiting on external provider',
                'color' => '#F59E0B',
            ],
        ];
    }
}
