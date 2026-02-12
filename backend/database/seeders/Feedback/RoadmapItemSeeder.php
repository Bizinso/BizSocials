<?php

declare(strict_types=1);

namespace Database\Seeders\Feedback;

use App\Enums\Feedback\AdminPriority;
use App\Enums\Feedback\RoadmapCategory;
use App\Enums\Feedback\RoadmapStatus;
use App\Models\Feedback\RoadmapItem;
use Illuminate\Database\Seeder;

/**
 * Seeder for RoadmapItem.
 *
 * Creates sample roadmap items.
 */
final class RoadmapItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Roadmap Items...');

        $items = $this->getRoadmapItems();

        foreach ($items as $item) {
            RoadmapItem::create($item);
        }

        $this->command->info('Roadmap Items seeded successfully!');
    }

    /**
     * Get the list of roadmap items to create.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRoadmapItems(): array
    {
        return [
            // Shipped
            [
                'title' => 'TikTok Integration',
                'description' => 'Full TikTok publishing and analytics support',
                'detailed_description' => 'Complete TikTok integration including video publishing, scheduling, and comprehensive analytics tracking.',
                'category' => RoadmapCategory::INTEGRATIONS,
                'status' => RoadmapStatus::SHIPPED,
                'quarter' => 'Q4 2025',
                'shipped_date' => now()->subDays(30),
                'priority' => AdminPriority::HIGH,
                'progress_percentage' => 100,
                'is_public' => true,
                'linked_feedback_count' => 45,
                'total_votes' => 567,
            ],
            // In Progress
            [
                'title' => 'Dark Mode',
                'description' => 'System-wide dark mode support',
                'detailed_description' => 'Implementation of dark mode across all platform screens including mobile apps.',
                'category' => RoadmapCategory::PLATFORM,
                'status' => RoadmapStatus::IN_PROGRESS,
                'quarter' => 'Q1 2026',
                'target_date' => now()->addDays(30),
                'priority' => AdminPriority::MEDIUM,
                'progress_percentage' => 65,
                'is_public' => true,
                'linked_feedback_count' => 23,
                'total_votes' => 234,
            ],
            // Beta
            [
                'title' => 'AI Content Assistant',
                'description' => 'AI-powered content suggestions and optimization',
                'detailed_description' => 'Leverage AI to suggest post content, optimal posting times, and content improvements.',
                'category' => RoadmapCategory::PUBLISHING,
                'status' => RoadmapStatus::BETA,
                'quarter' => 'Q1 2026',
                'target_date' => now()->addDays(15),
                'priority' => AdminPriority::HIGH,
                'progress_percentage' => 90,
                'is_public' => true,
                'linked_feedback_count' => 78,
                'total_votes' => 892,
            ],
            // Planned
            [
                'title' => 'Bulk Post Scheduling',
                'description' => 'Schedule multiple posts from CSV/spreadsheet',
                'detailed_description' => 'Import posts from CSV files and schedule them in bulk with customizable timing options.',
                'category' => RoadmapCategory::SCHEDULING,
                'status' => RoadmapStatus::PLANNED,
                'quarter' => 'Q2 2026',
                'target_date' => now()->addMonths(3),
                'priority' => AdminPriority::HIGH,
                'progress_percentage' => 0,
                'is_public' => true,
                'linked_feedback_count' => 34,
                'total_votes' => 456,
            ],
            [
                'title' => 'Advanced Analytics Dashboard',
                'description' => 'Customizable analytics with advanced metrics',
                'detailed_description' => 'Build your own analytics dashboard with customizable widgets and advanced performance metrics.',
                'category' => RoadmapCategory::ANALYTICS,
                'status' => RoadmapStatus::PLANNED,
                'quarter' => 'Q2 2026',
                'target_date' => now()->addMonths(4),
                'priority' => AdminPriority::MEDIUM,
                'progress_percentage' => 0,
                'is_public' => true,
                'linked_feedback_count' => 19,
                'total_votes' => 312,
            ],
            // Considering
            [
                'title' => 'WhatsApp Business Integration',
                'description' => 'WhatsApp Business API support',
                'detailed_description' => 'Integration with WhatsApp Business API for messaging and status updates.',
                'category' => RoadmapCategory::INTEGRATIONS,
                'status' => RoadmapStatus::CONSIDERING,
                'quarter' => null,
                'priority' => AdminPriority::LOW,
                'progress_percentage' => 0,
                'is_public' => true,
                'linked_feedback_count' => 12,
                'total_votes' => 189,
            ],
            [
                'title' => 'API Rate Limiting Dashboard',
                'description' => 'Visual API usage and rate limit tracking',
                'detailed_description' => 'Dashboard to monitor API usage, rate limits, and optimization suggestions.',
                'category' => RoadmapCategory::API,
                'status' => RoadmapStatus::CONSIDERING,
                'quarter' => null,
                'priority' => AdminPriority::LOW,
                'progress_percentage' => 0,
                'is_public' => true,
                'linked_feedback_count' => 8,
                'total_votes' => 67,
            ],
        ];
    }
}
