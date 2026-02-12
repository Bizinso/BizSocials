<?php

declare(strict_types=1);

namespace Database\Factories\Inbox;

use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Inbox\PostMetricSnapshot;
use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for PostMetricSnapshot model.
 *
 * @extends Factory<PostMetricSnapshot>
 */
final class PostMetricSnapshotFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PostMetricSnapshot>
     */
    protected $model = PostMetricSnapshot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $likes = fake()->numberBetween(0, 500);
        $comments = fake()->numberBetween(0, 50);
        $shares = fake()->numberBetween(0, 30);
        $impressions = fake()->numberBetween(100, 10000);
        $reach = (int) ($impressions * fake()->randomFloat(2, 0.6, 0.9));
        $clicks = fake()->numberBetween(0, 100);

        $totalEngagement = $likes + $comments + $shares;
        $engagementRate = $impressions > 0
            ? round(($totalEngagement / $impressions) * 100, 4)
            : null;

        return [
            'post_target_id' => PostTarget::factory(),
            'captured_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'likes_count' => $likes,
            'comments_count' => $comments,
            'shares_count' => $shares,
            'impressions_count' => $impressions,
            'reach_count' => $reach,
            'clicks_count' => $clicks,
            'engagement_rate' => $engagementRate,
            'raw_response' => null,
        ];
    }

    /**
     * Set low engagement metrics.
     */
    public function lowEngagement(): static
    {
        return $this->state(fn (array $attributes): array => [
            'likes_count' => fake()->numberBetween(0, 10),
            'comments_count' => fake()->numberBetween(0, 2),
            'shares_count' => 0,
            'impressions_count' => fake()->numberBetween(50, 200),
            'reach_count' => fake()->numberBetween(30, 150),
            'clicks_count' => fake()->numberBetween(0, 5),
            'engagement_rate' => fake()->randomFloat(4, 0.5, 2.0),
        ]);
    }

    /**
     * Set medium engagement metrics.
     */
    public function mediumEngagement(): static
    {
        return $this->state(fn (array $attributes): array => [
            'likes_count' => fake()->numberBetween(50, 200),
            'comments_count' => fake()->numberBetween(10, 30),
            'shares_count' => fake()->numberBetween(5, 15),
            'impressions_count' => fake()->numberBetween(1000, 5000),
            'reach_count' => fake()->numberBetween(800, 4000),
            'clicks_count' => fake()->numberBetween(20, 80),
            'engagement_rate' => fake()->randomFloat(4, 3.0, 6.0),
        ]);
    }

    /**
     * Set high engagement metrics.
     */
    public function highEngagement(): static
    {
        return $this->state(fn (array $attributes): array => [
            'likes_count' => fake()->numberBetween(500, 2000),
            'comments_count' => fake()->numberBetween(50, 200),
            'shares_count' => fake()->numberBetween(30, 100),
            'impressions_count' => fake()->numberBetween(10000, 50000),
            'reach_count' => fake()->numberBetween(8000, 40000),
            'clicks_count' => fake()->numberBetween(200, 800),
            'engagement_rate' => fake()->randomFloat(4, 7.0, 12.0),
        ]);
    }

    /**
     * Set viral engagement metrics.
     */
    public function viralEngagement(): static
    {
        return $this->state(fn (array $attributes): array => [
            'likes_count' => fake()->numberBetween(5000, 50000),
            'comments_count' => fake()->numberBetween(500, 5000),
            'shares_count' => fake()->numberBetween(1000, 10000),
            'impressions_count' => fake()->numberBetween(100000, 1000000),
            'reach_count' => fake()->numberBetween(80000, 800000),
            'clicks_count' => fake()->numberBetween(5000, 50000),
            'engagement_rate' => fake()->randomFloat(4, 15.0, 25.0),
        ]);
    }

    /**
     * Associate with a specific post target.
     */
    public function forPostTarget(PostTarget $postTarget): static
    {
        return $this->state(fn (array $attributes): array => [
            'post_target_id' => $postTarget->id,
        ]);
    }

    /**
     * Set captured at timestamp.
     */
    public function capturedAt(\DateTimeInterface $datetime): static
    {
        return $this->state(fn (array $attributes): array => [
            'captured_at' => $datetime,
        ]);
    }

    /**
     * Include raw response data.
     *
     * @param  array<string, mixed>  $response
     */
    public function withRawResponse(array $response): static
    {
        return $this->state(fn (array $attributes): array => [
            'raw_response' => $response,
        ]);
    }

    /**
     * Associate with a specific workspace.
     * Creates a PostTarget with Post and SocialAccount for the workspace.
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(function (array $attributes) use ($workspace): array {
            $socialAccount = SocialAccount::factory()->forWorkspace($workspace)->create();
            $post = Post::factory()->forWorkspace($workspace)->create();
            $postTarget = PostTarget::factory()
                ->forPost($post)
                ->forSocialAccount($socialAccount)
                ->published()
                ->create();

            return [
                'post_target_id' => $postTarget->id,
            ];
        });
    }
}
