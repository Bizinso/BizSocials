<?php

declare(strict_types=1);

namespace Database\Factories\Feedback;

use App\Models\Feedback\Feedback;
use App\Models\Feedback\FeedbackComment;
use App\Models\Platform\SuperAdminUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for FeedbackComment model.
 *
 * @extends Factory<FeedbackComment>
 */
final class FeedbackCommentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<FeedbackComment>
     */
    protected $model = FeedbackComment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'feedback_id' => Feedback::factory(),
            'user_id' => User::factory(),
            'admin_id' => null,
            'commenter_name' => fake()->name(),
            'content' => fake()->paragraph(),
            'is_internal' => false,
            'is_official_response' => false,
        ];
    }

    /**
     * Set as internal comment.
     */
    public function internal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_internal' => true,
            'admin_id' => SuperAdminUser::factory(),
            'user_id' => null,
        ]);
    }

    /**
     * Set as official response.
     */
    public function official(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_official_response' => true,
            'admin_id' => SuperAdminUser::factory(),
            'user_id' => null,
        ]);
    }

    /**
     * Set for a specific feedback.
     */
    public function forFeedback(Feedback $feedback): static
    {
        return $this->state(fn (array $attributes): array => [
            'feedback_id' => $feedback->id,
        ]);
    }

    /**
     * Set by a specific user.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
            'admin_id' => null,
        ]);
    }

    /**
     * Set by a specific admin.
     */
    public function byAdmin(SuperAdminUser $admin): static
    {
        return $this->state(fn (array $attributes): array => [
            'admin_id' => $admin->id,
            'user_id' => null,
        ]);
    }
}
