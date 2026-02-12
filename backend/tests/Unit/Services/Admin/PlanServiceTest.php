<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use App\Data\Admin\CreatePlanData;
use App\Data\Admin\UpdatePlanData;
use App\Enums\Billing\SubscriptionStatus;
use App\Enums\Platform\PlanCode;
use App\Models\Billing\Subscription;
use App\Models\Platform\PlanDefinition;
use App\Models\Platform\PlanLimit;
use App\Models\Tenant\Tenant;
use App\Services\Admin\PlanService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class PlanServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlanService();
    }

    public function test_list_returns_all_plans_ordered(): void
    {
        PlanDefinition::factory()->create(['sort_order' => 2]);
        PlanDefinition::factory()->create(['sort_order' => 1]);
        PlanDefinition::factory()->create(['sort_order' => 3]);

        $result = $this->service->list();

        $this->assertCount(3, $result);
        $this->assertEquals(1, $result->first()->sort_order);
        $this->assertEquals(3, $result->last()->sort_order);
    }

    public function test_get_returns_plan_with_limits(): void
    {
        $plan = PlanDefinition::factory()->create();
        PlanLimit::factory()->create(['plan_id' => $plan->id, 'limit_key' => 'max_users']);

        $result = $this->service->get($plan->id);

        $this->assertEquals($plan->id, $result->id);
        $this->assertTrue($result->relationLoaded('limits'));
    }

    public function test_get_throws_exception_for_nonexistent_plan(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->get('nonexistent-id');
    }

    public function test_create_makes_new_plan(): void
    {
        $data = new CreatePlanData(
            code: 'STARTER',
            name: 'Starter Plan',
            description: 'For beginners',
            price_inr_monthly: 999,
            price_inr_yearly: 9999,
            price_usd_monthly: 12,
            price_usd_yearly: 120,
            trial_days: 14,
        );

        $result = $this->service->create($data);

        $this->assertEquals('Starter Plan', $result->name);
        $this->assertDatabaseHas('plan_definitions', ['name' => 'Starter Plan']);
    }

    public function test_create_with_limits(): void
    {
        $data = new CreatePlanData(
            code: 'PROFESSIONAL',
            name: 'Professional Plan',
            price_inr_monthly: 999,
            price_inr_yearly: 9999,
            price_usd_monthly: 12,
            price_usd_yearly: 120,
            limits: ['max_users' => 5, 'max_workspaces' => 3],
        );

        $result = $this->service->create($data);

        $this->assertCount(2, $result->limits);
    }

    public function test_create_throws_for_duplicate_code(): void
    {
        PlanDefinition::factory()->create(['code' => PlanCode::STARTER]);

        $data = new CreatePlanData(
            code: 'STARTER',
            name: 'Another Starter',
            price_inr_monthly: 999,
            price_inr_yearly: 9999,
            price_usd_monthly: 12,
            price_usd_yearly: 120,
        );

        $this->expectException(ValidationException::class);

        $this->service->create($data);
    }

    public function test_update_modifies_plan(): void
    {
        $plan = PlanDefinition::factory()->create(['name' => 'Old Name']);

        $data = new UpdatePlanData(name: 'New Name');
        $result = $this->service->update($plan, $data);

        $this->assertEquals('New Name', $result->name);
    }

    public function test_delete_removes_plan(): void
    {
        $plan = PlanDefinition::factory()->create();

        $this->service->delete($plan);

        $this->assertDatabaseMissing('plan_definitions', ['id' => $plan->id]);
    }

    public function test_delete_throws_for_plan_with_active_subscriptions(): void
    {
        $plan = PlanDefinition::factory()->create();
        $tenant = Tenant::factory()->create();

        Subscription::factory()->create([
            'plan_id' => $plan->id,
            'tenant_id' => $tenant->id,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $this->expectException(ValidationException::class);

        $this->service->delete($plan);
    }

    public function test_update_limits_creates_new_limits(): void
    {
        $plan = PlanDefinition::factory()->create();

        $result = $this->service->updateLimits($plan, [
            'max_users' => 10,
            'max_workspaces' => 5,
        ]);

        $this->assertDatabaseHas('plan_limits', [
            'plan_id' => $plan->id,
            'limit_key' => 'max_users',
            'limit_value' => 10,
        ]);
    }

    public function test_update_limits_updates_existing_limits(): void
    {
        $plan = PlanDefinition::factory()->create();
        PlanLimit::factory()->create([
            'plan_id' => $plan->id,
            'limit_key' => 'max_users',
            'limit_value' => 5,
        ]);

        $result = $this->service->updateLimits($plan, [
            'max_users' => 15,
        ]);

        $this->assertDatabaseHas('plan_limits', [
            'plan_id' => $plan->id,
            'limit_key' => 'max_users',
            'limit_value' => 15,
        ]);
    }

    public function test_get_subscription_count_returns_correct_count(): void
    {
        $plan = PlanDefinition::factory()->create();
        $tenant = Tenant::factory()->create();

        Subscription::factory()->count(3)->create([
            'plan_id' => $plan->id,
            'tenant_id' => $tenant->id,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        Subscription::factory()->create([
            'plan_id' => $plan->id,
            'tenant_id' => $tenant->id,
            'status' => SubscriptionStatus::CANCELLED,
        ]);

        $count = $this->service->getSubscriptionCount($plan);

        $this->assertEquals(3, $count);
    }
}
