<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Platform\SuperAdminRole;
use App\Enums\Platform\SuperAdminStatus;
use App\Enums\User\TenantRole;
use App\Enums\User\UserStatus;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    private SuperAdminUser $admin;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = SuperAdminUser::factory()->create([
            'role' => SuperAdminRole::SUPER_ADMIN,
            'status' => SuperAdminStatus::ACTIVE,
        ]);

        $this->tenant = Tenant::factory()->create();
    }

    public function test_can_list_users(): void
    {
        User::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'status',
                        'role_in_tenant',
                        'tenant_id',
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

    public function test_can_filter_users_by_status(): void
    {
        User::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'status' => UserStatus::ACTIVE,
        ]);
        User::factory()->count(1)->create([
            'tenant_id' => $this->tenant->id,
            'status' => UserStatus::SUSPENDED,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users?status=active');

        $response->assertOk();
        $this->assertEquals(2, $response->json('meta.total'));
    }

    public function test_can_filter_users_by_tenant(): void
    {
        $anotherTenant = Tenant::factory()->create();

        User::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);
        User::factory()->count(2)->create(['tenant_id' => $anotherTenant->id]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/users?tenant_id={$this->tenant->id}");

        $response->assertOk();
        $this->assertEquals(3, $response->json('meta.total'));
    }

    public function test_can_get_single_user(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/users/{$user->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_can_update_user(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/users/{$user->id}", [
                'name' => 'Updated User Name',
                'role_in_tenant' => 'admin',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated User Name')
            ->assertJsonPath('data.role_in_tenant', 'admin');
    }

    public function test_can_suspend_user_with_reason(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$user->id}/suspend", [
                'reason' => 'Suspicious activity detected',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'suspended');

        $user->refresh();
        $this->assertEquals(UserStatus::SUSPENDED, $user->status);
    }

    public function test_suspend_requires_reason(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$user->id}/suspend", []);

        $response->assertStatus(422);
    }

    public function test_can_activate_user(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => UserStatus::SUSPENDED,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$user->id}/activate");

        $response->assertOk()
            ->assertJsonPath('data.status', 'active');

        $user->refresh();
        $this->assertEquals(UserStatus::ACTIVE, $user->status);
    }

    public function test_can_reset_user_password(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'password' => 'original-password',
        ]);

        // Create a token so we can verify it gets revoked
        $user->createToken('test-token');
        $this->assertCount(1, $user->tokens);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$user->id}/reset-password");

        $response->assertOk()
            ->assertJsonPath('message', 'Password reset email sent successfully');

        // Verify tokens were revoked
        $user->refresh();
        $this->assertCount(0, $user->tokens);
    }

    public function test_can_get_user_stats(): void
    {
        User::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => UserStatus::ACTIVE,
        ]);
        User::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'status' => UserStatus::SUSPENDED,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total',
                    'active',
                    'suspended',
                    'by_status',
                    'by_role',
                ],
            ]);

        $this->assertEquals(7, $response->json('data.total'));
        $this->assertEquals(5, $response->json('data.active'));
    }

    public function test_returns_404_for_nonexistent_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users/nonexistent-uuid');

        $response->assertNotFound();
    }
}
