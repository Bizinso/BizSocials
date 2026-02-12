<?php

declare(strict_types=1);

namespace App\Services\Workspace;

use App\Enums\Workspace\WorkspaceRole;
use App\Events\Workspace\MemberAdded;
use App\Events\Workspace\MemberRemoved;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class WorkspaceMembershipService extends BaseService
{
    /**
     * List members of a workspace.
     *
     * @param array<string, mixed> $filters
     */
    public function listMembers(Workspace $workspace, array $filters = []): LengthAwarePaginator
    {
        $query = WorkspaceMembership::where('workspace_id', $workspace->id)
            ->with('user');

        // Apply role filter
        if (!empty($filters['role'])) {
            $role = WorkspaceRole::tryFrom($filters['role']);
            if ($role !== null) {
                $query->where('role', $role);
            }
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = min($perPage, 100); // Max 100 per page

        return $query->orderBy('joined_at', 'desc')->paginate($perPage);
    }

    /**
     * Add a member to a workspace.
     */
    public function addMember(Workspace $workspace, User $user, WorkspaceRole $role): WorkspaceMembership
    {
        return $this->transaction(function () use ($workspace, $user, $role) {
            // Check if user belongs to the same tenant
            if ($user->tenant_id !== $workspace->tenant_id) {
                throw ValidationException::withMessages([
                    'user_id' => ['User does not belong to this organization.'],
                ]);
            }

            // Check if user is already a member
            $existingMembership = WorkspaceMembership::where('workspace_id', $workspace->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingMembership !== null) {
                throw ValidationException::withMessages([
                    'user_id' => ['User is already a member of this workspace.'],
                ]);
            }

            $membership = $workspace->addMember($user, $role);

            $this->log('Member added to workspace', [
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => $role->value,
            ]);

            event(new MemberAdded($workspace, $user));

            return $membership->load('user');
        });
    }

    /**
     * Update a member's role in a workspace.
     */
    public function updateRole(Workspace $workspace, User $user, WorkspaceRole $role): WorkspaceMembership
    {
        return $this->transaction(function () use ($workspace, $user, $role) {
            $membership = WorkspaceMembership::where('workspace_id', $workspace->id)
                ->where('user_id', $user->id)
                ->first();

            if ($membership === null) {
                throw ValidationException::withMessages([
                    'user_id' => ['User is not a member of this workspace.'],
                ]);
            }

            // Cannot change the owner's role if they are the only owner
            if ($membership->role === WorkspaceRole::OWNER && $role !== WorkspaceRole::OWNER) {
                $ownersCount = WorkspaceMembership::where('workspace_id', $workspace->id)
                    ->where('role', WorkspaceRole::OWNER)
                    ->count();

                if ($ownersCount === 1) {
                    throw ValidationException::withMessages([
                        'role' => ['Cannot change role of the only owner. Assign another owner first.'],
                    ]);
                }
            }

            $membership->updateRole($role);

            $this->log('Member role updated in workspace', [
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'new_role' => $role->value,
            ]);

            return $membership->load('user');
        });
    }

    /**
     * Remove a member from a workspace.
     */
    public function removeMember(Workspace $workspace, User $user): void
    {
        $this->transaction(function () use ($workspace, $user) {
            $membership = WorkspaceMembership::where('workspace_id', $workspace->id)
                ->where('user_id', $user->id)
                ->first();

            if ($membership === null) {
                throw ValidationException::withMessages([
                    'user_id' => ['User is not a member of this workspace.'],
                ]);
            }

            // Cannot remove the only owner
            if ($membership->role === WorkspaceRole::OWNER) {
                $ownersCount = WorkspaceMembership::where('workspace_id', $workspace->id)
                    ->where('role', WorkspaceRole::OWNER)
                    ->count();

                if ($ownersCount === 1) {
                    throw ValidationException::withMessages([
                        'user_id' => ['Cannot remove the only owner. Transfer ownership first.'],
                    ]);
                }
            }

            $workspace->removeMember($user->id);

            $this->log('Member removed from workspace', [
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
            ]);

            event(new MemberRemoved($workspace, $user->id));
        });
    }

    /**
     * Get all workspaces for a user.
     */
    public function getUserWorkspaces(User $user): Collection
    {
        return Workspace::forUser($user->id)
            ->where('tenant_id', $user->tenant_id)
            ->active()
            ->with(['memberships' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get();
    }

    /**
     * Check if a user has a specific permission in a workspace.
     */
    public function checkPermission(User $user, Workspace $workspace, string $permission): bool
    {
        $membership = WorkspaceMembership::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null) {
            return false;
        }

        return match ($permission) {
            'manage_workspace' => $membership->canManageWorkspace(),
            'manage_members' => $membership->canManageMembers(),
            'create_content' => $membership->canCreateContent(),
            'approve_content' => $membership->canApproveContent(),
            'publish_directly' => $membership->canPublishDirectly(),
            'view' => true, // All members can view
            default => false,
        };
    }

    /**
     * Get a user's membership in a workspace.
     */
    public function getMembership(Workspace $workspace, User $user): ?WorkspaceMembership
    {
        return WorkspaceMembership::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();
    }
}
