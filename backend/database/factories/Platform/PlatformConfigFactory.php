<?php

declare(strict_types=1);

namespace Database\Factories\Platform;

use App\Enums\Platform\ConfigCategory;
use App\Models\Platform\PlatformConfig;
use App\Models\Platform\SuperAdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for PlatformConfig model.
 *
 * @extends Factory<PlatformConfig>
 */
final class PlatformConfigFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PlatformConfig>
     */
    protected $model = PlatformConfig::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'config.' . fake()->unique()->slug(2),
            'value' => ['value' => fake()->randomElement([
                fake()->word(),
                fake()->numberBetween(1, 100),
                fake()->boolean(),
                fake()->url(),
            ])],
            'category' => fake()->randomElement(ConfigCategory::cases()),
            'description' => fake()->optional(0.7)->sentence(),
            'is_sensitive' => false,
            'updated_by' => null,
        ];
    }

    /**
     * Set a specific category.
     */
    public function category(ConfigCategory $category): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => $category,
        ]);
    }

    /**
     * Indicate the config is sensitive.
     */
    public function sensitive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_sensitive' => true,
        ]);
    }

    /**
     * Set a specific key-value pair.
     */
    public function withKeyValue(string $key, mixed $value): static
    {
        return $this->state(fn (array $attributes): array => [
            'key' => $key,
            'value' => is_array($value) ? $value : ['value' => $value],
        ]);
    }

    /**
     * Set the updater.
     */
    public function updatedBy(SuperAdminUser $admin): static
    {
        return $this->state(fn (array $attributes): array => [
            'updated_by' => $admin->id,
        ]);
    }

    /**
     * Create a general config.
     */
    public function general(): static
    {
        return $this->category(ConfigCategory::GENERAL);
    }

    /**
     * Create a security config.
     */
    public function security(): static
    {
        return $this->category(ConfigCategory::SECURITY)->sensitive();
    }

    /**
     * Create an integration config.
     */
    public function integration(): static
    {
        return $this->category(ConfigCategory::INTEGRATIONS);
    }

    /**
     * Create a limits config.
     */
    public function limits(): static
    {
        return $this->category(ConfigCategory::LIMITS);
    }
}
