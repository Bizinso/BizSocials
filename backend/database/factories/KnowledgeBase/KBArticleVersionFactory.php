<?php

declare(strict_types=1);

namespace Database\Factories\KnowledgeBase;

use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBArticleVersion;
use App\Models\Platform\SuperAdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for KBArticleVersion model.
 *
 * @extends Factory<KBArticleVersion>
 */
final class KBArticleVersionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<KBArticleVersion>
     */
    protected $model = KBArticleVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => KBArticle::factory(),
            'version' => 1,
            'title' => fake()->sentence(6),
            'content' => fake()->paragraphs(5, true),
            'change_summary' => fake()->sentence(),
            'changed_by' => SuperAdminUser::factory(),
        ];
    }

    /**
     * Associate with a specific article.
     */
    public function forArticle(KBArticle $article): static
    {
        return $this->state(fn (array $attributes): array => [
            'article_id' => $article->id,
            'title' => $article->title,
            'content' => $article->content,
        ]);
    }

    /**
     * Set the author of the version.
     */
    public function byAuthor(SuperAdminUser $author): static
    {
        return $this->state(fn (array $attributes): array => [
            'changed_by' => $author->id,
        ]);
    }

    /**
     * Set a specific version number.
     */
    public function withVersion(int $version): static
    {
        return $this->state(fn (array $attributes): array => [
            'version' => $version,
        ]);
    }

    /**
     * Set a specific change summary.
     */
    public function withChangeSummary(string $summary): static
    {
        return $this->state(fn (array $attributes): array => [
            'change_summary' => $summary,
        ]);
    }
}
