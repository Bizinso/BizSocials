<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use App\Data\Admin\UpdateTenantAdminData;
use App\Enums\Tenant\TenantStatus;
use App\Enums\User\TenantRole;
use App\Enums\User\UserStatus;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Admin\AdminTenantService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminTenantServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminTenantService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminTenantService();
    }

    public function test_list_returns_paginated_tenants(): void
    {
        Tenant::factory()->count(5)->create();

        $result = $this->service->list(['per_page' => 3]);

        $this->assertEquals(3, $result->perPage());
        $this->assertEquals(5, $result->total());
    }

    public function test_list_filters_by_status(): void
    {
        Tenant::factory()->count(3)->create(['status' => TenantStatus::ACTIVE]);
        Tenant::factory()->count(2)->create(['status' => TenantStatus::SUSPENDED]);

        $result = $this->service->list(['status' => 'active']);

        $this->assertEquals(3, $result->total());
    }

    public function test_list_searches_by_name(): void
    {
        Tenant::factory()->create(['name' => 'Alpha Company']);
        Tenant::factory()->create(['name' => 'Beta Corp']);
        Tenant::factory()->create(['name' => 'Alpha Industries']);

        $result = $this->service->list(['search' => 'Alpha']);

        $this->assertEquals(2, $result->total());
    }

    public function test_get_returns_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $result = $this->service->get($tenant->id);

        $this->assertEquals($tenant->id, $result->id);
    }

    public function test_get_throws_exception_for_nonexistent_tenant(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->get('nonexistent-id');
    }

    public function test_update_modifies_tenant(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Old Name']);

        $data = new UpdateTenantAdminData(name: 'New Name');
        $result = $this->service->update($tenant, $data);

        $this->assertEquals('New Name', $result->name);
    }

    public function test_update_merges_settings(): void
    {
        $tenant = Tenant::factory()->create(['settings' => ['key1' => 'value1']]);

        $data = new UpdateTenantAdminData(settings: ['key2' => 'value2']);
        $result = $this->service->update($tenant, $data);

        $this->assertEquals('value1', $result->settings['key1']);
        $this->assertEquals('value2', $result->settings['key2']);
    }

    public function test_suspend_changes_status(): void
    {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);

        $result = $this->service->suspend($tenant, 'Test reason');

        $this->assertEquals(TenantStatus::SUSPENDED, $result->status);
        $this->assertEquals('Test reason', $result->metadata['suspension_reason']);
        $this->assertNotNull($result->metadata['suspended_at']);
    }

    public function test_activate_changes_status(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => TenantStatus::SUSPENDED,
            'metadata' => ['suspension_reason' => 'reason', 'suspended_at' => now()->toIso8601String()],
        ]);

        $result = $this->service->activate($tenant);

        $this->assertEquals(TenantStatus::ACTIVE, $result->status);
        $this->assertArrayNotHasKey('suspension_reason', $result->metadata ?? []);
    }

    public function test_impersonate_returns_token(): void
    {
        $admin = SuperAdminUser::factory()->create();
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role_in_tenant' => TenantRole::OWNER,
            'status' => UserStatus::ACTIVE,
        ]);

        $token = $this->service->impersonate($tenant, $admin);

        $this->assertNotEmpty($token);
    }

    public function test_impersonate_throws_when_no_user_found(): void
    {
        $admin = SuperAdminUser::factory()->create();
        $tenant = Tenant::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        $this->service->impersonate($tenant, $admin);
    }

    public function test_get_stats_returns_correct_counts(): void
    {
        Tenant::factory()->count(3)->create(['status' => TenantStatus::ACTIVE]);
        Tenant::factory()->count(2)->create(['status' => TenantStatus::SUSPENDED]);

        $stats = $this->service->getStats();

        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(3, $stats['active']);
        $this->assertEquals(2, $stats['suspended']);
    }
}
