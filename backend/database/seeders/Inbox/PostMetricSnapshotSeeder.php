<?php

declare(strict_types=1);

namespace Database\Seeders\Inbox;

use App\Enums\Content\PostTargetStatus;
use App\Models\Content\PostTarget;
use App\Models\Inbox\PostMetricSnapshot;
use Illuminate\Database\Seeder;

/**
 * Seeder for PostMetricSnapshot model.
 *
 * Creates sample metric snapshots for published posts.
 */
final class PostMetricSnapshotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get published post targets
        $publishedTargets = PostTarget::where('status', PostTargetStatus::PUBLISHED)
            ->whereNotNull('published_at')
            ->get();

        if ($publishedTargets->isEmpty()) {
            $this->command->warn('No published posts found. Skipping metric snapshots seeding.');

            return;
        }

        foreach ($publishedTargets as $target) {
            // Create 3-5 snapshots per post (simulating daily/hourly captures)
            $snapshotCount = random_int(3, 5);
            $publishedAt = $target->published_at;

            // Generate snapshots at increasing intervals
            $baseMetrics = $this->generateBaseMetrics();

            for ($i = 0; $i < $snapshotCount; $i++) {
                // Each snapshot is captured at a later time with growing metrics
                $capturedAt = $publishedAt->copy()->addHours(($i + 1) * 12);

                // Metrics grow over time with some randomness
                $growthFactor = 1 + ($i * 0.3) + fake()->randomFloat(2, 0, 0.2);

                $this->createSnapshot($target, $capturedAt, $baseMetrics, $growthFactor);
            }
        }

        $this->command->info('Post metric snapshots seeded successfully.');
    }

    /**
     * Generate base metrics for a post.
     *
     * @return array<string, int>
     */
    private function generateBaseMetrics(): array
    {
        // Determine engagement level
        $engagementLevel = fake()->randomElement(['low', 'medium', 'high']);

        return match ($engagementLevel) {
            'low' => [
                'likes' => fake()->numberBetween(5, 30),
                'comments' => fake()->numberBetween(0, 5),
                'shares' => fake()->numberBetween(0, 2),
                'impressions' => fake()->numberBetween(100, 500),
                'reach' => fake()->numberBetween(80, 400),
                'clicks' => fake()->numberBetween(0, 10),
            ],
            'medium' => [
                'likes' => fake()->numberBetween(50, 200),
                'comments' => fake()->numberBetween(5, 25),
                'shares' => fake()->numberBetween(3, 15),
                'impressions' => fake()->numberBetween(1000, 5000),
                'reach' => fake()->numberBetween(800, 4000),
                'clicks' => fake()->numberBetween(20, 80),
            ],
            'high' => [
                'likes' => fake()->numberBetween(300, 1000),
                'comments' => fake()->numberBetween(30, 100),
                'shares' => fake()->numberBetween(20, 80),
                'impressions' => fake()->numberBetween(5000, 20000),
                'reach' => fake()->numberBetween(4000, 16000),
                'clicks' => fake()->numberBetween(100, 400),
            ],
        };
    }

    /**
     * Create a metric snapshot.
     *
     * @param  array<string, int>  $baseMetrics
     */
    private function createSnapshot(
        PostTarget $target,
        \DateTimeInterface $capturedAt,
        array $baseMetrics,
        float $growthFactor
    ): PostMetricSnapshot {
        $likes = (int) ($baseMetrics['likes'] * $growthFactor);
        $comments = (int) ($baseMetrics['comments'] * $growthFactor);
        $shares = (int) ($baseMetrics['shares'] * $growthFactor);
        $impressions = (int) ($baseMetrics['impressions'] * $growthFactor);
        $reach = (int) ($baseMetrics['reach'] * $growthFactor);
        $clicks = (int) ($baseMetrics['clicks'] * $growthFactor);

        $totalEngagement = $likes + $comments + $shares;
        $engagementRate = $impressions > 0
            ? round(($totalEngagement / $impressions) * 100, 4)
            : null;

        return PostMetricSnapshot::create([
            'post_target_id' => $target->id,
            'captured_at' => $capturedAt,
            'likes_count' => $likes,
            'comments_count' => $comments,
            'shares_count' => $shares,
            'impressions_count' => $impressions,
            'reach_count' => $reach,
            'clicks_count' => $clicks,
            'engagement_rate' => $engagementRate,
            'raw_response' => [
                'source' => 'api',
                'captured_at' => $capturedAt->format('Y-m-d H:i:s'),
                'platform' => $target->platform_code,
            ],
        ]);
    }
}
