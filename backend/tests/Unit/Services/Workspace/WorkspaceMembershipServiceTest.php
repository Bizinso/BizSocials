<?php

declare(strict_types=1);

use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Workspace\WorkspaceMembershipService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = new WorkspaceMembershipService();
    $this->tenant = Tenant::factory()->active()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->workspace = Workspace::factory()->active()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->workspace->addMember($this->owner, WorkspaceRole::OWNER);
});

describe('listMembers', function () {
    it('returns paginated members', function () {
        $users = User::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        foreach ($users as $user) {
            $this->workspace->addMember($user, WorkspaceRole::VIEWER);
        }

        $result = $this->service->listMembers($this->workspace);

        expect($result->total())->toBe(4); // 3 + owner
    });

    it('filters by role', function () {
        $admin = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $viewer = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->workspace->addMember($admin, WorkspaceRole::ADMIN);
        $this->workspace->addMember($viewer, WorkspaceRole::VIEWER);

        $result = $this->service->listMembers($this->workspace, ['role' => 'admin']);

        expect($result->total())->toBe(1);
    });

    it('filters by search', function () {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $this->workspace->addMember($user, WorkspaceRole::VIEWER);

        $result = $this->service->listMembers($this->workspace, ['search' => 'john']);

        expect($result->total())->toBe(1);
    });
});

describe('addMember', function () {
    it('adds member with specified role', function () {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $membership = $this->service->addMember($this->workspace, $user, WorkspaceRole::EDITOR);

        expect($membership->user_id)->toBe($user->id);
        expect($membership->role)->toBe(WorkspaceRole::EDITOR);
    });

    it('throws exception for user from different tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        expect(fn () => $this->service->addMember($this->workspace, $otherUser, WorkspaceRole::VIEWER))
            ->toThrow(ValidationException::class);
    });

    it('throws exception for existing member', function () {
        expect(fn () => $this->service->addMember($this->workspace, $this->owner, WorkspaceRole::VIEWER))
            ->toThrow(ValidationException::class);
    });
});

describe('updateRole', function () {
    it('updates member role', function () {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->workspace->addMember($user, WorkspaceRole::VIEWER);

        $membership = $this->service->updateRole($this->workspace, $user, WorkspaceRole::EDITOR);

        expect($membership->role)->toBe(WorkspaceRole::EDITOR);
    });

    it('throws exception for non-member', function () {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        expect(fn () => $this->service->updateRole($this->workspace, $user, WorkspaceRole::EDITOR))
            ->toThrow(ValidationException::class);
    });

    it('throws exception when demoting only owner', function () {
        expect(fn () => $this->service->updateRole($this->workspace, $this->owner, WorkspaceRole::ADMIN))
            ->toThrow(ValidationException::class);
    });

    it('allows demoting owner when there are multiple owners', function () {
        $secondOwner = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->workspace->addMember($secondOwner, WorkspaceRole::OWNER);

        $membership = $this->service->updateRole($this->workspace, $this->owner, WorkspaceRole::ADMIN);

        expect($membership->role)->toBe(WorkspaceRole::ADMIN);
    });
});

describe('removeMember', function () {
    it('removes member from workspace', function () {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->workspace->addMember($user, WorkspaceRole::VIEWER);

        $this->service->removeMember($this->workspace, $user);

        expect($this->workspace->hasMember($user->id))->toBeFalse();
    });

    it('throws exception for non-member', function () {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        expect(fn () => $this->service->removeMember($this->workspace, $user))
            ->toThrow(ValidationException::class);
    });

    it('throws exception when removing only owner', function () {
        expect(fn () => $this->service->removeMember($this->workspace, $this->owner))
            ->toThrow(ValidationException::class);
    });

    it('allows removing owner when there are multiple owners', function () {
        $secondOwner = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->workspace->addMember($secondOwner, WorkspaceRole::OWNER);

        $this->service->removeMember($this->workspace, $this->owner);

        expect($this->workspace->hasMember($this->owner->id))->toBeFalse();
    });
});

describe('getUserWorkspaces', function () {
    it('returns workspaces for user', function () {
        // $this->workspace already has owner as member from beforeEach
        $workspace2 = Workspace::factory()->active()->create(['tenant_id' => $this->tenant->id]);
        $workspace2->addMember($this->owner, WorkspaceRole::ADMIN);

        $workspace3 = Workspace::factory()->active()->create(['tenant_id' => $this->tenant->id]);
        // Owner not a member of workspace3

        $result = $this->service->getUserWorkspaces($this->owner);

        // Should have 2: $this->workspace and $workspace2
        expect($result)->toHaveCount(2);
    });

    it('returns only active workspaces', function () {
        // $this->workspace is active from beforeEach
        $suspendedWorkspace = Workspace::factory()->suspended()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $suspendedWorkspace->addMember($this->owner, WorkspaceRole::ADMIN);

        $result = $this->service->getUserWorkspaces($this->owner);

        // Should have 1: only $this->workspace (active), not the suspended one
        expect($result)->toHaveCount(1);
    });
});

describe('checkPermission', function () {
    it('returns true for valid permission', function () {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->workspace->addMember($user, WorkspaceRole::ADMIN);

        expect($this->service->checkPermission($user, $this->workspace, 'manage_workspace'))->toBeTrue();
        expect($this->service->checkPermission($user, $this->workspace, 'manage_members'))->toBeTrue();
        expect($this->service->checkPermission($user, $this->workspace, 'create_content'))->toBeTrue();
    });

    it('returns false for non-member', function () {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        expect($this->service->checkPermission($user, $this->workspace, 'view'))->toBeFalse();
    });

    it('checks role-specific permissions', function () {
        $viewer = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->workspace->addMember($viewer, WorkspaceRole::VIEWER);

        expect($this->service->checkPermission($viewer, $this->workspace, 'view'))->toBeTrue();
        expect($this->service->checkPermission($viewer, $this->workspace, 'create_content'))->toBeFalse();
        expect($this->service->checkPermission($viewer, $this->workspace, 'manage_members'))->toBeFalse();
    });
});

describe('getMembership', function () {
    it('returns membership for member', function () {
        $result = $this->service->getMembership($this->workspace, $this->owner);

        expect($result)->not->toBeNull();
        expect($result->role)->toBe(WorkspaceRole::OWNER);
    });

    it('returns null for non-member', function () {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $result = $this->service->getMembership($this->workspace, $user);

        expect($result)->toBeNull();
    });
});
