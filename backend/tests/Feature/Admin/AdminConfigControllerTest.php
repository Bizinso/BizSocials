<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Platform\ConfigCategory;
use App\Enums\Platform\SuperAdminRole;
use App\Enums\Platform\SuperAdminStatus;
use App\Models\Platform\PlatformConfig;
use App\Models\Platform\SuperAdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminConfigControllerTest extends TestCase
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

    public function test_can_list_configs(): void
    {
        PlatformConfig::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/config');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'key',
                        'value',
                        'category',
                        'description',
                        'is_sensitive',
                    ],
                ],
            ]);
    }

    public function test_can_filter_configs_by_category(): void
    {
        PlatformConfig::factory()->count(2)->create(['category' => ConfigCategory::GENERAL]);
        PlatformConfig::factory()->count(1)->create(['category' => ConfigCategory::SECURITY]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/config?category=general');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_get_single_config(): void
    {
        $config = PlatformConfig::factory()->create(['key' => 'test.config']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/config/test.config');

        $response->assertOk()
            ->assertJsonPath('data.key', 'test.config');
    }

    public function test_can_set_config_value(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/config/app.name', [
                'value' => 'BizSocials Platform',
                'category' => 'general',
                'description' => 'The application name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.key', 'app.name');

        $this->assertDatabaseHas('platform_configs', [
            'key' => 'app.name',
        ]);
    }

    public function test_can_update_existing_config(): void
    {
        PlatformConfig::factory()->create([
            'key' => 'existing.config',
            'value' => ['value' => 'old value'],
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/config/existing.config', [
                'value' => 'new value',
            ]);

        $response->assertOk();

        $config = PlatformConfig::where('key', 'existing.config')->first();
        $this->assertEquals('new value', $config->value['value']);
    }

    public function test_can_delete_config(): void
    {
        PlatformConfig::factory()->create(['key' => 'deletable.config']);

        $response = $this->actingAs($this->admin)
            ->deleteJson('/api/v1/admin/config/deletable.config');

        $response->assertOk();
        $this->assertDatabaseMissing('platform_configs', ['key' => 'deletable.config']);
    }

    public function test_can_get_configs_grouped_by_category(): void
    {
        PlatformConfig::factory()->create(['category' => ConfigCategory::GENERAL]);
        PlatformConfig::factory()->create(['category' => ConfigCategory::SECURITY]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/config/grouped');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'general',
                    'security',
                ],
            ]);
    }

    public function test_can_bulk_set_configs(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/config/bulk', [
                'configs' => [
                    'bulk.config.1' => 'value 1',
                    'bulk.config.2' => 'value 2',
                    'bulk.config.3' => 123,
                ],
                'category' => 'general',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('platform_configs', ['key' => 'bulk.config.1']);
        $this->assertDatabaseHas('platform_configs', ['key' => 'bulk.config.2']);
        $this->assertDatabaseHas('platform_configs', ['key' => 'bulk.config.3']);
    }

    public function test_can_get_available_categories(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/config/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'value',
                        'label',
                        'description',
                    ],
                ],
            ]);

        $this->assertCount(count(ConfigCategory::cases()), $response->json('data'));
    }

    public function test_sensitive_config_value_is_masked(): void
    {
        PlatformConfig::factory()->create([
            'key' => 'api.secret',
            'value' => ['value' => 'super-secret-value'],
            'is_sensitive' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/config/api.secret');

        $response->assertOk()
            ->assertJsonPath('data.value', '********');
    }

    public function test_returns_404_for_nonexistent_config(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/config/nonexistent.config');

        $response->assertNotFound();
    }
}
