<?php

declare(strict_types=1);

use App\Enums\User\TenantRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
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

describe('GET /api/v1/tenants/current/members', function () {
    it('returns list of tenant members', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/tenants/current/members');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'status',
                        'joined_at',
                    ],
                ],
                'meta',
            ]);
    });

    it('allows filtering by role', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/tenants/current/members?role=admin');

        $response->assertOk();

        foreach ($response->json('data') as $member) {
            expect($member['role'])->toBe('admin');
        }
    });

    it('allows searching by name or email', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/tenants/current/members?search=' . $this->admin->email);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->admin->id);
    });
});

describe('PUT /api/v1/tenants/current/members/{userId}', function () {
    it('allows admin to update member role', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/tenants/current/members/{$this->member->id}", [
            'role' => 'admin',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.role', 'admin');

        $this->member->refresh();
        expect($this->member->role_in_tenant)->toBe(TenantRole::ADMIN);
    });

    it('denies member from updating roles', function () {
        Sanctum::actingAs($this->member);

        $response = $this->putJson("/api/v1/tenants/current/members/{$this->admin->id}", [
            'role' => 'member',
        ]);

        $response->assertForbidden();
    });

    it('prevents changing own role', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->putJson("/api/v1/tenants/current/members/{$this->owner->id}", [
            'role' => 'admin',
        ]);

        $response->assertStatus(422);
    });

    it('prevents demoting the only owner', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/tenants/current/members/{$this->owner->id}", [
            'role' => 'member',
        ]);

        $response->assertStatus(422);
    });

    it('returns 404 for non-existent member', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->putJson('/api/v1/tenants/current/members/00000000-0000-0000-0000-000000000000', [
            'role' => 'admin',
        ]);

        $response->assertNotFound();
    });
});

describe('DELETE /api/v1/tenants/current/members/{userId}', function () {
    it('allows admin to remove member', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/tenants/current/members/{$this->member->id}");

        $response->assertOk();

        $this->member->refresh();
        expect($this->member->trashed())->toBeTrue();
    });

    it('denies member from removing others', function () {
        $anotherMember = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        Sanctum::actingAs($this->member);

        $response = $this->deleteJson("/api/v1/tenants/current/members/{$anotherMember->id}");

        $response->assertForbidden();
    });

    it('prevents removing self', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/tenants/current/members/{$this->admin->id}");

        $response->assertStatus(422);
    });

    it('prevents removing the only owner', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/tenants/current/members/{$this->owner->id}");

        $response->assertStatus(422);
    });
});
