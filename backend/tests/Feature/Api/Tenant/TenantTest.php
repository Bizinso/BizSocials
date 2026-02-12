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

describe('GET /api/v1/tenants/current', function () {
    it('returns the current tenant for authenticated user', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/tenants/current');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'type',
                    'status',
                    'settings',
                    'created_at',
                ],
            ])
            ->assertJsonPath('data.id', $this->tenant->id);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/tenants/current');

        $response->assertUnauthorized();
    });
});

describe('PUT /api/v1/tenants/current', function () {
    it('allows owner to update tenant', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->putJson('/api/v1/tenants/current', [
            'name' => 'Updated Tenant Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Tenant Name');
    });

    it('allows admin to update tenant', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson('/api/v1/tenants/current', [
            'name' => 'Updated by Admin',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated by Admin');
    });

    it('denies member from updating tenant', function () {
        Sanctum::actingAs($this->member);

        $response = $this->putJson('/api/v1/tenants/current', [
            'name' => 'Should Fail',
        ]);

        $response->assertForbidden();
    });

    it('validates input data', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->putJson('/api/v1/tenants/current', [
            'name' => str_repeat('a', 101), // Too long
            'website' => 'not-a-url',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'website']);
    });
});

describe('PUT /api/v1/tenants/current/settings', function () {
    it('allows admin to update settings', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson('/api/v1/tenants/current/settings', [
            'timezone' => 'America/New_York',
            'language' => 'en',
        ]);

        $response->assertOk();

        $this->tenant->refresh();
        expect($this->tenant->getSetting('timezone'))->toBe('America/New_York');
    });

    it('denies member from updating settings', function () {
        Sanctum::actingAs($this->member);

        $response = $this->putJson('/api/v1/tenants/current/settings', [
            'timezone' => 'America/New_York',
        ]);

        $response->assertForbidden();
    });
});

describe('GET /api/v1/tenants/current/stats', function () {
    it('returns usage statistics', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/tenants/current/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'users',
                    'workspaces',
                    'social_accounts',
                    'storage',
                ],
            ]);
    });
});
