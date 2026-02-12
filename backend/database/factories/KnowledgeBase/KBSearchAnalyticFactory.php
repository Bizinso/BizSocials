<?php

declare(strict_types=1);

namespace Database\Factories\KnowledgeBase;

use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBSearchAnalytic;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for KBSearchAnalytic model.
 *
 * @extends Factory<KBSearchAnalytic>
 */
final class KBSearchAnalyticFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<KBSearchAnalytic>
     */
    protected $model = KBSearchAnalytic::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $searchQuery = fake()->words(fake()->numberBetween(1, 5), true);

        return [
            'search_query' => $searchQuery,
            'search_query_normalized' => Str::lower($searchQuery),
            'results_count' => fake()->numberBetween(0, 50),
            'clicked_article_id' => null,
            'search_successful' => null,
            'user_id' => fake()->boolean(60) ? User::factory() : null,
            'tenant_id' => fake()->boolean(60) ? Tenant::factory() : null,
            'session_id' => Str::uuid()->toString(),
        ];
    }

    /**
     * Set as successful search.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes): array => [
            'results_count' => fake()->numberBetween(1, 50),
            'search_successful' => true,
        ]);
    }

    /**
     * Set as search with no results.
     */
    public function noResults(): static
    {
        return $this->state(fn (array $attributes): array => [
            'results_count' => 0,
            'search_successful' => false,
        ]);
    }

    /**
     * Set with a clicked article.
     */
    public function withClick(KBArticle $article): static
    {
        return $this->state(fn (array $attributes): array => [
            'clicked_article_id' => $article->id,
            'search_successful' => true,
            'results_count' => fake()->numberBetween(1, 50),
        ]);
    }

    /**
     * Associate with a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Associate with a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes): array => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Set a specific search query.
     */
    public function withQuery(string $query): static
    {
        return $this->state(fn (array $attributes): array => [
            'search_query' => $query,
            'search_query_normalized' => Str::lower($query),
        ]);
    }
}
