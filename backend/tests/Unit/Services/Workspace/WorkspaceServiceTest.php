<?php

declare(strict_types=1);

use App\Data\Workspace\CreateWorkspaceData;
use App\Data\Workspace\UpdateWorkspaceData;
use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Enums\Workspace\WorkspaceStatus;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Workspace\WorkspaceService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = new WorkspaceService();
    $this->tenant = Tenant::factory()->active()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
});

describe('listForTenant', function () {
    it('returns paginated workspaces', function () {
        Workspace::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => WorkspaceStatus::ACTIVE,
        ]);

        $result = $this->service->listForTenant($this->tenant);

        expect($result->total())->toBe(5);
    });

    it('excludes deleted workspaces by default', function () {
        Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => WorkspaceStatus::ACTIVE,
        ]);
        Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => WorkspaceStatus::DELETED,
        ]);

        $result = $this->service->listForTenant($this->tenant);

        expect($result->total())->toBe(1);
    });

    it('filters by status', function () {
        Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => WorkspaceStatus::ACTIVE,
        ]);
        Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => WorkspaceStatus::SUSPENDED,
        ]);

        $result = $this->service->listForTenant($this->tenant, ['status' => 'suspended']);

        expect($result->total())->toBe(1);
    });

    it('filters by search', function () {
        Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Marketing Team',
        ]);
        Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sales Team',
        ]);

        $result = $this->service->listForTenant($this->tenant, ['search' => 'Marketing']);

        expect($result->total())->toBe(1);
    });
});

describe('create', function () {
    it('creates workspace with creator as owner', function () {
        $data = new CreateWorkspaceData(
            name: 'New Workspace',
            description: 'A test workspace',
        );

        $workspace = $this->service->create($this->tenant, $this->owner, $data);

        expect($workspace->name)->toBe('New Workspace');
        expect($workspace->description)->toBe('A test workspace');
        expect($workspace->tenant_id)->toBe($this->tenant->id);
        expect($workspace->status)->toBe(WorkspaceStatus::ACTIVE);
        expect($workspace->hasMember($this->owner->id))->toBeTrue();
        expect($workspace->getMemberRole($this->owner->id))->toBe(WorkspaceRole::OWNER);
    });

    it('includes icon and color in settings', function () {
        $data = new CreateWorkspaceData(
            name: 'Styled Workspace',
            icon: 'rocket',
            color: '#FF5733',
        );

        $workspace = $this->service->create($this->tenant, $this->owner, $data);

        expect($workspace->getSetting('icon'))->toBe('rocket');
        expect($workspace->getSetting('color'))->toBe('#FF5733');
    });

    it('generates slug automatically', function () {
        $data = new CreateWorkspaceData(name: 'My Test Workspace');

        $workspace = $this->service->create($this->tenant, $this->owner, $data);

        expect($workspace->slug)->toBe('my-test-workspace');
    });
});

describe('get', function () {
    it('returns workspace by ID', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $result = $this->service->get($workspace->id);

        expect($result->id)->toBe($workspace->id);
    });

    it('throws exception for non-existent workspace', function () {
        expect(fn () => $this->service->get('00000000-0000-0000-0000-000000000000'))
            ->toThrow(ValidationException::class);
    });
});

describe('update', function () {
    it('updates workspace name', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $data = new UpdateWorkspaceData(name: 'Updated Name');

        $result = $this->service->update($workspace, $data);

        expect($result->name)->toBe('Updated Name');
    });

    it('updates workspace description', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'description' => 'Old description',
        ]);

        $data = new UpdateWorkspaceData(description: 'New description');

        $result = $this->service->update($workspace, $data);

        expect($result->description)->toBe('New description');
    });

    it('updates icon and color in settings', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $data = new UpdateWorkspaceData(icon: 'star', color: '#00FF00');

        $result = $this->service->update($workspace, $data);

        expect($result->getSetting('icon'))->toBe('star');
        expect($result->getSetting('color'))->toBe('#00FF00');
    });
});

describe('updateSettings', function () {
    it('merges settings', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'settings' => ['existing' => 'value'],
        ]);

        $result = $this->service->updateSettings($workspace, ['new_key' => 'new_value']);

        expect($result->getSetting('existing'))->toBe('value');
        expect($result->getSetting('new_key'))->toBe('new_value');
    });
});

describe('delete', function () {
    it('soft deletes workspace', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->service->delete($workspace);

        $workspace->refresh();
        expect($workspace->status)->toBe(WorkspaceStatus::DELETED);
        expect($workspace->trashed())->toBeTrue();
    });
});

describe('archive', function () {
    it('archives active workspace', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => WorkspaceStatus::ACTIVE,
        ]);

        $result = $this->service->archive($workspace);

        expect($result->status)->toBe(WorkspaceStatus::SUSPENDED);
    });

    it('throws exception for non-active workspace', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => WorkspaceStatus::SUSPENDED,
        ]);

        expect(fn () => $this->service->archive($workspace))
            ->toThrow(ValidationException::class);
    });
});

describe('restore', function () {
    it('restores archived workspace', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => WorkspaceStatus::SUSPENDED,
        ]);

        $result = $this->service->restore($workspace);

        expect($result->status)->toBe(WorkspaceStatus::ACTIVE);
    });

    it('throws exception for active workspace', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => WorkspaceStatus::ACTIVE,
        ]);

        expect(fn () => $this->service->restore($workspace))
            ->toThrow(ValidationException::class);
    });
});

describe('switchWorkspace', function () {
    it('switches to workspace for member', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => WorkspaceStatus::ACTIVE,
        ]);
        $workspace->addMember($this->owner, WorkspaceRole::VIEWER);

        $result = $this->service->switchWorkspace($this->owner, $workspace);

        expect($result->id)->toBe($workspace->id);
        expect(session('current_workspace_id'))->toBe($workspace->id);
        expect(app('current_workspace')->id)->toBe($workspace->id);
    });

    it('throws exception for workspace from different tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $workspace = Workspace::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        expect(fn () => $this->service->switchWorkspace($this->owner, $workspace))
            ->toThrow(ValidationException::class);
    });

    it('throws exception for non-member', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        expect(fn () => $this->service->switchWorkspace($this->owner, $workspace))
            ->toThrow(ValidationException::class);
    });

    it('throws exception for inaccessible workspace', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => WorkspaceStatus::SUSPENDED,
        ]);
        $workspace->addMember($this->owner, WorkspaceRole::VIEWER);

        expect(fn () => $this->service->switchWorkspace($this->owner, $workspace))
            ->toThrow(ValidationException::class);
    });
});

describe('getCurrentWorkspace', function () {
    it('returns current workspace from session', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $workspace->addMember($this->owner, WorkspaceRole::VIEWER);

        session(['current_workspace_id' => $workspace->id]);

        $result = $this->service->getCurrentWorkspace($this->owner);

        expect($result)->not->toBeNull();
        expect($result->id)->toBe($workspace->id);
    });

    it('returns null when no workspace in session', function () {
        $result = $this->service->getCurrentWorkspace($this->owner);

        expect($result)->toBeNull();
    });

    it('clears invalid workspace from session', function () {
        session(['current_workspace_id' => '00000000-0000-0000-0000-000000000000']);

        $result = $this->service->getCurrentWorkspace($this->owner);

        expect($result)->toBeNull();
        expect(session('current_workspace_id'))->toBeNull();
    });

    it('clears workspace when user no longer has access', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $workspace->addMember($this->owner, WorkspaceRole::VIEWER);

        session(['current_workspace_id' => $workspace->id]);

        // Remove user from workspace
        $workspace->removeMember($this->owner->id);

        $result = $this->service->getCurrentWorkspace($this->owner);

        expect($result)->toBeNull();
        expect(session('current_workspace_id'))->toBeNull();
    });

    it('returns cached workspace from container', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        app()->instance('current_workspace', $workspace);

        $result = $this->service->getCurrentWorkspace($this->owner);

        expect($result->id)->toBe($workspace->id);
    });
});

describe('clearCurrentWorkspace', function () {
    it('clears workspace from session and container', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        session(['current_workspace_id' => $workspace->id]);
        app()->instance('current_workspace', $workspace);

        $this->service->clearCurrentWorkspace();

        expect(session('current_workspace_id'))->toBeNull();
        expect(app()->has('current_workspace'))->toBeFalse();
    });
});

describe('tenant isolation', function () {
    it('only lists workspaces for specific tenant', function () {
        $otherTenant = Tenant::factory()->create();

        Workspace::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);
        Workspace::factory()->count(2)->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $result = $this->service->listForTenant($this->tenant);

        expect($result->total())->toBe(3);
    });

    it('prevents creating workspace for wrong tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $data = new CreateWorkspaceData(name: 'Test Workspace');

        $workspace = $this->service->create($this->tenant, $this->owner, $data);

        expect($workspace->tenant_id)->toBe($this->tenant->id);
        expect($workspace->tenant_id)->not->toBe($otherTenant->id);
    });
});

describe('member management', function () {
    it('adds creator as owner when creating workspace', function () {
        $data = new CreateWorkspaceData(name: 'Test Workspace');

        $workspace = $this->service->create($this->tenant, $this->owner, $data);

        expect($workspace->hasMember($this->owner->id))->toBeTrue();
        expect($workspace->getMemberRole($this->owner->id))->toBe(WorkspaceRole::OWNER);
    });

    it('tracks member count correctly', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $user1 = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user2 = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $workspace->addMember($user1, WorkspaceRole::EDITOR);
        $workspace->addMember($user2, WorkspaceRole::VIEWER);

        expect($workspace->getMemberCount())->toBe(2);
    });
});
