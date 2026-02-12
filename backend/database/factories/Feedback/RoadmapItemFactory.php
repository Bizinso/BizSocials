<?php

declare(strict_types=1);

namespace Database\Factories\Feedback;

use App\Enums\Feedback\AdminPriority;
use App\Enums\Feedback\RoadmapCategory;
use App\Enums\Feedback\RoadmapStatus;
use App\Models\Feedback\RoadmapItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for RoadmapItem model.
 *
 * @extends Factory<RoadmapItem>
 */
final class RoadmapItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<RoadmapItem>
     */
    protected $model = RoadmapItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'detailed_description' => fake()->paragraphs(3, true),
            'category' => fake()->randomElement(RoadmapCategory::cases()),
            'status' => RoadmapStatus::CONSIDERING,
            'quarter' => 'Q' . fake()->numberBetween(1, 4) . ' ' . date('Y'),
            'target_date' => fake()->dateTimeBetween('now', '+1 year'),
            'shipped_date' => null,
            'priority' => AdminPriority::MEDIUM,
            'progress_percentage' => 0,
            'is_public' => true,
            'linked_feedback_count' => 0,
            'total_votes' => 0,
        ];
    }

    /**
     * Set status to considering.
     */
    public function considering(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RoadmapStatus::CONSIDERING,
            'progress_percentage' => 0,
        ]);
    }

    /**
     * Set status to planned.
     */
    public function planned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RoadmapStatus::PLANNED,
            'progress_percentage' => 0,
        ]);
    }

    /**
     * Set status to in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RoadmapStatus::IN_PROGRESS,
            'progress_percentage' => fake()->numberBetween(10, 80),
        ]);
    }

    /**
     * Set status to beta.
     */
    public function beta(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RoadmapStatus::BETA,
            'progress_percentage' => fake()->numberBetween(80, 95),
        ]);
    }

    /**
     * Set status to shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RoadmapStatus::SHIPPED,
            'progress_percentage' => 100,
            'shipped_date' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Set status to cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RoadmapStatus::CANCELLED,
            'is_public' => false,
        ]);
    }

    /**
     * Set the category.
     */
    public function inCategory(RoadmapCategory $category): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => $category,
        ]);
    }

    /**
     * Set the priority.
     */
    public function withPriority(AdminPriority $priority): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => $priority,
        ]);
    }

    /**
     * Set as private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_public' => false,
        ]);
    }

    /**
     * Set for a specific quarter.
     */
    public function forQuarter(string $quarter): static
    {
        return $this->state(fn (array $attributes): array => [
            'quarter' => $quarter,
        ]);
    }
}
