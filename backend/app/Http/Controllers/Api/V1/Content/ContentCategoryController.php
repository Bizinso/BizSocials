<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Content\ContentCategory;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\ContentCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ContentCategoryController extends Controller
{
    public function __construct(
        private readonly ContentCategoryService $categoryService,
    ) {}

    /**
     * List all categories.
     */
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $categories = $this->categoryService->list($workspace->id);

        return $this->success($categories, 'Categories retrieved successfully');
    }

    /**
     * Create a new category.
     */
    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string|max:500',
        ]);

        $category = $this->categoryService->create($workspace->id, $validated);

        return $this->created($category, 'Category created successfully');
    }

    /**
     * Get a single category.
     */
    public function show(Request $request, Workspace $workspace, ContentCategory $contentCategory): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($contentCategory->workspace_id !== $workspace->id) {
            return $this->notFound('Category not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        return $this->success($contentCategory, 'Category retrieved successfully');
    }

    /**
     * Update a category.
     */
    public function update(Request $request, Workspace $workspace, ContentCategory $contentCategory): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($contentCategory->workspace_id !== $workspace->id) {
            return $this->notFound('Category not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string|max:500',
        ]);

        $category = $this->categoryService->update($contentCategory, $validated);

        return $this->success($category, 'Category updated successfully');
    }

    /**
     * Delete a category.
     */
    public function destroy(Request $request, Workspace $workspace, ContentCategory $contentCategory): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($contentCategory->workspace_id !== $workspace->id) {
            return $this->notFound('Category not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->categoryService->delete($contentCategory);

        return $this->success(null, 'Category deleted successfully');
    }

    /**
     * Reorder categories.
     */
    public function reorder(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'uuid|exists:content_categories,id',
        ]);

        $count = $this->categoryService->reorder($validated['category_ids']);

        return $this->success(['updated_count' => $count], 'Categories reordered successfully');
    }
}
