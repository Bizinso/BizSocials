<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Platform\SuperAdminRole;
use App\Enums\Platform\SuperAdminStatus;
use App\Models\Platform\FeatureFlag;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminFeatureFlagControllerTest extends TestCase
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

    public function test_can_list_feature_flags(): void
    {
        FeatureFlag::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/feature-flags');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'key',
                        'name',
                        'description',
                        'is_enabled',
                        'rollout_percentage',
                        'allowed_plans',
                        'allowed_tenants',
                    ],
                ],
            ]);
    }

    public function test_can_get_single_feature_flag(): void
    {
        $flag = FeatureFlag::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/feature-flags/{$flag->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $flag->id)
            ->assertJsonPath('data.key', $flag->key);
    }

    public function test_can_create_feature_flag(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/feature-flags', [
                'key' => 'new.feature',
                'name' => 'New Feature',
                'description' => 'A new experimental feature',
                'is_enabled' => false,
                'rollout_percentage' => 50,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.key', 'new.feature')
            ->assertJsonPath('data.name', 'New Feature')
            ->assertJsonPath('data.rollout_percentage', 50);

        $this->assertDatabaseHas('feature_flags', [
            'key' => 'new.feature',
            'name' => 'New Feature',
        ]);
    }

    public function test_cannot_create_feature_flag_with_duplicate_key(): void
    {
        FeatureFlag::factory()->create(['key' => 'existing.feature']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/feature-flags', [
                'key' => 'existing.feature',
                'name' => 'Another Feature',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_update_feature_flag(): void
    {
        $flag = FeatureFlag::factory()->create([
            'is_enabled' => false,
            'rollout_percentage' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/feature-flags/{$flag->id}", [
                'name' => 'Updated Feature Name',
                'is_enabled' => true,
                'rollout_percentage' => 75,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Feature Name')
            ->assertJsonPath('data.is_enabled', true)
            ->assertJsonPath('data.rollout_percentage', 75);
    }

    public function test_can_toggle_feature_flag(): void
    {
        $flag = FeatureFlag::factory()->create(['is_enabled' => false]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/feature-flags/{$flag->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('data.is_enabled', true);

        // Toggle again
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/feature-flags/{$flag->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('data.is_enabled', false);
    }

    public function test_can_delete_feature_flag(): void
    {
        $flag = FeatureFlag::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/feature-flags/{$flag->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('feature_flags', ['id' => $flag->id]);
    }

    public function test_can_add_tenant_to_allowed_list(): void
    {
        $flag = FeatureFlag::factory()->create(['allowed_tenants' => null]);
        $tenant = Tenant::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/feature-flags/{$flag->id}/tenants/{$tenant->id}");

        $response->assertOk();

        $flag->refresh();
        $this->assertContains($tenant->id, $flag->allowed_tenants);
    }

    public function test_can_remove_tenant_from_allowed_list(): void
    {
        $tenant = Tenant::factory()->create();
        $flag = FeatureFlag::factory()->create(['allowed_tenants' => [$tenant->id]]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/feature-flags/{$flag->id}/tenants/{$tenant->id}");

        $response->assertOk();

        $flag->refresh();
        $this->assertNull($flag->allowed_tenants);
    }

    public function test_can_check_if_feature_is_enabled(): void
    {
        $flag = FeatureFlag::factory()->create([
            'key' => 'test.feature',
            'is_enabled' => true,
            'rollout_percentage' => 100,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/feature-flags/check/test.feature');

        $response->assertOk()
            ->assertJsonPath('data.key', 'test.feature')
            ->assertJsonPath('data.is_enabled', true);
    }

    public function test_check_returns_false_for_nonexistent_feature(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/feature-flags/check/nonexistent.feature');

        $response->assertOk()
            ->assertJsonPath('data.is_enabled', false);
    }

    public function test_returns_404_for_nonexistent_feature_flag(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/feature-flags/nonexistent-uuid');

        $response->assertNotFound();
    }
}
