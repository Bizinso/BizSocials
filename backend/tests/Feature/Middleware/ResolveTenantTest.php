<?php

declare(strict_types=1);

use App\Enums\Tenant\TenantStatus;
use App\Enums\User\TenantRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
});

describe('ResolveTenant middleware', function () {
    it('allows requests from users with active tenants', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/user');

        $response->assertOk();
    });

    it('blocks requests when tenant is suspended', function () {
        $this->tenant->update(['status' => TenantStatus::SUSPENDED]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/user');

        $response->assertForbidden()
            ->assertJsonPath('message', 'Your organization has been suspended. Please contact support.');
    });

    it('blocks requests when tenant is terminated', function () {
        $this->tenant->update(['status' => TenantStatus::TERMINATED]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/user');

        $response->assertForbidden()
            ->assertJsonPath('message', 'Your organization has been terminated.');
    });

    it('binds tenant to app container as current_tenant', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/user');

        $response->assertOk();
        expect(app('current_tenant'))->toBeInstanceOf(Tenant::class);
        expect(app('current_tenant')->id)->toBe($this->tenant->id);
    });
});
