<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Billing\SubscriptionStatus;
use App\Enums\Platform\SuperAdminRole;
use App\Enums\Platform\SuperAdminStatus;
use App\Enums\Tenant\TenantStatus;
use App\Enums\User\UserStatus;
use App\Models\Billing\Subscription;
use App\Models\Platform\PlanDefinition;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminDashboardControllerTest extends TestCase
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

    public function test_can_get_dashboard_stats(): void
    {
        // Create test data
        $plan = PlanDefinition::factory()->create();
        $tenants = Tenant::factory()->count(5)->create([
            'status' => TenantStatus::ACTIVE,
            'plan_id' => $plan->id,
        ]);

        foreach ($tenants as $tenant) {
            User::factory()->count(2)->create([
                'tenant_id' => $tenant->id,
                'status' => UserStatus::ACTIVE,
            ]);

            Workspace::factory()->create([
                'tenant_id' => $tenant->id,
            ]);

            Subscription::factory()->create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::ACTIVE,
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/dashboard/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_tenants',
                    'active_tenants',
                    'total_users',
                    'active_users',
                    'total_workspaces',
                    'total_subscriptions',
                    'active_subscriptions',
                    'tenants_by_status',
                    'tenants_by_plan',
                    'users_by_status',
                    'signups_by_month',
                    'subscriptions_by_status',
                    'generated_at',
                ],
            ]);

        $this->assertEquals(5, $response->json('data.total_tenants'));
        $this->assertEquals(10, $response->json('data.total_users'));
        $this->assertEquals(5, $response->json('data.total_workspaces'));
    }

    public function test_can_get_revenue_stats(): void
    {
        $plan = PlanDefinition::factory()->create();
        $tenant = Tenant::factory()->create();

        Subscription::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => 'monthly',
            'amount' => 1000,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/dashboard/revenue');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'mrr',
                    'arr',
                    'monthly_subscriptions',
                    'yearly_subscriptions',
                ],
            ]);
    }

    public function test_can_get_growth_metrics(): void
    {
        Tenant::factory()->count(5)->create(['created_at' => now()]);
        Tenant::factory()->count(3)->create(['created_at' => now()->subMonth()]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/dashboard/growth');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'new_tenants_this_month',
                    'new_tenants_last_month',
                    'tenant_growth_percent',
                    'new_users_this_month',
                    'new_users_last_month',
                    'user_growth_percent',
                    'churn_rate_percent',
                ],
            ]);
    }

    public function test_can_get_activity_metrics(): void
    {
        $tenant = Tenant::factory()->create();

        User::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'last_active_at' => now(),
            'last_login_at' => now(),
        ]);

        User::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
            'last_active_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/dashboard/activity');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'active_users_24h',
                    'active_users_7d',
                    'active_users_30d',
                    'logins_today',
                ],
            ]);

        $this->assertEquals(3, $response->json('data.active_users_24h'));
        $this->assertEquals(5, $response->json('data.active_users_7d'));
    }

    public function test_can_get_combined_dashboard(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'stats',
                    'revenue',
                    'growth',
                    'activity',
                ],
            ]);
    }

    public function test_can_get_tenant_overview(): void
    {
        Tenant::factory()->count(5)->create(['status' => TenantStatus::ACTIVE]);
        Tenant::factory()->count(2)->create(['status' => TenantStatus::SUSPENDED]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/dashboard/tenants');

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

    public function test_can_get_user_overview(): void
    {
        $tenant = Tenant::factory()->create();

        User::factory()->count(5)->create([
            'tenant_id' => $tenant->id,
            'status' => UserStatus::ACTIVE,
        ]);

        User::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
            'status' => UserStatus::SUSPENDED,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/dashboard/users');

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

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertUnauthorized();
    }
}
