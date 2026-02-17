<?php

declare(strict_types=1);

namespace Database\Factories\Content;

use App\Models\Content\MediaLibraryItem;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaLibraryItem>
 */
final class MediaLibraryItemFactory extends Factory
{
    protected $model = MediaLibraryItem::class;

    public function definition(): array
    {
        $mimeTypes = ['image/jpeg', 'image/png', 'video/mp4', 'application/pdf'];
        $mimeType = fake()->randomElement($mimeTypes);
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'video/mp4' => 'mp4',
            'application/pdf' => 'pdf',
            default => 'jpg',
        };

        $fileName = fake()->uuid() . '.' . $extension;
        $path = 'media/' . fake()->uuid() . '/' . $fileName;

        return [
            'workspace_id' => Workspace::factory(),
            'uploaded_by_user_id' => User::factory(),
            'folder_id' => null,
            'file_name' => $fileName,
            'original_name' => fake()->word() . '.' . $extension,
            'mime_type' => $mimeType,
            'file_size' => fake()->numberBetween(1000, 10000000),
            'disk' => 'public',
            'path' => $path,
            'url' => 'https://cdn.example.com/' . $path,
            'thumbnail_url' => str_starts_with($mimeType, 'image') ? 'https://cdn.example.com/thumbs/' . $fileName : null,
            'alt_text' => fake()->optional()->sentence(),
            'width' => str_starts_with($mimeType, 'image') ? fake()->numberBetween(100, 4000) : null,
            'height' => str_starts_with($mimeType, 'image') ? fake()->numberBetween(100, 4000) : null,
            'duration' => $mimeType === 'video/mp4' ? fake()->numberBetween(10, 600) : null,
            'tags' => fake()->optional()->randomElements(['nature', 'business', 'tech', 'people'], fake()->numberBetween(1, 3)),
            'metadata' => [],
            'usage_count' => fake()->numberBetween(0, 100),
            'last_used_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
