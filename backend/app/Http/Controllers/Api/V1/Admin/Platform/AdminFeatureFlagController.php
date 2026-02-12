<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Platform;

use App\Data\Admin\CreateFeatureFlagData;
use App\Data\Admin\FeatureFlagData;
use App\Data\Admin\UpdateFeatureFlagData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Platform\FeatureFlag;
use App\Services\Admin\FeatureFlagService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class AdminFeatureFlagController extends Controller
{
    public function __construct(
        private readonly FeatureFlagService $featureFlagService,
    ) {}

    /**
     * List all feature flags.
     * GET /admin/feature-flags
     */
    public function index(): JsonResponse
    {
        $flags = $this->featureFlagService->list();

        $transformedItems = $flags->map(
            fn (FeatureFlag $flag) => FeatureFlagData::fromModel($flag)->toArray()
        );

        return $this->success($transformedItems, 'Feature flags retrieved successfully');
    }

    /**
     * Get a specific feature flag.
     * GET /admin/feature-flags/{featureFlag}
     */
    public function show(string $featureFlag): JsonResponse
    {
        try {
            $flag = $this->featureFlagService->get($featureFlag);

            return $this->success(
                FeatureFlagData::fromModel($flag)->toArray(),
                'Feature flag retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Feature flag not found');
        }
    }

    /**
     * Create a new feature flag.
     * POST /admin/feature-flags
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = CreateFeatureFlagData::from($request->all());
            $flag = $this->featureFlagService->create($data);

            return $this->created(
                FeatureFlagData::fromModel($flag)->toArray(),
                'Feature flag created successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        }
    }

    /**
     * Update a feature flag.
     * PUT /admin/feature-flags/{featureFlag}
     */
    public function update(Request $request, string $featureFlag): JsonResponse
    {
        try {
            $flag = $this->featureFlagService->get($featureFlag);

            $data = UpdateFeatureFlagData::from($request->all());
            $updatedFlag = $this->featureFlagService->update($flag, $data);

            return $this->success(
                FeatureFlagData::fromModel($updatedFlag)->toArray(),
                'Feature flag updated successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Feature flag not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        }
    }

    /**
     * Delete a feature flag.
     * DELETE /admin/feature-flags/{featureFlag}
     */
    public function destroy(string $featureFlag): JsonResponse
    {
        try {
            $flag = $this->featureFlagService->get($featureFlag);
            $this->featureFlagService->delete($flag);

            return $this->success(null, 'Feature flag deleted successfully');
        } catch (ModelNotFoundException) {
            return $this->notFound('Feature flag not found');
        }
    }

    /**
     * Toggle a feature flag.
     * POST /admin/feature-flags/{featureFlag}/toggle
     */
    public function toggle(string $featureFlag): JsonResponse
    {
        try {
            $flag = $this->featureFlagService->get($featureFlag);
            $toggledFlag = $this->featureFlagService->toggle($flag);

            return $this->success(
                FeatureFlagData::fromModel($toggledFlag)->toArray(),
                'Feature flag toggled successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Feature flag not found');
        }
    }

    /**
     * Add a tenant to the allowed list.
     * POST /admin/feature-flags/{featureFlag}/tenants/{tenant}
     */
    public function addTenant(string $featureFlag, string $tenant): JsonResponse
    {
        try {
            $flag = $this->featureFlagService->get($featureFlag);
            $updatedFlag = $this->featureFlagService->addAllowedTenant($flag, $tenant);

            return $this->success(
                FeatureFlagData::fromModel($updatedFlag)->toArray(),
                'Tenant added to feature flag'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Feature flag not found');
        }
    }

    /**
     * Remove a tenant from the allowed list.
     * DELETE /admin/feature-flags/{featureFlag}/tenants/{tenant}
     */
    public function removeTenant(string $featureFlag, string $tenant): JsonResponse
    {
        try {
            $flag = $this->featureFlagService->get($featureFlag);
            $updatedFlag = $this->featureFlagService->removeAllowedTenant($flag, $tenant);

            return $this->success(
                FeatureFlagData::fromModel($updatedFlag)->toArray(),
                'Tenant removed from feature flag'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Feature flag not found');
        }
    }

    /**
     * Check if a feature is enabled.
     * GET /admin/feature-flags/check/{key}
     */
    public function check(Request $request, string $key): JsonResponse
    {
        $tenantId = $request->query('tenant_id');

        $tenant = null;
        if ($tenantId !== null) {
            $tenant = \App\Models\Tenant\Tenant::find($tenantId);
        }

        $isEnabled = $this->featureFlagService->isEnabled($key, $tenant);

        return $this->success([
            'key' => $key,
            'is_enabled' => $isEnabled,
            'tenant_id' => $tenantId,
        ], 'Feature flag check completed');
    }
}
