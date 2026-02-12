<?php

declare(strict_types=1);

namespace Database\Factories\Feedback;

use App\Enums\Feedback\ChangeType;
use App\Models\Feedback\ReleaseNote;
use App\Models\Feedback\ReleaseNoteItem;
use App\Models\Feedback\RoadmapItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for ReleaseNoteItem model.
 *
 * @extends Factory<ReleaseNoteItem>
 */
final class ReleaseNoteItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ReleaseNoteItem>
     */
    protected $model = ReleaseNoteItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'release_note_id' => ReleaseNote::factory(),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'change_type' => fake()->randomElement(ChangeType::cases()),
            'roadmap_item_id' => null,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }

    /**
     * Set as new feature.
     */
    public function newFeature(): static
    {
        return $this->state(fn (array $attributes): array => [
            'change_type' => ChangeType::NEW_FEATURE,
        ]);
    }

    /**
     * Set as improvement.
     */
    public function improvement(): static
    {
        return $this->state(fn (array $attributes): array => [
            'change_type' => ChangeType::IMPROVEMENT,
        ]);
    }

    /**
     * Set as bug fix.
     */
    public function bugFix(): static
    {
        return $this->state(fn (array $attributes): array => [
            'change_type' => ChangeType::BUG_FIX,
        ]);
    }

    /**
     * Set as security fix.
     */
    public function security(): static
    {
        return $this->state(fn (array $attributes): array => [
            'change_type' => ChangeType::SECURITY,
        ]);
    }

    /**
     * Set as breaking change.
     */
    public function breakingChange(): static
    {
        return $this->state(fn (array $attributes): array => [
            'change_type' => ChangeType::BREAKING_CHANGE,
        ]);
    }

    /**
     * Set the change type.
     */
    public function ofType(ChangeType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'change_type' => $type,
        ]);
    }

    /**
     * Set for a specific release note.
     */
    public function forRelease(ReleaseNote $releaseNote): static
    {
        return $this->state(fn (array $attributes): array => [
            'release_note_id' => $releaseNote->id,
        ]);
    }

    /**
     * Link to a roadmap item.
     */
    public function linkedTo(RoadmapItem $roadmapItem): static
    {
        return $this->state(fn (array $attributes): array => [
            'roadmap_item_id' => $roadmapItem->id,
        ]);
    }

    /**
     * Set with a specific sort order.
     */
    public function withOrder(int $order): static
    {
        return $this->state(fn (array $attributes): array => [
            'sort_order' => $order,
        ]);
    }
}
