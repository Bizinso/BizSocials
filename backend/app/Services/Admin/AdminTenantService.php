<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Data\Admin\UpdateTenantAdminData;
use App\Enums\Tenant\TenantStatus;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

final class AdminTenantService extends BaseService
{
    /**
     * List tenants with filters.
     *
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Tenant::query()
            ->with(['plan']);

        // Filter by status
        if (!empty($filters['status'])) {
            $status = TenantStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by plan
        if (!empty($filters['plan_id'])) {
            $query->where('plan_id', $filters['plan_id']);
        }

        // Filter by onboarding status
        if (isset($filters['onboarding_completed'])) {
            if ($filters['onboarding_completed']) {
                $query->whereNotNull('onboarding_completed_at');
            } else {
                $query->whereNull('onboarding_completed_at');
            }
        }

        // Filter tenants on trial
        if (!empty($filters['on_trial'])) {
            $query->onTrial();
        }

        // Search by name or slug
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Get a tenant by ID.
     *
     * @throws ModelNotFoundException
     */
    public function get(string $id): Tenant
    {
        $tenant = Tenant::with(['plan', 'profile', 'onboarding'])->find($id);

        if ($tenant === null) {
            throw new ModelNotFoundException('Tenant not found.');
        }

        return $tenant;
    }

    /**
     * Update a tenant.
     */
    public function update(Tenant $tenant, UpdateTenantAdminData $data): Tenant
    {
        return $this->transaction(function () use ($tenant, $data) {
            $updateData = [];

            if ($data->name !== null) {
                $updateData['name'] = $data->name;
            }

            if ($data->plan_id !== null) {
                $updateData['plan_id'] = $data->plan_id;
            }

            if ($data->settings !== null) {
                $currentSettings = $tenant->settings ?? [];
                $updateData['settings'] = array_merge($currentSettings, $data->settings);
            }

            if ($data->metadata !== null) {
                $currentMetadata = $tenant->metadata ?? [];
                $updateData['metadata'] = array_merge($currentMetadata, $data->metadata);
            }

            if (!empty($updateData)) {
                $tenant->update($updateData);
            }

            $this->log('Tenant updated by admin', [
                'tenant_id' => $tenant->id,
                'updates' => array_keys($updateData),
            ]);

            return $tenant->fresh(['plan']);
        });
    }

    /**
     * Suspend a tenant.
     */
    public function suspend(Tenant $tenant, string $reason): Tenant
    {
        return $this->transaction(function () use ($tenant, $reason) {
            $tenant->suspend($reason);

            $this->log('Tenant suspended by admin', [
                'tenant_id' => $tenant->id,
                'reason' => $reason,
            ]);

            return $tenant->fresh(['plan']);
        });
    }

    /**
     * Activate a tenant.
     */
    public function activate(Tenant $tenant): Tenant
    {
        return $this->transaction(function () use ($tenant) {
            $metadata = $tenant->metadata ?? [];
            unset($metadata['suspension_reason'], $metadata['suspended_at']);
            $tenant->metadata = $metadata;

            $tenant->activate();

            $this->log('Tenant activated by admin', [
                'tenant_id' => $tenant->id,
            ]);

            return $tenant->fresh(['plan']);
        });
    }

    /**
     * Generate an impersonation token for a tenant.
     * Returns a temporary API token for accessing the platform as the tenant.
     */
    public function impersonate(Tenant $tenant, SuperAdminUser $admin): string
    {
        // Get the owner user of the tenant
        $ownerUser = $tenant->users()
            ->where('role_in_tenant', 'owner')
            ->first();

        if ($ownerUser === null) {
            // Fallback to any admin user in the tenant
            $ownerUser = $tenant->users()
                ->whereIn('role_in_tenant', ['owner', 'admin'])
                ->first();
        }

        if ($ownerUser === null) {
            throw new ModelNotFoundException('No user found to impersonate for this tenant.');
        }

        // Create a temporary token with limited expiration
        $token = $ownerUser->createToken(
            'admin-impersonation',
            ['*'],
            now()->addHours(1)
        );

        $this->log('Admin impersonated tenant', [
            'tenant_id' => $tenant->id,
            'admin_id' => $admin->id,
            'impersonated_user_id' => $ownerUser->id,
        ]);

        return $token->plainTextToken;
    }

    /**
     * Get tenant statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', TenantStatus::ACTIVE)->count();
        $suspendedTenants = Tenant::where('status', TenantStatus::SUSPENDED)->count();
        $onTrialTenants = Tenant::onTrial()->count();
        $completedOnboarding = Tenant::whereNotNull('onboarding_completed_at')->count();

        // Tenants by status
        $byStatus = [];
        foreach (TenantStatus::cases() as $status) {
            $byStatus[$status->value] = Tenant::where('status', $status)->count();
        }

        // Tenants by plan
        $byPlan = Tenant::selectRaw('plan_id, COUNT(*) as count')
            ->groupBy('plan_id')
            ->pluck('count', 'plan_id')
            ->toArray();

        return [
            'total' => $totalTenants,
            'active' => $activeTenants,
            'suspended' => $suspendedTenants,
            'on_trial' => $onTrialTenants,
            'completed_onboarding' => $completedOnboarding,
            'by_status' => $byStatus,
            'by_plan' => $byPlan,
        ];
    }
}
