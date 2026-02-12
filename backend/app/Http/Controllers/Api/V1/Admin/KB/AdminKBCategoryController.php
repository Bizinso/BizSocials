<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\KB;

use App\Data\KnowledgeBase\CreateCategoryData;
use App\Data\KnowledgeBase\KBCategoryData;
use App\Data\KnowledgeBase\UpdateCategoryData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\KB\CreateCategoryRequest;
use App\Http\Requests\KB\UpdateCategoryOrderRequest;
use App\Http\Requests\KB\UpdateCategoryRequest;
use App\Models\KnowledgeBase\KBCategory;
use App\Services\KnowledgeBase\KBCategoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

final class AdminKBCategoryController extends Controller
{
    public function __construct(
        private readonly KBCategoryService $categoryService,
    ) {}

    /**
     * List all categories (admin view).
     * GET /admin/kb/categories
     */
    public function index(): JsonResponse
    {
        $categories = $this->categoryService->list();

        return $this->success(
            $categories->map(fn (KBCategory $category) => KBCategoryData::fromModel($category)->toArray())->toArray(),
            'Categories retrieved successfully'
        );
    }

    /**
     * Create a new category.
     * POST /admin/kb/categories
     */
    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $data = CreateCategoryData::from($request->validated());

        try {
            $category = $this->categoryService->create($data);

            return $this->created(
                KBCategoryData::fromModel($category)->toArray(),
                'Category created successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Get a single category.
     * GET /admin/kb/categories/{category}
     */
    public function show(KBCategory $category): JsonResponse
    {
        try {
            $category = $this->categoryService->get($category->id);

            return $this->success(
                KBCategoryData::fromModel($category)->toArray(),
                'Category retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Category not found');
        }
    }

    /**
     * Update a category.
     * PUT /admin/kb/categories/{category}
     */
    public function update(UpdateCategoryRequest $request, KBCategory $category): JsonResponse
    {
        $data = UpdateCategoryData::from($request->validated());

        try {
            $category = $this->categoryService->update($category, $data);

            return $this->success(
                KBCategoryData::fromModel($category)->toArray(),
                'Category updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Delete a category.
     * DELETE /admin/kb/categories/{category}
     */
    public function destroy(KBCategory $category): JsonResponse
    {
        try {
            $this->categoryService->delete($category);

            return $this->success(null, 'Category deleted successfully');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Update category order.
     * PUT /admin/kb/categories/order
     */
    public function updateOrder(UpdateCategoryOrderRequest $request): JsonResponse
    {
        $order = $request->validated()['order'];

        $this->categoryService->updateOrder($order);

        return $this->success(null, 'Category order updated successfully');
    }
}
