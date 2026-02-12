<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Data\Tenant\UpdateTenantData;
use App\Enums\User\TenantRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

final class TenantService extends BaseService
{
    /**
     * Get the current tenant for a user.
     */
    public function getCurrent(User $user): Tenant
    {
        $tenant = $user->tenant;

        if ($tenant === null) {
            throw ValidationException::withMessages([
                'tenant' => ['User does not belong to any tenant.'],
            ]);
        }

        return $tenant;
    }

    /**
     * Update a tenant.
     */
    public function update(Tenant $tenant, UpdateTenantData $data): Tenant
    {
        return $this->transaction(function () use ($tenant, $data) {
            $updateData = [];

            if ($data->name !== null && !($data->name instanceof \Spatie\LaravelData\Optional)) {
                $updateData['name'] = $data->name;
            }

            if (!empty($updateData)) {
                $tenant->update($updateData);
            }

            // Update profile if website, industry or company_size are provided
            $profileData = [];
            if ($data->website !== null && !($data->website instanceof \Spatie\LaravelData\Optional)) {
                $profileData['website'] = $data->website;
            }
            if ($data->industry !== null && !($data->industry instanceof \Spatie\LaravelData\Optional)) {
                $profileData['industry'] = $data->industry;
            }
            if ($data->company_size !== null && !($data->company_size instanceof \Spatie\LaravelData\Optional)) {
                $profileData['company_size'] = $data->company_size;
            }

            if (!empty($profileData)) {
                if ($tenant->profile !== null) {
                    $tenant->profile->update($profileData);
                } else {
                    $tenant->profile()->create($profileData);
                }
            }

            // Update timezone in settings if provided
            if ($data->timezone !== null && !($data->timezone instanceof \Spatie\LaravelData\Optional)) {
                $tenant->setSetting('timezone', $data->timezone);
            }

            $this->log('Tenant updated', ['tenant_id' => $tenant->id]);

            return $tenant->fresh();
        });
    }

    /**
     * Update tenant settings.
     *
     * @param array<string, mixed> $settings
     */
    public function updateSettings(Tenant $tenant, array $settings): Tenant
    {
        return $this->transaction(function () use ($tenant, $settings) {
            $currentSettings = $tenant->settings ?? [];
            $mergedSettings = array_merge($currentSettings, $settings);
            $tenant->settings = $mergedSettings;
            $tenant->save();

            $this->log('Tenant settings updated', ['tenant_id' => $tenant->id]);

            return $tenant->fresh();
        });
    }

    /**
     * Get usage statistics for a tenant.
     *
     * @return array<string, mixed>
     */
    public function getUsageStats(Tenant $tenant): array
    {
        $usersCount = User::where('tenant_id', $tenant->id)->count();
        $workspacesCount = $tenant->profile?->workspaces_count ?? 0;
        $socialAccountsCount = 0; // Will be populated when social accounts are available

        return [
            'users' => [
                'count' => $usersCount,
                'limit' => $this->getPlanLimit($tenant, 'users'),
            ],
            'workspaces' => [
                'count' => $workspacesCount,
                'limit' => $this->getPlanLimit($tenant, 'workspaces'),
            ],
            'social_accounts' => [
                'count' => $socialAccountsCount,
                'limit' => $this->getPlanLimit($tenant, 'social_accounts'),
            ],
            'storage' => [
                'used_mb' => 0, // Will be populated when storage tracking is available
                'limit_mb' => $this->getPlanLimit($tenant, 'storage_mb'),
            ],
        ];
    }

    /**
     * Get plan limit for a specific resource.
     */
    private function getPlanLimit(Tenant $tenant, string $resource): ?int
    {
        $plan = $tenant->plan;
        if ($plan === null) {
            // Default limits for tenants without a plan
            return match ($resource) {
                'users' => 5,
                'workspaces' => 3,
                'social_accounts' => 10,
                'storage_mb' => 500,
                default => null,
            };
        }

        return Arr::get($plan->limits ?? [], $resource);
    }

    /**
     * Get members of a tenant.
     *
     * @param array<string, mixed> $filters
     */
    public function getMembers(Tenant $tenant, array $filters = []): LengthAwarePaginator
    {
        $query = User::where('tenant_id', $tenant->id);

        // Apply role filter
        if (!empty($filters['role'])) {
            $role = TenantRole::tryFrom($filters['role']);
            if ($role !== null) {
                $query->where('role_in_tenant', $role);
            }
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = min($perPage, 100); // Max 100 per page

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Update a member's role in the tenant.
     */
    public function updateMemberRole(Tenant $tenant, User $targetUser, TenantRole $role): User
    {
        return $this->transaction(function () use ($tenant, $targetUser, $role) {
            // Verify user belongs to tenant
            if ($targetUser->tenant_id !== $tenant->id) {
                throw ValidationException::withMessages([
                    'user' => ['User does not belong to this tenant.'],
                ]);
            }

            // Cannot change the owner's role if they are the only owner
            if ($targetUser->role_in_tenant === TenantRole::OWNER && $role !== TenantRole::OWNER) {
                $ownersCount = User::where('tenant_id', $tenant->id)
                    ->where('role_in_tenant', TenantRole::OWNER)
                    ->count();

                if ($ownersCount === 1) {
                    throw ValidationException::withMessages([
                        'role' => ['Cannot change role of the only owner. Assign another owner first.'],
                    ]);
                }
            }

            $targetUser->role_in_tenant = $role;
            $targetUser->save();

            $this->log('Member role updated', [
                'tenant_id' => $tenant->id,
                'user_id' => $targetUser->id,
                'new_role' => $role->value,
            ]);

            return $targetUser;
        });
    }

    /**
     * Remove a member from the tenant.
     */
    public function removeMember(Tenant $tenant, User $targetUser): void
    {
        $this->transaction(function () use ($tenant, $targetUser) {
            // Verify user belongs to tenant
            if ($targetUser->tenant_id !== $tenant->id) {
                throw ValidationException::withMessages([
                    'user' => ['User does not belong to this tenant.'],
                ]);
            }

            // Cannot remove the only owner
            if ($targetUser->role_in_tenant === TenantRole::OWNER) {
                $ownersCount = User::where('tenant_id', $tenant->id)
                    ->where('role_in_tenant', TenantRole::OWNER)
                    ->count();

                if ($ownersCount === 1) {
                    throw ValidationException::withMessages([
                        'user' => ['Cannot remove the only owner. Transfer ownership first.'],
                    ]);
                }
            }

            $this->log('Member removed from tenant', [
                'tenant_id' => $tenant->id,
                'user_id' => $targetUser->id,
            ]);

            // Soft delete the user (they remain in the system but are deactivated)
            $targetUser->deactivate();
            $targetUser->delete();
        });
    }
}
