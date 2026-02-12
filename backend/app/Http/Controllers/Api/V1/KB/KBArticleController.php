<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\KB;

use App\Data\KnowledgeBase\KBArticleData;
use App\Data\KnowledgeBase\KBArticleSummaryData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\KnowledgeBase\KBArticle;
use App\Services\KnowledgeBase\KBArticleService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KBArticleController extends Controller
{
    public function __construct(
        private readonly KBArticleService $articleService,
    ) {}

    /**
     * List published articles.
     * GET /kb/articles
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'category_id' => $request->query('category_id'),
            'article_type' => $request->query('article_type'),
            'difficulty_level' => $request->query('difficulty_level'),
            'tag_id' => $request->query('tag_id'),
            'per_page' => $request->query('per_page', 15),
            'sort_by' => $request->query('sort_by', 'published_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
        ];

        $articles = $this->articleService->listPublished($filters);

        $transformedItems = collect($articles->items())->map(
            fn (KBArticle $article) => KBArticleSummaryData::fromModel($article)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Articles retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
                'from' => $articles->firstItem(),
                'to' => $articles->lastItem(),
            ],
            'links' => [
                'first' => $articles->url(1),
                'last' => $articles->url($articles->lastPage()),
                'prev' => $articles->previousPageUrl(),
                'next' => $articles->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get featured articles.
     * GET /kb/articles/featured
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 5);
        $limit = min($limit, 20);

        $articles = $this->articleService->getFeatured($limit);

        return $this->success(
            $articles->map(fn (KBArticle $article) => KBArticleSummaryData::fromModel($article)->toArray())->toArray(),
            'Featured articles retrieved successfully'
        );
    }

    /**
     * Get popular articles.
     * GET /kb/articles/popular
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 10);
        $limit = min($limit, 50);

        $articles = $this->articleService->getPopular($limit);

        return $this->success(
            $articles->map(fn (KBArticle $article) => KBArticleSummaryData::fromModel($article)->toArray())->toArray(),
            'Popular articles retrieved successfully'
        );
    }

    /**
     * Get article by slug.
     * GET /kb/articles/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $article = $this->articleService->getBySlug($slug);

            // Increment view count
            $this->articleService->incrementViewCount($article);

            // Get related articles
            $related = $this->articleService->getRelated($article, 5);

            $response = KBArticleData::fromModel($article)->toArray();
            $response['related_articles'] = $related->map(
                fn (KBArticle $a) => KBArticleSummaryData::fromModel($a)->toArray()
            )->toArray();

            return $this->success($response, 'Article retrieved successfully');
        } catch (ModelNotFoundException) {
            return $this->notFound('Article not found');
        }
    }
}
