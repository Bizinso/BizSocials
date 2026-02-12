<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Platform;

use App\Data\Admin\PlatformConfigData;
use App\Enums\Platform\ConfigCategory;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Platform\PlatformConfig;
use App\Models\Platform\SuperAdminUser;
use App\Services\Admin\PlatformConfigService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class AdminConfigController extends Controller
{
    public function __construct(
        private readonly PlatformConfigService $configService,
    ) {}

    /**
     * List all configs.
     * GET /admin/config
     */
    public function index(Request $request): JsonResponse
    {
        $category = $request->query('category');

        if ($category !== null) {
            $categoryEnum = ConfigCategory::tryFrom($category);
            if ($categoryEnum !== null) {
                $configs = $this->configService->getByCategory($categoryEnum);
            } else {
                $configs = collect();
            }
        } else {
            $configs = $this->configService->list();
        }

        $transformedItems = $configs->map(
            fn (PlatformConfig $config) => PlatformConfigData::fromModel($config)->toArray()
        );

        return $this->success($transformedItems, 'Configs retrieved successfully');
    }

    /**
     * Get a specific config by key.
     * GET /admin/config/{key}
     */
    public function show(string $key): JsonResponse
    {
        try {
            $config = $this->configService->get($key);

            return $this->success(
                PlatformConfigData::fromModel($config)->toArray(),
                'Config retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Config not found');
        }
    }

    /**
     * Set a config value.
     * PUT /admin/config/{key}
     */
    public function update(Request $request, string $key): JsonResponse
    {
        try {
            $validated = $request->validate([
                'value' => 'required',
                'category' => 'sometimes|string',
                'description' => 'sometimes|string|nullable',
                'is_sensitive' => 'sometimes|boolean',
            ]);

            $category = null;
            if (!empty($validated['category'])) {
                $category = ConfigCategory::tryFrom($validated['category']);
            }

            /** @var SuperAdminUser $admin */
            $admin = $request->user();

            $config = $this->configService->set(
                key: $key,
                value: $validated['value'],
                category: $category,
                description: $validated['description'] ?? null,
                isSensitive: $validated['is_sensitive'] ?? false,
                updatedBy: $admin->id
            );

            return $this->success(
                PlatformConfigData::fromModel($config)->toArray(),
                'Config updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        }
    }

    /**
     * Delete a config.
     * DELETE /admin/config/{key}
     */
    public function destroy(string $key): JsonResponse
    {
        try {
            $this->configService->get($key); // Check if exists
            $this->configService->delete($key);

            return $this->success(null, 'Config deleted successfully');
        } catch (ModelNotFoundException) {
            return $this->notFound('Config not found');
        }
    }

    /**
     * Get configs grouped by category.
     * GET /admin/config/grouped
     */
    public function grouped(): JsonResponse
    {
        $grouped = $this->configService->getAllGroupedByCategory();

        $transformed = [];
        foreach ($grouped as $category => $configs) {
            $transformed[$category] = $configs->map(
                fn (PlatformConfig $config) => PlatformConfigData::fromModel($config)->toArray()
            )->values()->toArray();
        }

        return $this->success($transformed, 'Configs retrieved successfully');
    }

    /**
     * Bulk set configs.
     * POST /admin/config/bulk
     */
    public function bulkSet(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'configs' => 'required|array',
                'configs.*' => 'required',
                'category' => 'sometimes|string',
            ]);

            $category = null;
            if (!empty($validated['category'])) {
                $category = ConfigCategory::tryFrom($validated['category']);
            }

            /** @var SuperAdminUser $admin */
            $admin = $request->user();

            $this->configService->bulkSet(
                configs: $validated['configs'],
                category: $category,
                updatedBy: $admin->id
            );

            return $this->success(null, 'Configs updated successfully');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        }
    }

    /**
     * Get available config categories.
     * GET /admin/config/categories
     */
    public function categories(): JsonResponse
    {
        $categories = [];
        foreach (ConfigCategory::cases() as $category) {
            $categories[] = [
                'value' => $category->value,
                'label' => $category->label(),
                'description' => $category->description(),
            ];
        }

        return $this->success($categories, 'Categories retrieved successfully');
    }
}
