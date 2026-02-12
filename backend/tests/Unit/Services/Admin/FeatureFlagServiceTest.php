<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use App\Data\Admin\CreateFeatureFlagData;
use App\Data\Admin\UpdateFeatureFlagData;
use App\Enums\Platform\PlanCode;
use App\Models\Platform\FeatureFlag;
use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use App\Services\Admin\FeatureFlagService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class FeatureFlagServiceTest extends TestCase
{
    use RefreshDatabase;

    private FeatureFlagService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FeatureFlagService();
    }

    public function test_list_returns_all_flags_ordered_by_key(): void
    {
        FeatureFlag::factory()->create(['key' => 'feature.beta']);
        FeatureFlag::factory()->create(['key' => 'feature.alpha']);
        FeatureFlag::factory()->create(['key' => 'feature.gamma']);

        $result = $this->service->list();

        $this->assertCount(3, $result);
        $this->assertEquals('feature.alpha', $result->first()->key);
    }

    public function test_get_returns_flag(): void
    {
        $flag = FeatureFlag::factory()->create();

        $result = $this->service->get($flag->id);

        $this->assertEquals($flag->id, $result->id);
    }

    public function test_get_throws_for_nonexistent_flag(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->get('nonexistent-id');
    }

    public function test_get_by_key_returns_flag(): void
    {
        $flag = FeatureFlag::factory()->create(['key' => 'test.feature']);

        $result = $this->service->getByKey('test.feature');

        $this->assertEquals($flag->id, $result->id);
    }

    public function test_create_makes_new_flag(): void
    {
        $data = new CreateFeatureFlagData(
            key: 'new.feature',
            name: 'New Feature',
            description: 'A new feature',
            is_enabled: true,
            rollout_percentage: 50,
        );

        $result = $this->service->create($data);

        $this->assertEquals('new.feature', $result->key);
        $this->assertTrue($result->is_enabled);
        $this->assertEquals(50, $result->rollout_percentage);
    }

    public function test_create_throws_for_duplicate_key(): void
    {
        FeatureFlag::factory()->create(['key' => 'existing.feature']);

        $data = new CreateFeatureFlagData(
            key: 'existing.feature',
            name: 'Another Feature',
        );

        $this->expectException(ValidationException::class);

        $this->service->create($data);
    }

    public function test_update_modifies_flag(): void
    {
        $flag = FeatureFlag::factory()->create([
            'name' => 'Old Name',
            'is_enabled' => false,
        ]);

        $data = new UpdateFeatureFlagData(
            name: 'New Name',
            is_enabled: true,
        );

        $result = $this->service->update($flag, $data);

        $this->assertEquals('New Name', $result->name);
        $this->assertTrue($result->is_enabled);
    }

    public function test_toggle_switches_enabled_state(): void
    {
        $flag = FeatureFlag::factory()->create(['is_enabled' => false]);

        $result = $this->service->toggle($flag);
        $this->assertTrue($result->is_enabled);

        $result = $this->service->toggle($flag);
        $this->assertFalse($result->is_enabled);
    }

    public function test_delete_removes_flag(): void
    {
        $flag = FeatureFlag::factory()->create();

        $this->service->delete($flag);

        $this->assertDatabaseMissing('feature_flags', ['id' => $flag->id]);
    }

    public function test_is_enabled_returns_true_for_enabled_flag(): void
    {
        FeatureFlag::factory()->create([
            'key' => 'enabled.feature',
            'is_enabled' => true,
            'rollout_percentage' => 100,
        ]);

        $result = $this->service->isEnabled('enabled.feature');

        $this->assertTrue($result);
    }

    public function test_is_enabled_returns_false_for_disabled_flag(): void
    {
        FeatureFlag::factory()->create([
            'key' => 'disabled.feature',
            'is_enabled' => false,
        ]);

        $result = $this->service->isEnabled('disabled.feature');

        $this->assertFalse($result);
    }

    public function test_is_enabled_returns_false_for_nonexistent_flag(): void
    {
        $result = $this->service->isEnabled('nonexistent.feature');

        $this->assertFalse($result);
    }

    public function test_is_enabled_with_tenant_checks_allowed_tenants(): void
    {
        $plan = PlanDefinition::factory()->create(['code' => PlanCode::STARTER]);
        $tenant = Tenant::factory()->create(['plan_id' => $plan->id]);

        FeatureFlag::factory()->create([
            'key' => 'beta.feature',
            'is_enabled' => true,
            'rollout_percentage' => 0,
            'allowed_tenants' => [$tenant->id],
        ]);

        $result = $this->service->isEnabled('beta.feature', $tenant);

        $this->assertTrue($result);
    }

    public function test_add_allowed_tenant_adds_to_list(): void
    {
        $flag = FeatureFlag::factory()->create(['allowed_tenants' => null]);
        $tenant = Tenant::factory()->create();

        $result = $this->service->addAllowedTenant($flag, $tenant->id);

        $this->assertContains($tenant->id, $result->allowed_tenants);
    }

    public function test_add_allowed_tenant_does_not_duplicate(): void
    {
        $tenant = Tenant::factory()->create();
        $flag = FeatureFlag::factory()->create(['allowed_tenants' => [$tenant->id]]);

        $result = $this->service->addAllowedTenant($flag, $tenant->id);

        $this->assertCount(1, $result->allowed_tenants);
    }

    public function test_remove_allowed_tenant_removes_from_list(): void
    {
        $tenant = Tenant::factory()->create();
        $flag = FeatureFlag::factory()->create(['allowed_tenants' => [$tenant->id]]);

        $result = $this->service->removeAllowedTenant($flag, $tenant->id);

        $this->assertNull($result->allowed_tenants);
    }

    public function test_add_allowed_plan_adds_to_list(): void
    {
        $flag = FeatureFlag::factory()->create(['allowed_plans' => null]);

        $result = $this->service->addAllowedPlan($flag, 'professional');

        $this->assertContains('professional', $result->allowed_plans);
    }

    public function test_remove_allowed_plan_removes_from_list(): void
    {
        $flag = FeatureFlag::factory()->create(['allowed_plans' => ['starter', 'professional']]);

        $result = $this->service->removeAllowedPlan($flag, 'starter');

        $this->assertNotContains('starter', $result->allowed_plans);
        $this->assertContains('professional', $result->allowed_plans);
    }
}
