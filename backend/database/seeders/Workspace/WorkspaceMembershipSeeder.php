<?php

declare(strict_types=1);

namespace Database\Seeders\Workspace;

use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;
use Illuminate\Database\Seeder;

/**
 * Seeder for WorkspaceMembership model.
 *
 * Assigns users to workspaces with appropriate roles based on their tenant role.
 */
final class WorkspaceMembershipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all()->keyBy('slug');

        // 1. Acme Corporation - Assign all users to all workspaces with appropriate roles
        $acmeTenant = $tenants->get('acme-corporation');
        if ($acmeTenant) {
            $acmeWorkspaces = Workspace::where('tenant_id', $acmeTenant->id)->get();
            $acmeUsers = User::where('tenant_id', $acmeTenant->id)->get();

            foreach ($acmeWorkspaces as $workspace) {
                foreach ($acmeUsers as $user) {
                    $this->createMembership($workspace, $user, $this->mapTenantRoleToWorkspaceRole($user->role_in_tenant));
                }
            }

            // Override Eve Viewer to VIEWER workspace role (auto-mapping gives EDITOR for MEMBER)
            $eveViewer = User::where('tenant_id', $acmeTenant->id)
                ->where('email', 'eve.viewer@acme.example.com')
                ->first();
            if ($eveViewer) {
                WorkspaceMembership::where('user_id', $eveViewer->id)
                    ->update(['role' => WorkspaceRole::VIEWER]);
            }
        }

        // 2. StartupXYZ - Assign users to main workspace
        $startupTenant = $tenants->get('startupxyz');
        if ($startupTenant) {
            $startupWorkspace = Workspace::where('tenant_id', $startupTenant->id)->first();
            $startupUsers = User::where('tenant_id', $startupTenant->id)->get();

            if ($startupWorkspace) {
                foreach ($startupUsers as $user) {
                    $this->createMembership($startupWorkspace, $user, $this->mapTenantRoleToWorkspaceRole($user->role_in_tenant));
                }
            }
        }

        // 3. Fashion Brand Co - Assign owner to both workspaces
        $fashionTenant = $tenants->get('fashion-brand-co');
        if ($fashionTenant) {
            $fashionWorkspaces = Workspace::where('tenant_id', $fashionTenant->id)->get();
            $fashionUsers = User::where('tenant_id', $fashionTenant->id)->get();

            foreach ($fashionWorkspaces as $workspace) {
                foreach ($fashionUsers as $user) {
                    $this->createMembership($workspace, $user, $this->mapTenantRoleToWorkspaceRole($user->role_in_tenant));
                }
            }
        }

        // 4. John Freelancer - Assign owner to personal workspace
        $freelancerTenant = $tenants->get('john-freelancer');
        if ($freelancerTenant) {
            $freelancerWorkspace = Workspace::where('tenant_id', $freelancerTenant->id)->first();
            $freelancerUser = User::where('tenant_id', $freelancerTenant->id)->first();

            if ($freelancerWorkspace && $freelancerUser) {
                $this->createMembership($freelancerWorkspace, $freelancerUser, WorkspaceRole::OWNER);
            }
        }

        // 5. Sarah Lifestyle - Assign owner to content workspace
        $influencerTenant = $tenants->get('sarah-lifestyle');
        if ($influencerTenant) {
            $influencerWorkspace = Workspace::where('tenant_id', $influencerTenant->id)->first();
            $influencerUser = User::where('tenant_id', $influencerTenant->id)->first();

            if ($influencerWorkspace && $influencerUser) {
                $this->createMembership($influencerWorkspace, $influencerUser, WorkspaceRole::OWNER);
            }
        }

        // 6. Green Earth Foundation - Assign users to workspaces
        $ngoTenant = $tenants->get('green-earth-foundation');
        if ($ngoTenant) {
            $ngoWorkspaces = Workspace::where('tenant_id', $ngoTenant->id)->get();
            $ngoUsers = User::where('tenant_id', $ngoTenant->id)->get();

            foreach ($ngoWorkspaces as $workspace) {
                foreach ($ngoUsers as $user) {
                    $this->createMembership($workspace, $user, $this->mapTenantRoleToWorkspaceRole($user->role_in_tenant));
                }
            }
        }

        $this->command->info('Workspace memberships seeded successfully.');
    }

    /**
     * Create a workspace membership.
     */
    private function createMembership(Workspace $workspace, User $user, WorkspaceRole $role): WorkspaceMembership
    {
        return WorkspaceMembership::firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
            ],
            [
                'role' => $role,
                'joined_at' => now(),
            ]
        );
    }

    /**
     * Map tenant role to workspace role.
     */
    private function mapTenantRoleToWorkspaceRole(TenantRole $tenantRole): WorkspaceRole
    {
        return match ($tenantRole) {
            TenantRole::OWNER => WorkspaceRole::OWNER,
            TenantRole::ADMIN => WorkspaceRole::ADMIN,
            TenantRole::MEMBER => WorkspaceRole::EDITOR,
        };
    }
}
