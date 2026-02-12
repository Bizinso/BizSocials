<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Billing\SubscriptionStatus;
use App\Enums\Platform\PlanCode;
use App\Enums\Platform\SuperAdminRole;
use App\Enums\Platform\SuperAdminStatus;
use App\Models\Billing\Subscription;
use App\Models\Platform\PlanDefinition;
use App\Models\Platform\PlanLimit;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminPlanControllerTest extends TestCase
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

    public function test_can_list_plans(): void
    {
        PlanDefinition::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/plans');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'is_active',
                        'is_public',
                        'price_inr_monthly',
                        'price_usd_monthly',
                        'limits',
                        'features',
                    ],
                ],
            ]);
    }

    public function test_can_get_single_plan(): void
    {
        $plan = PlanDefinition::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/plans/{$plan->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $plan->id)
            ->assertJsonPath('data.name', $plan->name);
    }

    public function test_can_create_plan(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/plans', [
                'code' => 'starter',
                'name' => 'Starter Plan',
                'description' => 'For small teams',
                'is_active' => true,
                'is_public' => true,
                'price_inr_monthly' => 999,
                'price_inr_yearly' => 9999,
                'price_usd_monthly' => 12,
                'price_usd_yearly' => 120,
                'trial_days' => 14,
                'sort_order' => 1,
                'features' => ['Feature 1', 'Feature 2'],
                'limits' => [
                    'max_workspaces' => 3,
                    'max_users' => 5,
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Starter Plan')
            ->assertJsonPath('data.code', 'STARTER');

        $this->assertDatabaseHas('plan_definitions', [
            'name' => 'Starter Plan',
        ]);
    }

    public function test_cannot_create_plan_with_duplicate_code(): void
    {
        PlanDefinition::factory()->create(['code' => PlanCode::STARTER]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/plans', [
                'code' => 'starter',
                'name' => 'Another Starter',
                'price_inr_monthly' => 999,
                'price_inr_yearly' => 9999,
                'price_usd_monthly' => 12,
                'price_usd_yearly' => 120,
            ]);

        $response->assertStatus(422);
    }

    public function test_can_update_plan(): void
    {
        $plan = PlanDefinition::factory()->create();

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/plans/{$plan->id}", [
                'name' => 'Updated Plan Name',
                'price_inr_monthly' => 1499,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Plan Name');

        $this->assertDatabaseHas('plan_definitions', [
            'id' => $plan->id,
            'name' => 'Updated Plan Name',
        ]);
    }

    public function test_can_delete_plan_without_subscriptions(): void
    {
        $plan = PlanDefinition::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/plans/{$plan->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('plan_definitions', ['id' => $plan->id]);
    }

    public function test_cannot_delete_plan_with_active_subscriptions(): void
    {
        $plan = PlanDefinition::factory()->create();
        $tenant = Tenant::factory()->create();

        Subscription::factory()->create([
            'plan_id' => $plan->id,
            'tenant_id' => $tenant->id,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/plans/{$plan->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('plan_definitions', ['id' => $plan->id]);
    }

    public function test_can_update_plan_limits(): void
    {
        $plan = PlanDefinition::factory()->create();

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/plans/{$plan->id}/limits", [
                'limits' => [
                    'max_workspaces' => 10,
                    'max_users' => 25,
                    'max_social_accounts' => 15,
                ],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('plan_limits', [
            'plan_id' => $plan->id,
            'limit_key' => 'max_workspaces',
            'limit_value' => 10,
        ]);

        $this->assertDatabaseHas('plan_limits', [
            'plan_id' => $plan->id,
            'limit_key' => 'max_users',
            'limit_value' => 25,
        ]);
    }

    public function test_returns_404_for_nonexistent_plan(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/plans/nonexistent-uuid');

        $response->assertNotFound();
    }
}
