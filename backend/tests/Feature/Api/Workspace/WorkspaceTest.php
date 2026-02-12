<?php

declare(strict_types=1);

use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Enums\Workspace\WorkspaceStatus;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->admin = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::ADMIN,
    ]);
    $this->member = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::MEMBER,
    ]);
});

describe('GET /api/v1/workspaces', function () {
    it('returns list of workspaces for tenant', function () {
        Workspace::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'status' => WorkspaceStatus::ACTIVE,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/workspaces');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'tenant_id',
                        'name',
                        'slug',
                        'status',
                        'settings',
                        'member_count',
                        'created_at',
                    ],
                ],
                'meta',
            ]);
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

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/workspaces?status=suspended');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'suspended');
    });

    it('supports search', function () {
        Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Marketing Team',
        ]);
        Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sales Team',
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/workspaces?search=Marketing');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Marketing Team');
    });
});

describe('POST /api/v1/workspaces', function () {
    it('allows admin to create workspace', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/workspaces', [
            'name' => 'New Workspace',
            'description' => 'A test workspace',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Workspace')
            ->assertJsonPath('data.description', 'A test workspace');

        // Verify creator is added as owner
        $workspaceId = $response->json('data.id');
        $workspace = Workspace::find($workspaceId);
        expect($workspace->hasMember($this->admin->id))->toBeTrue();
        expect($workspace->getMemberRole($this->admin->id))->toBe(WorkspaceRole::OWNER);
    });

    it('denies member from creating workspace', function () {
        Sanctum::actingAs($this->member);

        $response = $this->postJson('/api/v1/workspaces', [
            'name' => 'Should Fail',
        ]);

        $response->assertForbidden();
    });

    it('validates required fields', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/workspaces', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('validates name length', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/workspaces', [
            'name' => str_repeat('a', 101),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });
});

describe('GET /api/v1/workspaces/{id}', function () {
    it('returns workspace details for member', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $workspace->addMember($this->member, WorkspaceRole::VIEWER);

        Sanctum::actingAs($this->member);

        $response = $this->getJson("/api/v1/workspaces/{$workspace->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $workspace->id);
    });

    it('allows tenant admin to view any workspace', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/workspaces/{$workspace->id}");

        $response->assertOk();
    });

    it('denies access for non-member non-admin', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        Sanctum::actingAs($this->member);

        $response = $this->getJson("/api/v1/workspaces/{$workspace->id}");

        $response->assertForbidden();
    });

    it('returns 404 for workspace from different tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $workspace = Workspace::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/v1/workspaces/{$workspace->id}");

        $response->assertNotFound();
    });
});

describe('PUT /api/v1/workspaces/{id}', function () {
    it('allows workspace admin to update', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $workspace->addMember($this->admin, WorkspaceRole::ADMIN);

        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/workspaces/{$workspace->id}", [
            'name' => 'Updated Workspace Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Workspace Name');
    });

    it('denies viewer from updating', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $workspace->addMember($this->member, WorkspaceRole::VIEWER);

        Sanctum::actingAs($this->member);

        $response = $this->putJson("/api/v1/workspaces/{$workspace->id}", [
            'name' => 'Should Fail',
        ]);

        $response->assertForbidden();
    });
});

describe('DELETE /api/v1/workspaces/{id}', function () {
    it('allows workspace owner to delete', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $workspace->addMember($this->owner, WorkspaceRole::OWNER);

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson("/api/v1/workspaces/{$workspace->id}");

        $response->assertOk();

        $workspace->refresh();
        expect($workspace->status)->toBe(WorkspaceStatus::DELETED);
        expect($workspace->trashed())->toBeTrue();
    });

    it('denies admin from deleting', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $workspace->addMember($this->admin, WorkspaceRole::ADMIN);

        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/workspaces/{$workspace->id}");

        $response->assertForbidden();
    });
});

describe('POST /api/v1/workspaces/{id}/archive', function () {
    it('allows workspace admin to archive', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => WorkspaceStatus::ACTIVE,
        ]);
        $workspace->addMember($this->admin, WorkspaceRole::ADMIN);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$workspace->id}/archive");

        $response->assertOk()
            ->assertJsonPath('data.status', 'suspended');
    });

    it('fails for already archived workspace', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => WorkspaceStatus::SUSPENDED,
        ]);
        $workspace->addMember($this->admin, WorkspaceRole::ADMIN);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$workspace->id}/archive");

        $response->assertStatus(422);
    });
});

describe('POST /api/v1/workspaces/{id}/restore', function () {
    it('allows workspace admin to restore', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => WorkspaceStatus::SUSPENDED,
        ]);
        $workspace->addMember($this->admin, WorkspaceRole::ADMIN);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$workspace->id}/restore");

        $response->assertOk()
            ->assertJsonPath('data.status', 'active');
    });

    it('fails for active workspace', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => WorkspaceStatus::ACTIVE,
        ]);
        $workspace->addMember($this->admin, WorkspaceRole::ADMIN);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$workspace->id}/restore");

        $response->assertStatus(422);
    });
});

describe('PUT /api/v1/workspaces/{id}/settings', function () {
    it('allows workspace admin to update settings', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $workspace->addMember($this->admin, WorkspaceRole::ADMIN);

        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/workspaces/{$workspace->id}/settings", [
            'timezone' => 'America/New_York',
            'approval_workflow' => [
                'enabled' => false,
            ],
        ]);

        $response->assertOk();

        $workspace->refresh();
        expect($workspace->getSetting('timezone'))->toBe('America/New_York');
    });
});
