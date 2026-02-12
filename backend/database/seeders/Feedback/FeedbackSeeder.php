<?php

declare(strict_types=1);

namespace Database\Seeders\Feedback;

use App\Enums\Feedback\FeedbackCategory;
use App\Enums\Feedback\FeedbackSource;
use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackType;
use App\Enums\Feedback\UserPriority;
use App\Models\Feedback\Feedback;
use App\Models\Feedback\FeedbackTag;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder for Feedback.
 *
 * Creates sample feedback items.
 */
final class FeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Feedback...');

        $tenant = Tenant::first();
        $user = User::first();

        if (!$tenant || !$user) {
            $this->command->warn('No tenant or user found. Skipping feedback seeding.');
            return;
        }

        $feedbackItems = $this->getFeedbackItems();

        foreach ($feedbackItems as $item) {
            $tagSlugs = $item['tags'] ?? [];
            unset($item['tags']);

            $feedback = Feedback::create([
                ...$item,
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'source' => FeedbackSource::PORTAL,
            ]);

            // Attach tags
            if (!empty($tagSlugs)) {
                $tags = FeedbackTag::whereIn('slug', $tagSlugs)->get();
                foreach ($tags as $tag) {
                    $feedback->tags()->attach($tag->id);
                    $tag->incrementUsageCount();
                }
            }
        }

        // Create some random feedback with factory
        Feedback::factory()
            ->count(10)
            ->forTenant($tenant)
            ->byUser($user)
            ->create();

        $this->command->info('Feedback seeded successfully!');
    }

    /**
     * Get the list of feedback items to create.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getFeedbackItems(): array
    {
        return [
            [
                'title' => 'Add bulk scheduling for multiple posts',
                'description' => 'It would be great to be able to schedule multiple posts at once from a CSV or spreadsheet. This would save hours of manual work for content teams.',
                'feedback_type' => FeedbackType::FEATURE_REQUEST,
                'category' => FeedbackCategory::SCHEDULING,
                'user_priority' => UserPriority::CRITICAL,
                'business_impact' => 'Could save 5+ hours per week for our content team',
                'status' => FeedbackStatus::PLANNED,
                'vote_count' => 156,
                'tags' => ['high-priority', 'quick-win'],
            ],
            [
                'title' => 'Dark mode support',
                'description' => 'Please add dark mode support across the entire platform. Many users prefer working in dark mode, especially at night.',
                'feedback_type' => FeedbackType::FEATURE_REQUEST,
                'category' => FeedbackCategory::GENERAL,
                'user_priority' => UserPriority::IMPORTANT,
                'status' => FeedbackStatus::IN_PROGRESS,
                'vote_count' => 234,
                'tags' => ['ui-ux'],
            ],
            [
                'title' => 'Instagram Reels support',
                'description' => 'We need the ability to schedule and publish Instagram Reels directly from the platform.',
                'feedback_type' => FeedbackType::INTEGRATION_REQUEST,
                'category' => FeedbackCategory::PUBLISHING,
                'user_priority' => UserPriority::CRITICAL,
                'status' => FeedbackStatus::UNDER_REVIEW,
                'vote_count' => 312,
                'tags' => ['integration', 'high-priority'],
            ],
            [
                'title' => 'Improve analytics loading speed',
                'description' => 'The analytics dashboard takes too long to load, especially when viewing data for multiple accounts.',
                'feedback_type' => FeedbackType::IMPROVEMENT,
                'category' => FeedbackCategory::ANALYTICS,
                'user_priority' => UserPriority::IMPORTANT,
                'status' => FeedbackStatus::NEW,
                'vote_count' => 89,
                'tags' => ['performance'],
            ],
            [
                'title' => 'Mobile app crashes on iOS 17',
                'description' => 'The mobile app frequently crashes when switching between tabs on iOS 17. This started happening after the latest update.',
                'feedback_type' => FeedbackType::BUG_REPORT,
                'category' => FeedbackCategory::MOBILE_APP,
                'user_priority' => UserPriority::CRITICAL,
                'status' => FeedbackStatus::SHIPPED,
                'vote_count' => 45,
                'tags' => ['mobile'],
            ],
            [
                'title' => 'Better keyboard shortcuts',
                'description' => 'Add comprehensive keyboard shortcuts for power users. Would love shortcuts for quick actions like schedule, publish, and navigate.',
                'feedback_type' => FeedbackType::UX_FEEDBACK,
                'category' => FeedbackCategory::GENERAL,
                'user_priority' => UserPriority::NICE_TO_HAVE,
                'status' => FeedbackStatus::NEW,
                'vote_count' => 67,
                'tags' => ['ui-ux', 'quick-win'],
            ],
        ];
    }
}
