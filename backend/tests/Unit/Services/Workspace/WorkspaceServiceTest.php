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
