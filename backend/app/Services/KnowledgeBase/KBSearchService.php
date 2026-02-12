<?php

declare(strict_types=1);

namespace App\Services\KnowledgeBase;

use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBSearchAnalytic;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

final class KBSearchService extends BaseService
{
    /**
     * Search published articles.
     *
     * @param array<string, mixed> $filters
     */
    public function search(string $query, array $filters = []): LengthAwarePaginator
    {
        $normalizedQuery = $this->normalizeQuery($query);

        $articlesQuery = KBArticle::published()
            ->with(['category'])
            ->where(function ($q) use ($query, $normalizedQuery) {
                // Search in title (highest priority)
                $q->where('title', 'like', "%{$query}%")
                    // Search in excerpt
                    ->orWhere('excerpt', 'like', "%{$query}%")
                    // Search in content
                    ->orWhere('content', 'like', "%{$query}%")
                    // Search normalized
                    ->orWhere('title', 'like', "%{$normalizedQuery}%");
            })
            // Order by relevance (title matches first)
            ->orderByRaw("CASE
                WHEN title LIKE ? THEN 1
                WHEN excerpt LIKE ? THEN 2
                WHEN content LIKE ? THEN 3
                ELSE 4
            END", ["%{$query}%", "%{$query}%", "%{$query}%"])
            ->orderByDesc('view_count');

        // Filter by category
        if (!empty($filters['category_id'])) {
            $articlesQuery->where('category_id', $filters['category_id']);
        }

        // Filter by article type
        if (!empty($filters['article_type'])) {
            $articlesQuery->where('article_type', $filters['article_type']);
        }

        // Filter by difficulty level
        if (!empty($filters['difficulty_level'])) {
            $articlesQuery->where('difficulty_level', $filters['difficulty_level']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $articlesQuery->paginate($perPage);
    }

    /**
     * Get search suggestions based on query.
     */
    public function suggest(string $query, int $limit = 5): Collection
    {
        $normalizedQuery = $this->normalizeQuery($query);

        // Get article title suggestions
        $articles = KBArticle::published()
            ->select('id', 'title', 'slug')
            ->where('title', 'like', "{$query}%")
            ->orWhere('title', 'like', "%{$query}%")
            ->orderByRaw("CASE
                WHEN title LIKE ? THEN 1
                ELSE 2
            END", ["{$query}%"])
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();

        return $articles;
    }

    /**
     * Log a search query for analytics.
     */
    public function logSearch(string $query, int $resultCount, ?string $userId = null): void
    {
        KBSearchAnalytic::create([
            'search_query' => $query,
            'search_query_normalized' => $this->normalizeQuery($query),
            'results_count' => $resultCount,
            'user_id' => $userId,
            'search_successful' => $resultCount > 0,
            'session_id' => session()->getId(),
        ]);

        $this->log('Search logged', [
            'query' => $query,
            'result_count' => $resultCount,
        ]);
    }

    /**
     * Get popular search queries.
     */
    public function getPopularSearches(int $limit = 10): Collection
    {
        return KBSearchAnalytic::selectRaw('search_query_normalized as query')
            ->selectRaw('COUNT(*) as search_count')
            ->where('results_count', '>', 0)
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('search_query_normalized')
            ->orderByDesc('search_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Normalize search query for consistent analytics.
     */
    private function normalizeQuery(string $query): string
    {
        // Convert to lowercase
        $normalized = Str::lower($query);

        // Remove extra whitespace
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        // Trim
        $normalized = trim($normalized);

        return $normalized;
    }
}
