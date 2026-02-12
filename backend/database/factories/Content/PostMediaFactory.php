<?php

declare(strict_types=1);

namespace Database\Factories\Content;

use App\Enums\Content\MediaProcessingStatus;
use App\Enums\Content\MediaType;
use App\Models\Content\Post;
use App\Models\Content\PostMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for PostMedia model.
 *
 * @extends Factory<PostMedia>
 */
final class PostMediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PostMedia>
     */
    protected $model = PostMedia::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(MediaType::cases());

        return [
            'post_id' => Post::factory(),
            'type' => $type,
            'file_name' => $this->generateFileName($type),
            'file_size' => $this->generateFileSize($type),
            'mime_type' => $this->generateMimeType($type),
            'storage_path' => 'media/' . fake()->uuid() . '/' . fake()->slug(),
            'cdn_url' => fake()->boolean(60) ? fake()->url() : null,
            'thumbnail_url' => fake()->boolean(50) ? fake()->url() : null,
            'dimensions' => $type !== MediaType::DOCUMENT ? $this->generateDimensions() : null,
            'duration_seconds' => $type === MediaType::VIDEO ? fake()->numberBetween(5, 180) : null,
            'alt_text' => fake()->boolean(70) ? fake()->sentence(5) : null,
            'sort_order' => 0,
            'processing_status' => MediaProcessingStatus::COMPLETED,
            'metadata' => null,
        ];
    }

    /**
     * Set the media type to image.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MediaType::IMAGE,
            'file_name' => 'image_' . fake()->uuid() . '.jpg',
            'file_size' => fake()->numberBetween(100000, 5000000),
            'mime_type' => 'image/jpeg',
            'dimensions' => $this->generateDimensions(),
            'duration_seconds' => null,
        ]);
    }

    /**
     * Set the media type to video.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MediaType::VIDEO,
            'file_name' => 'video_' . fake()->uuid() . '.mp4',
            'file_size' => fake()->numberBetween(10000000, 100000000),
            'mime_type' => 'video/mp4',
            'dimensions' => $this->generateDimensions(),
            'duration_seconds' => fake()->numberBetween(5, 180),
        ]);
    }

    /**
     * Set the media type to gif.
     */
    public function gif(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MediaType::GIF,
            'file_name' => 'animation_' . fake()->uuid() . '.gif',
            'file_size' => fake()->numberBetween(500000, 10000000),
            'mime_type' => 'image/gif',
            'dimensions' => $this->generateDimensions(),
            'duration_seconds' => null,
        ]);
    }

    /**
     * Set the media type to document.
     */
    public function document(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MediaType::DOCUMENT,
            'file_name' => 'document_' . fake()->uuid() . '.pdf',
            'file_size' => fake()->numberBetween(100000, 50000000),
            'mime_type' => 'application/pdf',
            'dimensions' => null,
            'duration_seconds' => null,
        ]);
    }

    /**
     * Set the processing status to pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'processing_status' => MediaProcessingStatus::PENDING,
            'cdn_url' => null,
            'thumbnail_url' => null,
        ]);
    }

    /**
     * Set the processing status to processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'processing_status' => MediaProcessingStatus::PROCESSING,
            'cdn_url' => null,
            'thumbnail_url' => null,
        ]);
    }

    /**
     * Set the processing status to completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'processing_status' => MediaProcessingStatus::COMPLETED,
            'cdn_url' => fake()->url(),
            'thumbnail_url' => fake()->url(),
        ]);
    }

    /**
     * Set the processing status to failed.
     */
    public function processingFailed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'processing_status' => MediaProcessingStatus::FAILED,
            'cdn_url' => null,
            'thumbnail_url' => null,
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
     * Set the sort order.
     */
    public function withSortOrder(int $order): static
    {
        return $this->state(fn (array $attributes): array => [
            'sort_order' => $order,
        ]);
    }

    /**
     * Generate a file name based on media type.
     */
    private function generateFileName(MediaType $type): string
    {
        $uuid = fake()->uuid();

        return match ($type) {
            MediaType::IMAGE => "image_{$uuid}.jpg",
            MediaType::VIDEO => "video_{$uuid}.mp4",
            MediaType::GIF => "animation_{$uuid}.gif",
            MediaType::DOCUMENT => "document_{$uuid}.pdf",
        };
    }

    /**
     * Generate a file size based on media type.
     */
    private function generateFileSize(MediaType $type): int
    {
        return match ($type) {
            MediaType::IMAGE => fake()->numberBetween(100000, 5000000),
            MediaType::VIDEO => fake()->numberBetween(10000000, 100000000),
            MediaType::GIF => fake()->numberBetween(500000, 10000000),
            MediaType::DOCUMENT => fake()->numberBetween(100000, 50000000),
        };
    }

    /**
     * Generate a MIME type based on media type.
     */
    private function generateMimeType(MediaType $type): string
    {
        return match ($type) {
            MediaType::IMAGE => fake()->randomElement(['image/jpeg', 'image/png', 'image/webp']),
            MediaType::VIDEO => fake()->randomElement(['video/mp4', 'video/quicktime']),
            MediaType::GIF => 'image/gif',
            MediaType::DOCUMENT => 'application/pdf',
        };
    }

    /**
     * Generate random dimensions.
     *
     * @return array{width: int, height: int}
     */
    private function generateDimensions(): array
    {
        $aspectRatios = [
            [1080, 1080], // Square
            [1080, 1920], // Portrait
            [1920, 1080], // Landscape
            [1200, 628],  // LinkedIn
            [1080, 566],  // Twitter
        ];

        $dimensions = fake()->randomElement($aspectRatios);

        return [
            'width' => $dimensions[0],
            'height' => $dimensions[1],
        ];
    }
}
