<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace\Workspace;

final class WorkspacePolicy
{
    /**
     * Determine if the user can view the workspace.
     */
    public function view(User $user, Workspace $workspace): bool
    {
        // Must be same tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Tenant admins can view any workspace
        if ($user->isAdmin()) {
            return true;
        }

        // Must be a member
        return $workspace->hasMember($user->id);
    }

    /**
     * Determine if the user can update the workspace.
     */
    public function update(User $user, Workspace $workspace): bool
    {
        // Must be same tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Tenant admins can update any workspace
        if ($user->isAdmin()) {
            return true;
        }

        // Must be a member with admin+ role
        $role = $workspace->getMemberRole($user->id);

        if ($role === null) {
            return false;
        }

        return $role->canManageWorkspace();
    }
}
