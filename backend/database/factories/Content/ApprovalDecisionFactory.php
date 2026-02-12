<?php

declare(strict_types=1);

namespace Database\Factories\Content;

use App\Enums\Content\ApprovalDecisionType;
use App\Models\Content\ApprovalDecision;
use App\Models\Content\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for ApprovalDecision model.
 *
 * @extends Factory<ApprovalDecision>
 */
final class ApprovalDecisionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ApprovalDecision>
     */
    protected $model = ApprovalDecision::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'decided_by_user_id' => User::factory(),
            'decision' => fake()->randomElement(ApprovalDecisionType::cases()),
            'comment' => fake()->boolean(70) ? fake()->sentence() : null,
            'is_active' => true,
            'decided_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Set the decision to approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'decision' => ApprovalDecisionType::APPROVED,
            'comment' => fake()->boolean(50) ? 'Looks good!' : null,
        ]);
    }

    /**
     * Set the decision to rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'decision' => ApprovalDecisionType::REJECTED,
            'comment' => fake()->sentence(),
        ]);
    }

    /**
     * Set the decision as active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    /**
     * Set the decision as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Associate with a specific post.
     */
    public function forPost(Post $post): static
    {
        return $this->state(fn (array $attributes): array => [
            'post_id' => $post->id,
        ]);
    }

    /**
     * Set the user who made the decision.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'decided_by_user_id' => $user->id,
        ]);
    }

    /**
     * Set a specific comment.
     */
    public function withComment(string $comment): static
    {
        return $this->state(fn (array $attributes): array => [
            'comment' => $comment,
        ]);
    }

    /**
     * Set the decided_at timestamp.
     */
    public function decidedAt(\DateTimeInterface $decidedAt): static
    {
        return $this->state(fn (array $attributes): array => [
            'decided_at' => $decidedAt,
        ]);
    }
}
