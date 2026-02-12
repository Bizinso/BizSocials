<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\KB;

use App\Data\KnowledgeBase\KBArticleSummaryData;
use App\Data\KnowledgeBase\KBCategoryData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBCategory;
use App\Services\KnowledgeBase\KBArticleService;
use App\Services\KnowledgeBase\KBCategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KBCategoryController extends Controller
{
    public function __construct(
        private readonly KBCategoryService $categoryService,
        private readonly KBArticleService $articleService,
    ) {}

    /**
     * List all public categories with article counts.
     * GET /kb/categories
     */
    public function index(): JsonResponse
    {
        $categories = $this->categoryService->listWithArticleCount();

        return $this->success(
            $categories->map(fn (KBCategory $category) => KBCategoryData::fromModel($category)->toArray())->toArray(),
            'Categories retrieved successfully'
        );
    }

    /**
     * Get category tree structure.
     * GET /kb/categories/tree
     */
    public function tree(): JsonResponse
    {
        $tree = $this->categoryService->getTree();

        return $this->success(
            $this->transformTree($tree),
            'Category tree retrieved successfully'
        );
    }

    /**
     * Get a category by slug with its articles.
     * GET /kb/categories/{slug}
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        try {
            $category = $this->categoryService->getBySlug($slug);

            $filters = [
                'category_id' => $category->id,
                'per_page' => $request->query('per_page', 15),
                'sort_by' => $request->query('sort_by', 'published_at'),
                'sort_dir' => $request->query('sort_dir', 'desc'),
            ];

            $articles = $this->articleService->listPublished($filters);

            $categoryData = KBCategoryData::fromModel($category)->toArray();
            $categoryData['articles'] = [
                'data' => collect($articles->items())->map(
                    fn (KBArticle $article) => KBArticleSummaryData::fromModel($article)->toArray()
                )->toArray(),
                'meta' => [
                    'current_page' => $articles->currentPage(),
                    'last_page' => $articles->lastPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                ],
            ];

            return $this->success($categoryData, 'Category retrieved successfully');
        } catch (ModelNotFoundException) {
            return $this->notFound('Category not found');
        }
    }

    /**
     * Transform category tree with nested children.
     *
     * @param \Illuminate\Database\Eloquent\Collection $tree
     * @return array<array<string, mixed>>
     */
    private function transformTree($tree): array
    {
        return $tree->map(function (KBCategory $category) {
            $data = KBCategoryData::fromModel($category)->toArray();
            $data['children'] = $category->relationLoaded('children')
                ? $this->transformTree($category->children)
                : [];
            return $data;
        })->toArray();
    }
}
