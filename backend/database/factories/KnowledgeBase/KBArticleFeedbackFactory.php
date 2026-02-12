<?php

declare(strict_types=1);

namespace Database\Factories\KnowledgeBase;

use App\Enums\KnowledgeBase\KBFeedbackCategory;
use App\Enums\KnowledgeBase\KBFeedbackStatus;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBArticleFeedback;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for KBArticleFeedback model.
 *
 * @extends Factory<KBArticleFeedback>
 */
final class KBArticleFeedbackFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<KBArticleFeedback>
     */
    protected $model = KBArticleFeedback::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => KBArticle::factory(),
            'is_helpful' => fake()->boolean(70),
            'feedback_text' => fake()->boolean(50) ? fake()->paragraph() : null,
            'feedback_category' => fake()->randomElement(KBFeedbackCategory::cases()),
            'user_id' => fake()->boolean(60) ? User::factory() : null,
            'tenant_id' => fake()->boolean(60) ? Tenant::factory() : null,
            'session_id' => fake()->boolean(80) ? Str::uuid()->toString() : null,
            'ip_address' => fake()->ipv4(),
            'status' => KBFeedbackStatus::PENDING,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'admin_notes' => null,
        ];
    }

    /**
     * Set as helpful feedback.
     */
    public function helpful(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_helpful' => true,
            'feedback_category' => KBFeedbackCategory::HELPFUL,
        ]);
    }

    /**
     * Set as not helpful feedback.
     */
    public function notHelpful(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_helpful' => false,
            'feedback_category' => fake()->randomElement([
                KBFeedbackCategory::OUTDATED,
                KBFeedbackCategory::INCOMPLETE,
                KBFeedbackCategory::UNCLEAR,
                KBFeedbackCategory::INCORRECT,
            ]),
            'feedback_text' => fake()->paragraph(),
        ]);
    }

    /**
     * Set as pending feedback.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => KBFeedbackStatus::PENDING,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    /**
     * Set as reviewed feedback.
     */
    public function reviewed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => KBFeedbackStatus::REVIEWED,
            'reviewed_by' => SuperAdminUser::factory(),
            'reviewed_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'admin_notes' => fake()->sentence(),
        ]);
    }

    /**
     * Set as actioned feedback.
     */
    public function actioned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => KBFeedbackStatus::ACTIONED,
            'reviewed_by' => SuperAdminUser::factory(),
            'reviewed_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'admin_notes' => fake()->paragraph(),
        ]);
    }

    /**
     * Set as dismissed feedback.
     */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => KBFeedbackStatus::DISMISSED,
            'reviewed_by' => SuperAdminUser::factory(),
            'reviewed_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'admin_notes' => fake()->sentence(),
        ]);
    }

    /**
     * Associate with a specific article.
     */
    public function forArticle(KBArticle $article): static
    {
        return $this->state(fn (array $attributes): array => [
            'article_id' => $article->id,
        ]);
    }

    /**
     * Associate with a specific user.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }
}
