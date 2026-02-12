<?php

declare(strict_types=1);

namespace Database\Factories\KnowledgeBase;

use App\Enums\KnowledgeBase\KBArticleStatus;
use App\Enums\KnowledgeBase\KBArticleType;
use App\Enums\KnowledgeBase\KBContentFormat;
use App\Enums\KnowledgeBase\KBDifficultyLevel;
use App\Enums\KnowledgeBase\KBVisibility;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBCategory;
use App\Models\Platform\SuperAdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for KBArticle model.
 *
 * @extends Factory<KBArticle>
 */
final class KBArticleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<KBArticle>
     */
    protected $model = KBArticle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'category_id' => KBCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->paragraph(2),
            'content' => $this->generateMarkdownContent(),
            'content_format' => KBContentFormat::MARKDOWN,
            'featured_image' => fake()->boolean(30) ? fake()->imageUrl(1200, 600, 'business') : null,
            'video_url' => fake()->boolean(20) ? 'https://www.youtube.com/watch?v=' . Str::random(11) : null,
            'video_duration' => fake()->boolean(20) ? fake()->numberBetween(60, 1800) : null,
            'article_type' => fake()->randomElement(KBArticleType::cases()),
            'difficulty_level' => KBDifficultyLevel::BEGINNER,
            'status' => KBArticleStatus::DRAFT,
            'is_featured' => fake()->boolean(10),
            'is_public' => true,
            'visibility' => KBVisibility::ALL,
            'allowed_plans' => null,
            'meta_title' => Str::limit($title, 60),
            'meta_description' => Str::limit(fake()->paragraph(), 155),
            'meta_keywords' => fake()->words(5),
            'version' => 1,
            'author_id' => SuperAdminUser::factory(),
            'last_edited_by' => null,
            'view_count' => fake()->numberBetween(0, 5000),
            'helpful_count' => fake()->numberBetween(0, 100),
            'not_helpful_count' => fake()->numberBetween(0, 20),
            'published_at' => null,
        ];
    }

    /**
     * Set the status to draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => KBArticleStatus::DRAFT,
            'published_at' => null,
        ]);
    }

    /**
     * Set the status to published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => KBArticleStatus::PUBLISHED,
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Set the status to archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => KBArticleStatus::ARCHIVED,
            'published_at' => fake()->dateTimeBetween('-2 years', '-6 months'),
        ]);
    }

    /**
     * Set as featured article.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_featured' => true,
            'status' => KBArticleStatus::PUBLISHED,
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Set the article type.
     */
    public function ofType(KBArticleType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'article_type' => $type,
        ]);
    }

    /**
     * Set article type to How-To.
     */
    public function howTo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'article_type' => KBArticleType::HOW_TO,
        ]);
    }

    /**
     * Set article type to FAQ.
     */
    public function faq(): static
    {
        return $this->state(fn (array $attributes): array => [
            'article_type' => KBArticleType::FAQ,
        ]);
    }

    /**
     * Set the author.
     */
    public function byAuthor(SuperAdminUser $author): static
    {
        return $this->state(fn (array $attributes): array => [
            'author_id' => $author->id,
        ]);
    }

    /**
     * Set the category.
     */
    public function forCategory(KBCategory $category): static
    {
        return $this->state(fn (array $attributes): array => [
            'category_id' => $category->id,
        ]);
    }

    /**
     * Set the difficulty level.
     */
    public function withDifficulty(KBDifficultyLevel $level): static
    {
        return $this->state(fn (array $attributes): array => [
            'difficulty_level' => $level,
        ]);
    }

    /**
     * Set the content format.
     */
    public function withFormat(KBContentFormat $format): static
    {
        return $this->state(fn (array $attributes): array => [
            'content_format' => $format,
        ]);
    }

    /**
     * Set high view count for popular article.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes): array => [
            'view_count' => fake()->numberBetween(5000, 50000),
            'helpful_count' => fake()->numberBetween(100, 1000),
        ]);
    }

    /**
     * Generate sample markdown content.
     */
    private function generateMarkdownContent(): string
    {
        $sections = [];

        $sections[] = "## Overview\n\n" . fake()->paragraph(3);

        $sections[] = "## Getting Started\n\n" . fake()->paragraph(2) . "\n\n";
        $sections[] = "### Step 1: " . fake()->sentence(4) . "\n\n" . fake()->paragraph(2);
        $sections[] = "### Step 2: " . fake()->sentence(4) . "\n\n" . fake()->paragraph(2);
        $sections[] = "### Step 3: " . fake()->sentence(4) . "\n\n" . fake()->paragraph(2);

        $sections[] = "## Key Features\n\n";
        $sections[] = "- " . fake()->sentence(8);
        $sections[] = "- " . fake()->sentence(8);
        $sections[] = "- " . fake()->sentence(8);
        $sections[] = "- " . fake()->sentence(8);

        $sections[] = "\n\n## Conclusion\n\n" . fake()->paragraph(2);

        return implode("\n", $sections);
    }
}
