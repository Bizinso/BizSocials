<?php

declare(strict_types=1);

namespace Database\Factories\Feedback;

use App\Enums\Feedback\AdminPriority;
use App\Enums\Feedback\EffortEstimate;
use App\Enums\Feedback\FeedbackCategory;
use App\Enums\Feedback\FeedbackSource;
use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackType;
use App\Enums\Feedback\UserPriority;
use App\Models\Feedback\Feedback;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for Feedback model.
 *
 * @extends Factory<Feedback>
 */
final class FeedbackFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Feedback>
     */
    protected $model = Feedback::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'submitter_email' => fake()->email(),
            'submitter_name' => fake()->name(),
            'title' => fake()->sentence(6),
            'description' => fake()->paragraphs(2, true),
            'feedback_type' => fake()->randomElement(FeedbackType::cases()),
            'category' => fake()->randomElement(FeedbackCategory::cases()),
            'user_priority' => UserPriority::IMPORTANT,
            'business_impact' => fake()->boolean(50) ? fake()->paragraph() : null,
            'admin_priority' => null,
            'effort_estimate' => null,
            'status' => FeedbackStatus::NEW,
            'status_reason' => null,
            'vote_count' => fake()->numberBetween(0, 100),
            'roadmap_item_id' => null,
            'duplicate_of_id' => null,
            'source' => FeedbackSource::PORTAL,
            'browser_info' => null,
            'page_url' => fake()->url(),
            'reviewed_at' => null,
            'reviewed_by' => null,
        ];
    }

    /**
     * Set the status to new.
     */
    public function newStatus(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FeedbackStatus::NEW,
            'reviewed_at' => null,
        ]);
    }

    /**
     * Set the status to under review.
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FeedbackStatus::UNDER_REVIEW,
            'reviewed_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Set the status to planned.
     */
    public function planned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FeedbackStatus::PLANNED,
            'admin_priority' => AdminPriority::MEDIUM,
            'effort_estimate' => fake()->randomElement(EffortEstimate::cases()),
        ]);
    }

    /**
     * Set the status to in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FeedbackStatus::IN_PROGRESS,
            'admin_priority' => AdminPriority::HIGH,
            'effort_estimate' => fake()->randomElement(EffortEstimate::cases()),
        ]);
    }

    /**
     * Set the status to shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FeedbackStatus::SHIPPED,
            'admin_priority' => AdminPriority::HIGH,
        ]);
    }

    /**
     * Set the status to declined.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FeedbackStatus::DECLINED,
            'status_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Set the feedback type.
     */
    public function ofType(FeedbackType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'feedback_type' => $type,
        ]);
    }

    /**
     * Set the category.
     */
    public function inCategory(FeedbackCategory $category): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => $category,
        ]);
    }

    /**
     * Set the user priority.
     */
    public function withUserPriority(UserPriority $priority): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_priority' => $priority,
        ]);
    }

    /**
     * Set the admin priority.
     */
    public function withAdminPriority(AdminPriority $priority): static
    {
        return $this->state(fn (array $attributes): array => [
            'admin_priority' => $priority,
        ]);
    }

    /**
     * Set the source.
     */
    public function fromSource(FeedbackSource $source): static
    {
        return $this->state(fn (array $attributes): array => [
            'source' => $source,
        ]);
    }

    /**
     * Set for a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes): array => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Set for a specific user.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set as highly voted.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes): array => [
            'vote_count' => fake()->numberBetween(100, 500),
        ]);
    }
}
