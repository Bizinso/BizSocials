<?php

declare(strict_types=1);

namespace Database\Factories\Content;

use App\Enums\Content\PostTargetStatus;
use App\Enums\Social\SocialPlatform;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for PostTarget model.
 *
 * @extends Factory<PostTarget>
 */
final class PostTargetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PostTarget>
     */
    protected $model = PostTarget::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $platform = fake()->randomElement(SocialPlatform::cases());

        return [
            'post_id' => Post::factory(),
            'social_account_id' => SocialAccount::factory(),
            'platform_code' => $platform->value,
            'content_override' => fake()->boolean(30) ? fake()->paragraph() : null,
            'status' => PostTargetStatus::PENDING,
            'external_post_id' => null,
            'external_post_url' => null,
            'published_at' => null,
            'error_code' => null,
            'error_message' => null,
            'retry_count' => 0,
            'metrics' => null,
        ];
    }

    /**
     * Set the status to pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostTargetStatus::PENDING,
            'external_post_id' => null,
            'external_post_url' => null,
            'published_at' => null,
            'error_code' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Set the status to publishing.
     */
    public function publishing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostTargetStatus::PUBLISHING,
            'external_post_id' => null,
            'external_post_url' => null,
            'published_at' => null,
        ]);
    }

    /**
     * Set the status to published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostTargetStatus::PUBLISHED,
            'external_post_id' => (string) fake()->numberBetween(1000000000, 9999999999),
            'external_post_url' => fake()->url(),
            'published_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'error_code' => null,
            'error_message' => null,
            'metrics' => $this->generateMetrics(),
        ]);
    }

    /**
     * Set the status to failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostTargetStatus::FAILED,
            'external_post_id' => null,
            'external_post_url' => null,
            'published_at' => null,
            'error_code' => fake()->randomElement(['AUTH_ERROR', 'RATE_LIMIT', 'CONTENT_POLICY', 'API_ERROR']),
            'error_message' => fake()->sentence(),
            'retry_count' => fake()->numberBetween(1, 3),
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
     * Associate with a specific social account.
     */
    public function forSocialAccount(SocialAccount $socialAccount): static
    {
        return $this->state(fn (array $attributes): array => [
            'social_account_id' => $socialAccount->id,
            'platform_code' => $socialAccount->platform->value,
        ]);
    }

    /**
     * Set the platform.
     */
    public function forPlatform(SocialPlatform $platform): static
    {
        return $this->state(fn (array $attributes): array => [
            'platform_code' => $platform->value,
        ]);
    }

    /**
     * Generate sample engagement metrics.
     *
     * @return array<string, int>
     */
    private function generateMetrics(): array
    {
        return [
            'likes' => fake()->numberBetween(0, 500),
            'comments' => fake()->numberBetween(0, 50),
            'shares' => fake()->numberBetween(0, 30),
            'impressions' => fake()->numberBetween(100, 10000),
            'clicks' => fake()->numberBetween(0, 100),
        ];
    }
}
