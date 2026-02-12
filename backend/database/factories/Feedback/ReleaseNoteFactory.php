<?php

declare(strict_types=1);

namespace Database\Factories\Feedback;

use App\Enums\Feedback\ReleaseNoteStatus;
use App\Enums\Feedback\ReleaseType;
use App\Models\Feedback\ReleaseNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for ReleaseNote model.
 *
 * @extends Factory<ReleaseNote>
 */
final class ReleaseNoteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ReleaseNote>
     */
    protected $model = ReleaseNote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version' => fake()->unique()->numerify('#.#.#'),
            'version_name' => fake()->boolean(30) ? fake()->word() : null,
            'title' => fake()->sentence(4),
            'summary' => fake()->paragraph(),
            'content' => $this->generateContent(),
            'content_format' => 'markdown',
            'release_type' => fake()->randomElement(ReleaseType::cases()),
            'status' => ReleaseNoteStatus::DRAFT,
            'is_public' => true,
            'scheduled_at' => null,
            'published_at' => null,
        ];
    }

    /**
     * Set status to draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReleaseNoteStatus::DRAFT,
            'published_at' => null,
            'scheduled_at' => null,
        ]);
    }

    /**
     * Set status to scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReleaseNoteStatus::SCHEDULED,
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 month'),
            'published_at' => null,
        ]);
    }

    /**
     * Set status to published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReleaseNoteStatus::PUBLISHED,
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'scheduled_at' => null,
        ]);
    }

    /**
     * Set the release type.
     */
    public function ofType(ReleaseType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'release_type' => $type,
        ]);
    }

    /**
     * Set as major release.
     */
    public function major(): static
    {
        return $this->state(fn (array $attributes): array => [
            'release_type' => ReleaseType::MAJOR,
            'version' => fake()->unique()->numerify('#.0.0'),
        ]);
    }

    /**
     * Set as minor release.
     */
    public function minor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'release_type' => ReleaseType::MINOR,
            'version' => fake()->unique()->numerify('#.#.0'),
        ]);
    }

    /**
     * Set as patch release.
     */
    public function patch(): static
    {
        return $this->state(fn (array $attributes): array => [
            'release_type' => ReleaseType::PATCH,
        ]);
    }

    /**
     * Set with a specific version.
     */
    public function withVersion(string $version): static
    {
        return $this->state(fn (array $attributes): array => [
            'version' => $version,
        ]);
    }

    /**
     * Generate sample content.
     */
    private function generateContent(): string
    {
        $faker = fake();

        return <<<MARKDOWN
## What's New

{$faker->paragraph(2)}

## Improvements

- {$faker->sentence()}
- {$faker->sentence()}
- {$faker->sentence()}

## Bug Fixes

- Fixed {$faker->sentence()}
- Resolved {$faker->sentence()}
MARKDOWN;
    }
}
