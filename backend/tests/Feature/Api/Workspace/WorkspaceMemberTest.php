<?php

declare(strict_types=1);

use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
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

    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->workspace->addMember($this->owner, WorkspaceRole::OWNER);
    $this->workspace->addMember($this->admin, WorkspaceRole::ADMIN);
});

describe('GET /api/v1/workspaces/{id}/members', function () {
    it('returns list of workspace members', function () {
        $this->workspace->addMember($this->member, WorkspaceRole::VIEWER);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/members");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'name',
                        'email',
                        'role',
                        'joined_at',
                    ],
                ],
                'meta',
            ]);
    });

    it('allows filtering by role', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/members?role=admin");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.role', 'admin');
    });

    it('allows searching by name or email', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/members?search={$this->owner->email}");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('denies access for non-member', function () {
        Sanctum::actingAs($this->member);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/members");

        $response->assertForbidden();
    });
});

describe('POST /api/v1/workspaces/{id}/members', function () {
    it('allows admin to add member', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/members", [
            'user_id' => $this->member->id,
            'role' => 'editor',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $this->member->id)
            ->assertJsonPath('data.role', 'editor');

        expect($this->workspace->hasMember($this->member->id))->toBeTrue();
    });

    it('uses default role when not specified', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/members", [
            'user_id' => $this->member->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.role', 'viewer');
    });

    it('denies viewer from adding members', function () {
        $this->workspace->addMember($this->member, WorkspaceRole::VIEWER);

        Sanctum::actingAs($this->member);

        $anotherMember = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/members", [
            'user_id' => $anotherMember->id,
        ]);

        $response->assertForbidden();
    });

    it('prevents adding user from different tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/members", [
            'user_id' => $otherUser->id,
        ]);

        $response->assertStatus(422);
    });

    it('prevents adding existing member', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/members", [
            'user_id' => $this->owner->id,
        ]);

        $response->assertStatus(422);
    });
});

describe('PUT /api/v1/workspaces/{id}/members/{userId}', function () {
    it('allows admin to update member role', function () {
        $this->workspace->addMember($this->member, WorkspaceRole::VIEWER);

        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/members/{$this->member->id}", [
            'role' => 'editor',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.role', 'editor');

        expect($this->workspace->getMemberRole($this->member->id))->toBe(WorkspaceRole::EDITOR);
    });

    it('prevents changing own role', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/members/{$this->admin->id}", [
            'role' => 'viewer',
        ]);

        $response->assertStatus(422);
    });

    it('prevents demoting the only owner', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/members/{$this->owner->id}", [
            'role' => 'admin',
        ]);

        $response->assertStatus(422);
    });

    it('returns 404 for non-member', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/members/{$this->member->id}", [
            'role' => 'editor',
        ]);

        $response->assertStatus(422);
    });

    it('validates role value', function () {
        $this->workspace->addMember($this->member, WorkspaceRole::VIEWER);

        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/members/{$this->member->id}", [
            'role' => 'invalid_role',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    });
});

describe('DELETE /api/v1/workspaces/{id}/members/{userId}', function () {
    it('allows admin to remove member', function () {
        $this->workspace->addMember($this->member, WorkspaceRole::EDITOR);

        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/members/{$this->member->id}");

        $response->assertOk();

        expect($this->workspace->hasMember($this->member->id))->toBeFalse();
    });

    it('prevents removing self', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/members/{$this->admin->id}");

        $response->assertStatus(422);
    });

    it('prevents removing the only owner', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/members/{$this->owner->id}");

        $response->assertStatus(422);
    });

    it('denies viewer from removing members', function () {
        $this->workspace->addMember($this->member, WorkspaceRole::VIEWER);

        $anotherMember = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->workspace->addMember($anotherMember, WorkspaceRole::EDITOR);

        Sanctum::actingAs($this->member);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/members/{$anotherMember->id}");

        $response->assertForbidden();
    });
});
