<?php

declare(strict_types=1);

namespace Database\Factories\Content;

use App\Enums\Content\PostStatus;
use App\Enums\Content\PostType;
use App\Models\Content\Post;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for Post model.
 *
 * @extends Factory<Post>
 */
final class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Post>
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'created_by_user_id' => User::factory(),
            'content_text' => fake()->paragraph(3),
            'content_variations' => null,
            'status' => PostStatus::DRAFT,
            'post_type' => PostType::STANDARD,
            'scheduled_at' => null,
            'scheduled_timezone' => null,
            'published_at' => null,
            'submitted_at' => null,
            'hashtags' => fake()->boolean(60) ? $this->generateHashtags() : null,
            'mentions' => fake()->boolean(30) ? $this->generateMentions() : null,
            'link_url' => fake()->boolean(40) ? fake()->url() : null,
            'link_preview' => null,
            'first_comment' => fake()->boolean(20) ? fake()->sentence() : null,
            'rejection_reason' => null,
            'metadata' => null,
        ];
    }

    /**
     * Set the status to draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostStatus::DRAFT,
            'submitted_at' => null,
            'published_at' => null,
        ]);
    }

    /**
     * Set the status to submitted.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Set the status to approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostStatus::APPROVED,
            'submitted_at' => now()->subHours(2),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Set the status to rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostStatus::REJECTED,
            'submitted_at' => now()->subHours(2),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Set the status to scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostStatus::SCHEDULED,
            'submitted_at' => now()->subDays(1),
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+1 week'),
            'scheduled_timezone' => fake()->randomElement(['UTC', 'America/New_York', 'Europe/London', 'Asia/Tokyo']),
        ]);
    }

    /**
     * Set the status to publishing.
     */
    public function publishing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostStatus::PUBLISHING,
            'submitted_at' => now()->subDays(1),
            'scheduled_at' => now()->subMinutes(5),
        ]);
    }

    /**
     * Set the status to published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostStatus::PUBLISHED,
            'submitted_at' => now()->subDays(2),
            'scheduled_at' => now()->subDay(),
            'published_at' => now()->subDay(),
        ]);
    }

    /**
     * Set the status to failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostStatus::FAILED,
            'submitted_at' => now()->subDays(1),
            'scheduled_at' => now()->subHours(2),
        ]);
    }

    /**
     * Set the status to cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostStatus::CANCELLED,
        ]);
    }

    /**
     * Associate with a specific workspace.
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes): array => [
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Set the user who created the post.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_by_user_id' => $user->id,
        ]);
    }

    /**
     * Schedule the post for a specific time.
     */
    public function scheduledFor(\DateTimeInterface $scheduledAt, ?string $timezone = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostStatus::SCHEDULED,
            'scheduled_at' => $scheduledAt,
            'scheduled_timezone' => $timezone ?? 'UTC',
        ]);
    }

    /**
     * Set specific content.
     */
    public function withContent(string $content): static
    {
        return $this->state(fn (array $attributes): array => [
            'content_text' => $content,
        ]);
    }

    /**
     * Set the post type.
     */
    public function ofType(PostType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'post_type' => $type,
        ]);
    }

    /**
     * Generate random hashtags.
     *
     * @return array<string>
     */
    private function generateHashtags(): array
    {
        $count = fake()->numberBetween(1, 5);
        $hashtags = [];

        for ($i = 0; $i < $count; $i++) {
            $hashtags[] = '#' . fake()->word();
        }

        return $hashtags;
    }

    /**
     * Generate random mentions.
     *
     * @return array<string>
     */
    private function generateMentions(): array
    {
        $count = fake()->numberBetween(1, 3);
        $mentions = [];

        for ($i = 0; $i < $count; $i++) {
            $mentions[] = '@' . fake()->userName();
        }

        return $mentions;
    }
}
