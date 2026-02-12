<?php

declare(strict_types=1);

namespace Database\Factories\KnowledgeBase;

use App\Models\KnowledgeBase\KBTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for KBTag model.
 *
 * @extends Factory<KBTag>
 */
final class KBTagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<KBTag>
     */
    protected $model = KBTag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'usage_count' => 0,
        ];
    }

    /**
     * Set as a popular tag with high usage count.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes): array => [
            'usage_count' => fake()->numberBetween(50, 500),
        ]);
    }

    /**
     * Set with a specific usage count.
     */
    public function withUsageCount(int $count): static
    {
        return $this->state(fn (array $attributes): array => [
            'usage_count' => $count,
        ]);
    }
}
