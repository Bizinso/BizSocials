<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Platform\SuperAdminRole;
use App\Enums\Platform\SuperAdminStatus;
use App\Enums\Tenant\TenantStatus;
use App\Enums\Tenant\TenantType;
use App\Enums\User\TenantRole;
use App\Enums\User\UserStatus;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminTenantControllerTest extends TestCase
{
    use RefreshDatabase;

    private SuperAdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = SuperAdminUser::factory()->create([
            'role' => SuperAdminRole::SUPER_ADMIN,
            'status' => SuperAdminStatus::ACTIVE,
        ]);
    }

    public function test_can_list_tenants(): void
    {
        Tenant::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/tenants');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'type',
                        'status',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_can_filter_tenants_by_status(): void
    {
        Tenant::factory()->count(2)->create(['status' => TenantStatus::ACTIVE]);
        Tenant::factory()->count(1)->create(['status' => TenantStatus::SUSPENDED]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/tenants?status=active');

        $response->assertOk();
        $this->assertEquals(2, $response->json('meta.total'));
    }

    public function test_can_get_single_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/tenants/{$tenant->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $tenant->id)
            ->assertJsonPath('data.name', $tenant->name);
    }

    public function test_can_update_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/tenants/{$tenant->id}", [
                'name' => 'Updated Tenant Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Tenant Name');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Updated Tenant Name',
        ]);
    }

    public function test_can_suspend_tenant_with_reason(): void
    {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/tenants/{$tenant->id}/suspend", [
                'reason' => 'Violation of terms of service',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'suspended');

        $tenant->refresh();
        $this->assertEquals(TenantStatus::SUSPENDED, $tenant->status);
        $this->assertEquals('Violation of terms of service', $tenant->metadata['suspension_reason']);
    }

    public function test_suspend_requires_reason(): void
    {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/tenants/{$tenant->id}/suspend", []);

        $response->assertStatus(422);
    }

    public function test_can_activate_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::SUSPENDED]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/tenants/{$tenant->id}/activate");

        $response->assertOk()
            ->assertJsonPath('data.status', 'active');

        $tenant->refresh();
        $this->assertEquals(TenantStatus::ACTIVE, $tenant->status);
    }

    public function test_can_impersonate_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role_in_tenant' => TenantRole::OWNER,
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/tenants/{$tenant->id}/impersonate");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'expires_at',
                ],
            ]);
    }

    public function test_can_get_tenant_stats(): void
    {
        Tenant::factory()->count(5)->create(['status' => TenantStatus::ACTIVE]);
        Tenant::factory()->count(2)->create(['status' => TenantStatus::SUSPENDED]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/tenants/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total',
                    'active',
                    'suspended',
                    'by_status',
                ],
            ]);

        $this->assertEquals(7, $response->json('data.total'));
        $this->assertEquals(5, $response->json('data.active'));
    }

    public function test_returns_404_for_nonexistent_tenant(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/tenants/nonexistent-uuid');

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $response = $this->getJson('/api/v1/admin/tenants');

        $response->assertUnauthorized();
    }
}
