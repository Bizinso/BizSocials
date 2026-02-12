<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Support;

use App\Data\Support\CreateCategoryData;
use App\Data\Support\SupportCategoryData;
use App\Data\Support\UpdateCategoryData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Support\CreateCategoryRequest;
use App\Http\Requests\Support\UpdateCategoryRequest;
use App\Models\Support\SupportCategory;
use App\Services\Support\SupportCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

final class AdminSupportCategoryController extends Controller
{
    public function __construct(
        private readonly SupportCategoryService $categoryService,
    ) {}

    /**
     * List all categories (including inactive).
     * GET /admin/support/categories
     */
    public function index(): JsonResponse
    {
        $categories = $this->categoryService->list();

        return $this->success(
            $categories->map(fn (SupportCategory $category) => SupportCategoryData::fromModel($category)->toArray())->toArray(),
            'Categories retrieved successfully'
        );
    }

    /**
     * Create a new category.
     * POST /admin/support/categories
     */
    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $data = CreateCategoryData::from($request->validated());

        try {
            $category = $this->categoryService->create($data);

            return $this->created(
                SupportCategoryData::fromModel($category)->toArray(),
                'Category created successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Update a category.
     * PUT /admin/support/categories/{category}
     */
    public function update(UpdateCategoryRequest $request, SupportCategory $category): JsonResponse
    {
        $data = UpdateCategoryData::from($request->validated());

        try {
            $category = $this->categoryService->update($category, $data);

            return $this->success(
                SupportCategoryData::fromModel($category)->toArray(),
                'Category updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Delete a category.
     * DELETE /admin/support/categories/{category}
     */
    public function destroy(SupportCategory $category): JsonResponse
    {
        try {
            $this->categoryService->delete($category);

            return $this->success(null, 'Category deleted successfully');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }
}
