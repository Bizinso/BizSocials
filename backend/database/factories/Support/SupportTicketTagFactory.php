<?php

declare(strict_types=1);

namespace Database\Factories\Support;

use App\Models\Support\SupportTicketTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for SupportTicketTag model.
 *
 * @extends Factory<SupportTicketTag>
 */
final class SupportTicketTagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<SupportTicketTag>
     */
    protected $model = SupportTicketTag::class;

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
