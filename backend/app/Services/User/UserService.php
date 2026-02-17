<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Data\User\UpdateProfileData;
use App\Enums\User\TenantRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class UserService extends BaseService
{
    /**
     * Get user profile with relationships.
     */
    public function getProfile(User $user): User
    {
        return $user->load(['tenant']);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(User $user, UpdateProfileData $data): User
    {
        $updateData = array_filter([
            'name' => $data->name,
            'timezone' => $data->timezone,
            'phone' => $data->phone,
        ], fn ($value) => $value !== null);

        // Handle job_title in settings
        if ($data->job_title !== null) {
            $user->setSetting('job_title', $data->job_title);
        }

        if (! empty($updateData)) {
            $user->update($updateData);
        }

        $this->log('Profile updated', ['user_id' => $user->id]);

        return $user->fresh() ?? $user;
    }

    /**
     * Update user settings.
     *
     * @param  array<string, mixed>  $settings
     */
    public function updateSettings(User $user, array $settings): User
    {
        $currentSettings = $user->settings ?? [];
        $user->settings = array_merge($currentSettings, $settings);
        $user->save();

        $this->log('Settings updated', ['user_id' => $user->id]);

        return $user;
    }

    /**
     * Delete user account.
     *
     * @throws ValidationException
     */
    public function deleteAccount(User $user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The password is incorrect.'],
            ]);
        }

        // Revoke all tokens
        $user->tokens()->delete();

        // Soft delete the user
        $user->delete();

        $this->log('Account deleted', ['user_id' => $user->id]);
    }

    /**
     * Get users for a tenant with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getUsersForTenant(Tenant $tenant, array $filters = []): LengthAwarePaginator
    {
        $query = User::query()
            ->where('tenant_id', $tenant->id)
            ->with(['tenant']);

        // Apply filters
        if (isset($filters['role']) && $filters['role'] !== null) {
            $query->where('role_in_tenant', $filters['role']);
        }

        if (isset($filters['status']) && $filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search']) && $filters['search'] !== null) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Create a new user in a tenant.
     *
     * @param  array<string, mixed>  $data
     * @throws ValidationException
     */
    public function createUser(Tenant $tenant, array $data, User $createdBy): User
    {
        // Check if creator has permission
        if (! $createdBy->isAdmin()) {
            throw ValidationException::withMessages([
                'permission' => ['Only admins can create users.'],
            ]);
        }

        // Check if email already exists in tenant
        $existingUser = User::where('tenant_id', $tenant->id)
            ->where('email', $data['email'])
            ->first();

        if ($existingUser !== null) {
            throw ValidationException::withMessages([
                'email' => ['A user with this email already exists in this tenant.'],
            ]);
        }

        $user = DB::transaction(function () use ($tenant, $data) {
            return User::create([
                'tenant_id' => $tenant->id,
                'email' => $data['email'],
                'name' => $data['name'],
                'password' => isset($data['password']) ? Hash::make($data['password']) : null,
                'role_in_tenant' => $data['role_in_tenant'] ?? TenantRole::MEMBER,
                'status' => $data['status'] ?? \App\Enums\User\UserStatus::ACTIVE,
                'timezone' => $data['timezone'] ?? null,
                'phone' => $data['phone'] ?? null,
                'language' => $data['language'] ?? 'en',
            ]);
        });

        $this->log('User created', [
            'user_id' => $user->id,
            'created_by' => $createdBy->id,
            'tenant_id' => $tenant->id,
        ]);

        return $user;
    }

    /**
     * Update a user's role.
     *
     * @throws ValidationException
     */
    public function updateUserRole(User $user, TenantRole $newRole, User $updatedBy): User
    {
        // Check if updater has permission
        if (! $updatedBy->isAdmin()) {
            throw ValidationException::withMessages([
                'permission' => ['Only admins can update user roles.'],
            ]);
        }

        // Cannot change own role
        if ($user->id === $updatedBy->id) {
            throw ValidationException::withMessages([
                'user' => ['You cannot change your own role.'],
            ]);
        }

        // Only owner can assign owner role
        if ($newRole === TenantRole::OWNER && ! $updatedBy->isOwner()) {
            throw ValidationException::withMessages([
                'role' => ['Only the owner can assign the owner role.'],
            ]);
        }

        // Cannot demote the last owner
        if ($user->isOwner() && $newRole !== TenantRole::OWNER) {
            $ownerCount = User::where('tenant_id', $user->tenant_id)
                ->where('role_in_tenant', TenantRole::OWNER)
                ->count();

            if ($ownerCount <= 1) {
                throw ValidationException::withMessages([
                    'role' => ['Cannot demote the last owner. Assign another owner first.'],
                ]);
            }
        }

        $oldRole = $user->role_in_tenant;
        $user->role_in_tenant = $newRole;
        $user->save();

        $this->log('User role updated', [
            'user_id' => $user->id,
            'old_role' => $oldRole->value,
            'new_role' => $newRole->value,
            'updated_by' => $updatedBy->id,
        ]);

        return $user->fresh() ?? $user;
    }

    /**
     * Remove a user from a tenant.
     *
     * @throws ValidationException
     */
    public function removeUser(User $user, User $removedBy): void
    {
        // Check if remover has permission
        if (! $removedBy->isAdmin()) {
            throw ValidationException::withMessages([
                'permission' => ['Only admins can remove users.'],
            ]);
        }

        // Cannot remove self
        if ($user->id === $removedBy->id) {
            throw ValidationException::withMessages([
                'user' => ['You cannot remove yourself.'],
            ]);
        }

        // Cannot remove the last owner
        if ($user->isOwner()) {
            $ownerCount = User::where('tenant_id', $user->tenant_id)
                ->where('role_in_tenant', TenantRole::OWNER)
                ->count();

            if ($ownerCount <= 1) {
                throw ValidationException::withMessages([
                    'user' => ['Cannot remove the last owner.'],
                ]);
            }
        }

        DB::transaction(function () use ($user) {
            // Revoke all tokens
            $user->tokens()->delete();

            // Soft delete the user
            $user->delete();
        });

        $this->log('User removed', [
            'user_id' => $user->id,
            'removed_by' => $removedBy->id,
        ]);
    }

    /**
     * Check if a user has permission to perform an action.
     */
    public function hasPermission(User $user, string $permission): bool
    {
        return match ($permission) {
            'manage_users' => $user->isAdmin(),
            'manage_billing' => $user->isOwner(),
            'delete_tenant' => $user->isOwner(),
            'manage_workspaces' => $user->isAdmin(),
            'view_analytics' => true, // All users can view analytics
            'manage_content' => true, // All users can manage content
            default => false,
        };
    }

    /**
     * Get all available permissions for a role.
     *
     * @return array<string>
     */
    public function getPermissionsForRole(TenantRole $role): array
    {
        return match ($role) {
            TenantRole::OWNER => [
                'manage_users',
                'manage_billing',
                'delete_tenant',
                'manage_workspaces',
                'view_analytics',
                'manage_content',
            ],
            TenantRole::ADMIN => [
                'manage_users',
                'manage_workspaces',
                'view_analytics',
                'manage_content',
            ],
            TenantRole::MEMBER => [
                'view_analytics',
                'manage_content',
            ],
        };
    }
}
