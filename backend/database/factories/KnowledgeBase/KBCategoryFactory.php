<?php

declare(strict_types=1);

namespace Database\Factories\KnowledgeBase;

use App\Enums\KnowledgeBase\KBVisibility;
use App\Models\KnowledgeBase\KBCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for KBCategory model.
 *
 * @extends Factory<KBCategory>
 */
final class KBCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<KBCategory>
     */
    protected $model = KBCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'parent_id' => null,
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'icon' => fake()->randomElement(['book', 'question', 'cog', 'star', 'folder', 'document']),
            'color' => fake()->hexColor(),
            'is_public' => true,
            'visibility' => KBVisibility::ALL,
            'allowed_plans' => null,
            'sort_order' => fake()->numberBetween(0, 100),
            'article_count' => 0,
        ];
    }

    /**
     * Set as a top-level category (no parent).
     */
    public function topLevel(): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => null,
        ]);
    }

    /**
     * Set as a child of another category.
     */
    public function childOf(KBCategory $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent->id,
        ]);
    }

    /**
     * Set as public category.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_public' => true,
            'visibility' => KBVisibility::ALL,
        ]);
    }

    /**
     * Set as private category.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_public' => false,
            'visibility' => KBVisibility::AUTHENTICATED,
        ]);
    }

    /**
     * Set with a specific icon.
     */
    public function withIcon(string $icon): static
    {
        return $this->state(fn (array $attributes): array => [
            'icon' => $icon,
        ]);
    }

    /**
     * Set with specific plans visibility.
     *
     * @param  array<string>  $planIds
     */
    public function forPlans(array $planIds): static
    {
        return $this->state(fn (array $attributes): array => [
            'visibility' => KBVisibility::SPECIFIC_PLANS,
            'allowed_plans' => $planIds,
        ]);
    }

    /**
     * Set specific article count.
     */
    public function withArticleCount(int $count): static
    {
        return $this->state(fn (array $attributes): array => [
            'article_count' => $count,
        ]);
    }
}
