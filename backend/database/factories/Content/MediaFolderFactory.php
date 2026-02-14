<?php

declare(strict_types=1);

namespace Database\Factories\Content;

use App\Models\Content\MediaFolder;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MediaFolder>
 */
final class MediaFolderFactory extends Factory
{
    protected $model = MediaFolder::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'workspace_id' => Workspace::factory(),
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => fake()->optional()->hexColor(),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
