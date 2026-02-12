<?php

declare(strict_types=1);

namespace Database\Factories\Support;

use App\Models\Support\SupportCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for SupportCategory model.
 *
 * @extends Factory<SupportCategory>
 */
final class SupportCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<SupportCategory>
     */
    protected $model = SupportCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(['folder', 'cog', 'question', 'chat', 'document']),
            'parent_id' => null,
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
            'ticket_count' => 0,
        ];
    }

    /**
     * Set the category as active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    /**
     * Set the category as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Set the category as a root category (no parent).
     */
    public function root(): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => null,
        ]);
    }

    /**
     * Set the category as a child of another category.
     */
    public function child(SupportCategory $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent->id,
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

    /**
     * Set with a specific ticket count.
     */
    public function withTicketCount(int $count): static
    {
        return $this->state(fn (array $attributes): array => [
            'ticket_count' => $count,
        ]);
    }
}
