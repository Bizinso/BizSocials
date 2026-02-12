<?php

declare(strict_types=1);

namespace Database\Factories\Feedback;

use App\Enums\Feedback\VoteType;
use App\Models\Feedback\Feedback;
use App\Models\Feedback\FeedbackVote;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for FeedbackVote model.
 *
 * @extends Factory<FeedbackVote>
 */
final class FeedbackVoteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<FeedbackVote>
     */
    protected $model = FeedbackVote::class;

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
            'tenant_id' => Tenant::factory(),
            'voter_email' => fake()->email(),
            'session_id' => Str::uuid()->toString(),
            'vote_type' => VoteType::UPVOTE,
        ];
    }

    /**
     * Set as upvote.
     */
    public function upvote(): static
    {
        return $this->state(fn (array $attributes): array => [
            'vote_type' => VoteType::UPVOTE,
        ]);
    }

    /**
     * Set as downvote.
     */
    public function downvote(): static
    {
        return $this->state(fn (array $attributes): array => [
            'vote_type' => VoteType::DOWNVOTE,
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
     * Set for a specific user.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set as anonymous (no user).
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => null,
        ]);
    }
}
