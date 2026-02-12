<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Data\Tenant\TenantData;
use App\Data\Tenant\UpdateTenantData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantSettingsRequest;
use App\Models\User;
use App\Services\Tenant\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TenantController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService,
    ) {}

    /**
     * Get the current tenant.
     */
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tenant = $this->tenantService->getCurrent($user);

        return $this->success(
            TenantData::fromModel($tenant)->toArray(),
            'Tenant retrieved successfully'
        );
    }

    /**
     * Update the current tenant.
     */
    public function update(UpdateTenantRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tenant = $this->tenantService->getCurrent($user);
        $data = UpdateTenantData::from($request->validated());

        $updatedTenant = $this->tenantService->update($tenant, $data);

        return $this->success(
            TenantData::fromModel($updatedTenant)->toArray(),
            'Tenant updated successfully'
        );
    }

    /**
     * Update the current tenant's settings.
     */
    public function updateSettings(UpdateTenantSettingsRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tenant = $this->tenantService->getCurrent($user);

        $updatedTenant = $this->tenantService->updateSettings($tenant, $request->validated());

        return $this->success(
            TenantData::fromModel($updatedTenant)->toArray(),
            'Tenant settings updated successfully'
        );
    }

    /**
     * Get usage statistics for the current tenant.
     */
    public function stats(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tenant = $this->tenantService->getCurrent($user);
        $stats = $this->tenantService->getUsageStats($tenant);

        return $this->success($stats, 'Usage statistics retrieved successfully');
    }
}
