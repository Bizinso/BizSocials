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
    $this->member = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::MEMBER,
    ]);
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->workspace->addMember($this->member, WorkspaceRole::EDITOR);
});

describe('EnsureWorkspaceMember middleware', function () {
    it('allows workspace members through', function () {
        Sanctum::actingAs($this->member);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/dashboard");

        $response->assertOk();
    });

    it('allows tenant admins even if not workspace member', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/dashboard");

        $response->assertOk();
    });

    it('blocks non-members from workspace routes', function () {
        $otherTenant = Tenant::factory()->active()->create();
        $outsider = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        Sanctum::actingAs($outsider);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/dashboard");

        $response->assertNotFound();
    });

    it('requires authentication', function () {
        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/dashboard");

        $response->assertUnauthorized();
    });
});
