<?php

declare(strict_types=1);

namespace App\Data\Analytics;

use App\Models\Content\Post;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class TopPostData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $content_excerpt,
        public string $platform,
        public string $platform_label,
        public ?string $thumbnail_url,
        public string $published_at,
        public int $impressions,
        public int $reach,
        public int $engagements,
        public int $likes,
        public int $comments,
        public int $shares,
        public float $engagement_rate,
        public int $rank,
    ) {}

    /**
     * Create TopPostData from a Post model with metrics.
     *
     * @param array<string, mixed> $metrics
     */
    public static function fromModelWithMetrics(Post $post, array $metrics, int $rank): self
    {
        $post->loadMissing(['media']);

        $thumbnail = $post->media->first()?->thumbnail_url ?? $post->media->first()?->url;

        return new self(
            id: $post->id,
            title: $post->title ?? 'Untitled Post',
            content_excerpt: $post->content ? mb_substr(strip_tags($post->content), 0, 150) : null,
            platform: $metrics['platform'] ?? 'unknown',
            platform_label: $metrics['platform_label'] ?? 'Unknown',
            thumbnail_url: $thumbnail,
            published_at: $post->published_at?->toIso8601String() ?? $post->created_at->toIso8601String(),
            impressions: $metrics['impressions'] ?? 0,
            reach: $metrics['reach'] ?? 0,
            engagements: $metrics['engagements'] ?? 0,
            likes: $metrics['likes'] ?? 0,
            comments: $metrics['comments'] ?? 0,
            shares: $metrics['shares'] ?? 0,
            engagement_rate: $metrics['engagement_rate'] ?? 0.0,
            rank: $rank,
        );
    }

    /**
     * Transform a collection of posts with metrics to an array of TopPostData.
     *
     * @param Collection<int, array{post: Post, metrics: array<string, mixed>}> $postsWithMetrics
     * @return array<int, array<string, mixed>>
     */
    public static function fromCollection(Collection $postsWithMetrics): array
    {
        $rank = 0;

        return $postsWithMetrics->map(
            fn (array $item): array => self::fromModelWithMetrics(
                $item['post'],
                $item['metrics'],
                ++$rank
            )->toArray()
        )->values()->all();
    }
}
