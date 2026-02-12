<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\KB;

use App\Data\KnowledgeBase\KBArticleSummaryData;
use App\Data\KnowledgeBase\KBSearchResultData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\KnowledgeBase\KBArticle;
use App\Services\KnowledgeBase\KBSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KBSearchController extends Controller
{
    public function __construct(
        private readonly KBSearchService $searchService,
    ) {}

    /**
     * Search articles.
     * GET /kb/search
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', '');

        if (strlen($query) < 2) {
            return $this->error('Search query must be at least 2 characters', 422);
        }

        $filters = [
            'category_id' => $request->query('category_id'),
            'article_type' => $request->query('article_type'),
            'difficulty_level' => $request->query('difficulty_level'),
            'per_page' => $request->query('per_page', 15),
        ];

        $results = $this->searchService->search($query, $filters);

        // Log the search for analytics
        $this->searchService->logSearch(
            $query,
            $results->total(),
            auth()->id()
        );

        $transformedItems = collect($results->items())->map(function (KBArticle $article, $index) {
            return new KBSearchResultData(
                id: $article->id,
                title: $article->title,
                slug: $article->slug,
                excerpt: $article->excerpt,
                category_name: $article->category?->name ?? '',
                article_type: $article->article_type->value,
                relevance_score: 100 - ($index * 5), // Simple relevance scoring
            );
        })->map(fn (KBSearchResultData $item) => $item->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Search completed successfully',
            'data' => $transformedItems,
            'meta' => [
                'query' => $query,
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem(),
            ],
            'links' => [
                'first' => $results->url(1),
                'last' => $results->url($results->lastPage()),
                'prev' => $results->previousPageUrl(),
                'next' => $results->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get search suggestions.
     * GET /kb/search/suggest
     */
    public function suggest(Request $request): JsonResponse
    {
        $query = $request->query('q', '');

        if (strlen($query) < 2) {
            return $this->success([], 'Suggestions retrieved');
        }

        $limit = (int) $request->query('limit', 5);
        $limit = min($limit, 10);

        $suggestions = $this->searchService->suggest($query, $limit);

        return $this->success(
            $suggestions->map(fn ($article) => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
            ])->toArray(),
            'Suggestions retrieved successfully'
        );
    }

    /**
     * Get popular searches.
     * GET /kb/search/popular
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 10);
        $limit = min($limit, 20);

        $searches = $this->searchService->getPopularSearches($limit);

        return $this->success(
            $searches->map(fn ($item) => [
                'query' => $item->query,
                'count' => $item->search_count,
            ])->toArray(),
            'Popular searches retrieved successfully'
        );
    }
}
