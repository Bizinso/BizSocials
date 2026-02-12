<?php

declare(strict_types=1);

namespace Database\Factories\Support;

use App\Enums\Support\CannedResponseCategory;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportCannedResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for SupportCannedResponse model.
 *
 * @extends Factory<SupportCannedResponse>
 */
final class SupportCannedResponseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<SupportCannedResponse>
     */
    protected $model = SupportCannedResponse::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'shortcut' => fake()->unique()->lexify('??-????'),
            'content' => fake()->paragraphs(2, true),
            'category' => CannedResponseCategory::GENERAL,
            'created_by' => SuperAdminUser::factory(),
            'is_shared' => true,
            'usage_count' => 0,
        ];
    }

    /**
     * Set as shared.
     */
    public function shared(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_shared' => true,
        ]);
    }

    /**
     * Set as personal (not shared).
     */
    public function personal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_shared' => false,
        ]);
    }

    /**
     * Set the category to greeting.
     */
    public function greeting(): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => CannedResponseCategory::GREETING,
            'content' => "Hello {name},\n\nThank you for reaching out to our support team. I'm happy to help you today.",
        ]);
    }

    /**
     * Set the category to billing.
     */
    public function billing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => CannedResponseCategory::BILLING,
        ]);
    }

    /**
     * Set the category to technical.
     */
    public function technical(): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => CannedResponseCategory::TECHNICAL,
        ]);
    }

    /**
     * Set the category to closing.
     */
    public function closing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => CannedResponseCategory::CLOSING,
            'content' => "If you have any further questions, please don't hesitate to reach out. Have a great day!",
        ]);
    }

    /**
     * Set for a specific category.
     */
    public function inCategory(CannedResponseCategory $category): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => $category,
        ]);
    }

    /**
     * Set the creator.
     */
    public function createdBy(SuperAdminUser $admin): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_by' => $admin->id,
        ]);
    }

    /**
     * Set as popular.
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
}
