<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Platform;

use App\Data\Admin\AdminTenantData;
use App\Data\Admin\SuspendData;
use App\Data\Admin\UpdateTenantAdminData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Services\Admin\AdminTenantService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Exceptions\CannotCreateData;

final class AdminTenantController extends Controller
{
    public function __construct(
        private readonly AdminTenantService $tenantService,
    ) {}

    /**
     * List all tenants.
     * GET /admin/tenants
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->query('status'),
            'type' => $request->query('type'),
            'plan_id' => $request->query('plan_id'),
            'onboarding_completed' => $request->query('onboarding_completed'),
            'on_trial' => $request->query('on_trial'),
            'search' => $request->query('search'),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
            'per_page' => $request->query('per_page', 15),
        ];

        $tenants = $this->tenantService->list($filters);

        $transformedItems = collect($tenants->items())->map(
            fn (Tenant $tenant) => AdminTenantData::fromModel($tenant)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Tenants retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'last_page' => $tenants->lastPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
                'from' => $tenants->firstItem(),
                'to' => $tenants->lastItem(),
            ],
            'links' => [
                'first' => $tenants->url(1),
                'last' => $tenants->url($tenants->lastPage()),
                'prev' => $tenants->previousPageUrl(),
                'next' => $tenants->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get a specific tenant.
     * GET /admin/tenants/{tenant}
     */
    public function show(string $tenant): JsonResponse
    {
        try {
            $tenantModel = $this->tenantService->get($tenant);

            return $this->success(
                AdminTenantData::fromModel($tenantModel)->toArray(),
                'Tenant retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Tenant not found');
        }
    }

    /**
     * Update a tenant.
     * PUT /admin/tenants/{tenant}
     */
    public function update(Request $request, string $tenant): JsonResponse
    {
        try {
            $tenantModel = $this->tenantService->get($tenant);

            $data = UpdateTenantAdminData::from($request->all());
            $updatedTenant = $this->tenantService->update($tenantModel, $data);

            return $this->success(
                AdminTenantData::fromModel($updatedTenant)->toArray(),
                'Tenant updated successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Tenant not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        }
    }

    /**
     * Suspend a tenant.
     * POST /admin/tenants/{tenant}/suspend
     */
    public function suspend(Request $request, string $tenant): JsonResponse
    {
        try {
            $tenantModel = $this->tenantService->get($tenant);

            $data = SuspendData::from($request->all());
            $suspendedTenant = $this->tenantService->suspend($tenantModel, $data->reason);

            return $this->success(
                AdminTenantData::fromModel($suspendedTenant)->toArray(),
                'Tenant suspended successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Tenant not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        } catch (CannotCreateData $e) {
            return $this->validationError(['reason' => ['The reason field is required.']], 'Validation failed');
        }
    }

    /**
     * Activate a tenant.
     * POST /admin/tenants/{tenant}/activate
     */
    public function activate(string $tenant): JsonResponse
    {
        try {
            $tenantModel = $this->tenantService->get($tenant);
            $activatedTenant = $this->tenantService->activate($tenantModel);

            return $this->success(
                AdminTenantData::fromModel($activatedTenant)->toArray(),
                'Tenant activated successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Tenant not found');
        }
    }

    /**
     * Get impersonation token for a tenant.
     * POST /admin/tenants/{tenant}/impersonate
     */
    public function impersonate(Request $request, string $tenant): JsonResponse
    {
        try {
            $tenantModel = $this->tenantService->get($tenant);

            /** @var SuperAdminUser $admin */
            $admin = $request->user();

            $token = $this->tenantService->impersonate($tenantModel, $admin);

            return $this->success([
                'token' => $token,
                'expires_at' => now()->addHours(1)->toIso8601String(),
            ], 'Impersonation token generated successfully');
        } catch (ModelNotFoundException $e) {
            return $this->notFound($e->getMessage());
        }
    }

    /**
     * Get tenant statistics.
     * GET /admin/tenants/stats
     */
    public function stats(): JsonResponse
    {
        $stats = $this->tenantService->getStats();

        return $this->success($stats, 'Tenant statistics retrieved successfully');
    }
}
