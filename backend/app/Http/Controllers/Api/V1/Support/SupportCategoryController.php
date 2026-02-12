<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Support;

use App\Data\Support\SupportCategoryData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Support\SupportCategory;
use App\Services\Support\SupportCategoryService;
use Illuminate\Http\JsonResponse;

final class SupportCategoryController extends Controller
{
    public function __construct(
        private readonly SupportCategoryService $categoryService,
    ) {}

    /**
     * List active categories (for ticket creation form).
     * GET /support/categories
     */
    public function index(): JsonResponse
    {
        $categories = $this->categoryService->listActive();

        return $this->success(
            $categories->map(fn (SupportCategory $category) => SupportCategoryData::fromModel($category)->toArray())->toArray(),
            'Categories retrieved successfully'
        );
    }
}
