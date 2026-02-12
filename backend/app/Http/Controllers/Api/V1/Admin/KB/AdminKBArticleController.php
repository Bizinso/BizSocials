<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\KB;

use App\Data\KnowledgeBase\CreateArticleData;
use App\Data\KnowledgeBase\KBArticleData;
use App\Data\KnowledgeBase\KBArticleSummaryData;
use App\Data\KnowledgeBase\UpdateArticleData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\KB\CreateArticleRequest;
use App\Http\Requests\KB\UpdateArticleRequest;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\Platform\SuperAdminUser;
use App\Services\KnowledgeBase\KBArticleService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class AdminKBArticleController extends Controller
{
    public function __construct(
        private readonly KBArticleService $articleService,
    ) {}

    /**
     * List all articles (admin view).
     * GET /admin/kb/articles
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->query('status'),
            'category_id' => $request->query('category_id'),
            'article_type' => $request->query('article_type'),
            'difficulty_level' => $request->query('difficulty_level'),
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 15),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
        ];

        $articles = $this->articleService->list($filters);

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
     * Create a new article.
     * POST /admin/kb/articles
     */
    public function store(CreateArticleRequest $request): JsonResponse
    {
        /** @var SuperAdminUser $admin */
        $admin = $request->user();

        $data = CreateArticleData::from($request->validated());

        try {
            $article = $this->articleService->create($admin, $data);

            return $this->created(
                KBArticleData::fromModel($article)->toArray(),
                'Article created successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Get a single article.
     * GET /admin/kb/articles/{article}
     */
    public function show(KBArticle $article): JsonResponse
    {
        try {
            $article = $this->articleService->get($article->id);

            return $this->success(
                KBArticleData::fromModel($article)->toArray(),
                'Article retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Article not found');
        }
    }

    /**
     * Update an article.
     * PUT /admin/kb/articles/{article}
     */
    public function update(UpdateArticleRequest $request, KBArticle $article): JsonResponse
    {
        /** @var SuperAdminUser $admin */
        $admin = $request->user();

        $data = UpdateArticleData::from($request->validated());

        try {
            $article = $this->articleService->update($article, $admin, $data);

            return $this->success(
                KBArticleData::fromModel($article)->toArray(),
                'Article updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Delete an article.
     * DELETE /admin/kb/articles/{article}
     */
    public function destroy(KBArticle $article): JsonResponse
    {
        $this->articleService->delete($article);

        return $this->success(null, 'Article deleted successfully');
    }

    /**
     * Publish an article.
     * POST /admin/kb/articles/{article}/publish
     */
    public function publish(KBArticle $article): JsonResponse
    {
        try {
            $article = $this->articleService->publish($article);

            return $this->success(
                KBArticleData::fromModel($article)->toArray(),
                'Article published successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Unpublish an article (set to draft).
     * POST /admin/kb/articles/{article}/unpublish
     */
    public function unpublish(KBArticle $article): JsonResponse
    {
        try {
            $article = $this->articleService->unpublish($article);

            return $this->success(
                KBArticleData::fromModel($article)->toArray(),
                'Article unpublished successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Archive an article.
     * POST /admin/kb/articles/{article}/archive
     */
    public function archive(KBArticle $article): JsonResponse
    {
        try {
            $article = $this->articleService->archive($article);

            return $this->success(
                KBArticleData::fromModel($article)->toArray(),
                'Article archived successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }
}
