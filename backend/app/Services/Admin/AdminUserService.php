<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Data\Admin\UpdateUserAdminData;
use App\Enums\User\TenantRole;
use App\Enums\User\UserStatus;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class AdminUserService extends BaseService
{
    /**
     * List users with filters.
     *
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['tenant']);

        // Filter by status
        if (!empty($filters['status'])) {
            $status = UserStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        // Filter by tenant
        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        // Filter by role
        if (!empty($filters['role'])) {
            $role = TenantRole::tryFrom($filters['role']);
            if ($role !== null) {
                $query->where('role_in_tenant', $role);
            }
        }

        // Filter by email verified
        if (isset($filters['email_verified'])) {
            if ($filters['email_verified']) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Filter by MFA enabled
        if (isset($filters['mfa_enabled'])) {
            $query->where('mfa_enabled', $filters['mfa_enabled']);
        }

        // Search by name or email
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Get a user by ID.
     *
     * @throws ModelNotFoundException
     */
    public function get(string $id): User
    {
        $user = User::with(['tenant'])->find($id);

        if ($user === null) {
            throw new ModelNotFoundException('User not found.');
        }

        return $user;
    }

    /**
     * Update a user.
     */
    public function update(User $user, UpdateUserAdminData $data): User
    {
        return $this->transaction(function () use ($user, $data) {
            $updateData = [];

            if ($data->name !== null) {
                $updateData['name'] = $data->name;
            }

            if ($data->role_in_tenant !== null) {
                $role = TenantRole::tryFrom($data->role_in_tenant);
                if ($role !== null) {
                    $updateData['role_in_tenant'] = $role;
                }
            }

            if ($data->timezone !== null) {
                $updateData['timezone'] = $data->timezone;
            }

            if ($data->language !== null) {
                $updateData['language'] = $data->language;
            }

            if ($data->mfa_enabled !== null) {
                $updateData['mfa_enabled'] = $data->mfa_enabled;
                // If disabling MFA, clear the secret
                if (!$data->mfa_enabled) {
                    $updateData['mfa_secret'] = null;
                }
            }

            if ($data->settings !== null) {
                $currentSettings = $user->settings ?? [];
                $updateData['settings'] = array_merge($currentSettings, $data->settings);
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            $this->log('User updated by admin', [
                'user_id' => $user->id,
                'updates' => array_keys($updateData),
            ]);

            return $user->fresh(['tenant']);
        });
    }

    /**
     * Suspend a user.
     */
    public function suspend(User $user, string $reason): User
    {
        return $this->transaction(function () use ($user, $reason) {
            $user->status = UserStatus::SUSPENDED;

            // Store suspension reason in settings
            $settings = $user->settings ?? [];
            $settings['suspension_reason'] = $reason;
            $settings['suspended_at'] = now()->toIso8601String();
            $user->settings = $settings;

            $user->save();

            // Revoke all active tokens
            $user->tokens()->delete();

            $this->log('User suspended by admin', [
                'user_id' => $user->id,
                'reason' => $reason,
            ]);

            return $user->fresh(['tenant']);
        });
    }

    /**
     * Activate a user.
     */
    public function activate(User $user): User
    {
        return $this->transaction(function () use ($user) {
            $user->status = UserStatus::ACTIVE;

            // Remove suspension info from settings
            $settings = $user->settings ?? [];
            unset($settings['suspension_reason'], $settings['suspended_at']);
            $user->settings = empty($settings) ? null : $settings;

            $user->save();

            $this->log('User activated by admin', [
                'user_id' => $user->id,
            ]);

            return $user->fresh(['tenant']);
        });
    }

    /**
     * Reset user password (sends password reset email).
     */
    public function resetPassword(User $user): void
    {
        // Generate a random temporary password
        $temporaryPassword = \Illuminate\Support\Str::random(16);

        $this->transaction(function () use ($user, $temporaryPassword) {
            // Update user with temporary password
            $user->password = $temporaryPassword;
            $user->save();

            // Revoke existing tokens to force re-login
            $user->tokens()->delete();

            // Try to send password reset notification if configured
            try {
                $token = Password::createToken($user);
                $user->sendPasswordResetNotification($token);
            } catch (\Throwable) {
                // If notification fails, just log the action
                // The admin can communicate the reset to the user directly
            }
        });

        $this->log('Password reset initiated by admin', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Force set user password (for admin override).
     */
    public function forceSetPassword(User $user, string $password): void
    {
        $this->transaction(function () use ($user, $password) {
            $user->password = $password;
            $user->save();

            // Revoke all active tokens to force re-login
            $user->tokens()->delete();

            $this->log('Password force-set by admin', [
                'user_id' => $user->id,
            ]);
        });
    }

    /**
     * Get user statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', UserStatus::ACTIVE)->count();
        $suspendedUsers = User::where('status', UserStatus::SUSPENDED)->count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $mfaEnabledUsers = User::where('mfa_enabled', true)->count();

        // Users by status
        $byStatus = [];
        foreach (UserStatus::cases() as $status) {
            $byStatus[$status->value] = User::where('status', $status)->count();
        }

        // Users by role
        $byRole = [];
        foreach (TenantRole::cases() as $role) {
            $byRole[$role->value] = User::where('role_in_tenant', $role)->count();
        }

        // Active users in last 24h
        $activeRecently = User::where('last_active_at', '>=', now()->subDay())->count();

        // Active users in last 7 days
        $activeLastWeek = User::where('last_active_at', '>=', now()->subWeek())->count();

        return [
            'total' => $totalUsers,
            'active' => $activeUsers,
            'suspended' => $suspendedUsers,
            'verified' => $verifiedUsers,
            'mfa_enabled' => $mfaEnabledUsers,
            'active_last_24h' => $activeRecently,
            'active_last_7_days' => $activeLastWeek,
            'by_status' => $byStatus,
            'by_role' => $byRole,
        ];
    }
}
