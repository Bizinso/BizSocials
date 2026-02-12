<?php

declare(strict_types=1);

namespace Database\Seeders\KnowledgeBase;

use App\Enums\KnowledgeBase\KBFeedbackCategory;
use App\Enums\KnowledgeBase\KBFeedbackStatus;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBArticleFeedback;
use App\Models\Platform\SuperAdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder for KB Article Feedback.
 *
 * Creates sample feedback for knowledge base articles.
 */
final class KBArticleFeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding KB Article Feedback...');

        $articles = KBArticle::published()->get();
        $admin = SuperAdminUser::first();

        if ($articles->isEmpty()) {
            $this->command->warn('No published articles found. Skipping feedback seeding.');
            return;
        }

        foreach ($articles as $article) {
            // Create a mix of helpful and not helpful feedback
            $feedbackCount = fake()->numberBetween(2, 8);

            for ($i = 0; $i < $feedbackCount; $i++) {
                $isHelpful = fake()->boolean(70);
                $status = fake()->randomElement([
                    KBFeedbackStatus::PENDING,
                    KBFeedbackStatus::PENDING,
                    KBFeedbackStatus::REVIEWED,
                    KBFeedbackStatus::ACTIONED,
                    KBFeedbackStatus::DISMISSED,
                ]);

                $feedback = [
                    'article_id' => $article->id,
                    'is_helpful' => $isHelpful,
                    'session_id' => Str::uuid()->toString(),
                    'ip_address' => fake()->ipv4(),
                    'status' => $status,
                ];

                if ($isHelpful) {
                    $feedback['feedback_category'] = KBFeedbackCategory::HELPFUL;
                    $feedback['feedback_text'] = fake()->boolean(30)
                        ? fake()->randomElement([
                            'Very helpful, thank you!',
                            'This solved my problem.',
                            'Clear and easy to follow.',
                            'Great article!',
                        ])
                        : null;
                } else {
                    $feedback['feedback_category'] = fake()->randomElement([
                        KBFeedbackCategory::OUTDATED,
                        KBFeedbackCategory::INCOMPLETE,
                        KBFeedbackCategory::UNCLEAR,
                        KBFeedbackCategory::INCORRECT,
                        KBFeedbackCategory::OTHER,
                    ]);
                    $feedback['feedback_text'] = fake()->randomElement([
                        'The screenshots are outdated.',
                        'Missing steps for the new interface.',
                        'Could use more examples.',
                        'Step 3 is confusing.',
                        'This didn\'t work for me.',
                    ]);
                }

                if ($status !== KBFeedbackStatus::PENDING && $admin) {
                    $feedback['reviewed_by'] = $admin->id;
                    $feedback['reviewed_at'] = fake()->dateTimeBetween('-1 week', 'now');
                    $feedback['admin_notes'] = $status === KBFeedbackStatus::ACTIONED
                        ? 'Updated article based on feedback.'
                        : 'Reviewed - no changes needed.';
                }

                KBArticleFeedback::create($feedback);
            }
        }

        $this->command->info('KB Article Feedback seeded successfully!');
    }
}
