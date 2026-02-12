<?php

declare(strict_types=1);

namespace Database\Factories\Feedback;

use App\Models\Feedback\FeedbackTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for FeedbackTag model.
 *
 * @extends Factory<FeedbackTag>
 */
final class FeedbackTagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<FeedbackTag>
     */
    protected $model = FeedbackTag::class;

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
            'color' => fake()->hexColor(),
            'description' => fake()->sentence(),
            'usage_count' => 0,
        ];
    }

    /**
     * Set as popular tag with high usage count.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes): array => [
            'usage_count' => fake()->numberBetween(50, 200),
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

    /**
     * Set with a specific name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }
}
