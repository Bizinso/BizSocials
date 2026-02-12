<?php

declare(strict_types=1);

use App\Enums\Platform\IntegrationStatus;
use App\Enums\Social\SocialAccountStatus;
use App\Models\Platform\SocialPlatformIntegration;
use App\Models\Platform\SuperAdminUser;
use App\Models\Social\SocialAccount;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->superAdmin = SuperAdminUser::factory()->active()->superAdmin()->create();
    $this->admin = SuperAdminUser::factory()->active()->admin()->create();
    $this->viewer = SuperAdminUser::factory()->active()->viewer()->create();
});

// ── GET /api/v1/admin/integrations ─────────────────────────

describe('GET /api/v1/admin/integrations', function () {
    it('lists all integrations for super admin', function () {
        SocialPlatformIntegration::factory()->active()->create();

        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->getJson('/api/v1/admin/integrations');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'provider',
                        'display_name',
                        'platforms',
                        'is_enabled',
                        'status',
                        'api_version',
                        'has_credentials',
                        'account_stats',
                        'updated_at',
                    ],
                ],
            ]);
    });

    it('lists all integrations for viewer', function () {
        SocialPlatformIntegration::factory()->active()->create();

        Sanctum::actingAs($this->viewer, ['*'], 'sanctum');

        $response = $this->getJson('/api/v1/admin/integrations');

        $response->assertOk();
    });

    it('includes account stats per platform', function () {
        SocialPlatformIntegration::factory()->active()->create();

        // Create some social accounts
        SocialAccount::factory()->facebook()->connected()->create();
        SocialAccount::factory()->facebook()->tokenExpired()->create();
        SocialAccount::factory()->instagram()->connected()->create();

        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->getJson('/api/v1/admin/integrations');

        $response->assertOk();
        $data = $response->json('data.0');
        expect($data['account_stats'])->toHaveKey('facebook');
        expect($data['account_stats'])->toHaveKey('instagram');
    });

    it('rejects unauthenticated requests', function () {
        $response = $this->getJson('/api/v1/admin/integrations');

        $response->assertUnauthorized();
    });
});

// ── GET /api/v1/admin/integrations/{provider} ──────────────

describe('GET /api/v1/admin/integrations/{provider}', function () {
    it('returns integration detail', function () {
        SocialPlatformIntegration::factory()->active()->create([
            'provider' => 'meta',
        ]);

        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->getJson('/api/v1/admin/integrations/meta');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'provider',
                    'display_name',
                    'platforms',
                    'app_id_masked',
                    'has_secret',
                    'redirect_uris',
                    'api_version',
                    'scopes',
                    'is_enabled',
                    'status',
                    'environment',
                ],
            ]);
    });

    it('returns 404 for unknown provider', function () {
        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->getJson('/api/v1/admin/integrations/unknown');

        $response->assertNotFound();
    });
});

// ── PUT /api/v1/admin/integrations/{provider} ──────────────

describe('PUT /api/v1/admin/integrations/{provider}', function () {
    it('creates integration via upsert', function () {
        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->putJson('/api/v1/admin/integrations/meta', [
            'app_id' => '123456789012345',
            'app_secret' => 'abc123secret456',
            'api_version' => 'v24.0',
            'scopes' => [
                'facebook' => ['pages_show_list', 'pages_read_engagement'],
                'instagram' => ['instagram_basic'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.provider', 'meta')
            ->assertJsonPath('data.api_version', 'v24.0');

        $this->assertDatabaseHas('social_platform_integrations', [
            'provider' => 'meta',
            'api_version' => 'v24.0',
        ]);
    });

    it('updates existing integration', function () {
        SocialPlatformIntegration::factory()->active()->create([
            'provider' => 'meta',
            'api_version' => 'v23.0',
        ]);

        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->putJson('/api/v1/admin/integrations/meta', [
            'api_version' => 'v24.0',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.api_version', 'v24.0');
    });

    it('denies app_secret update for non-super-admin', function () {
        SocialPlatformIntegration::factory()->active()->create([
            'provider' => 'meta',
        ]);

        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $response = $this->putJson('/api/v1/admin/integrations/meta', [
            'app_secret' => 'new-secret-value',
        ]);

        $response->assertForbidden();
    });

    it('allows super admin to update app_secret', function () {
        SocialPlatformIntegration::factory()->active()->create([
            'provider' => 'meta',
        ]);

        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->putJson('/api/v1/admin/integrations/meta', [
            'app_secret' => 'new-secret-value-here',
        ]);

        $response->assertOk();
    });

    it('validates api_version format', function () {
        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->putJson('/api/v1/admin/integrations/meta', [
            'api_version' => 'bad-version',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['api_version']);
    });

    it('returns scope changes metadata', function () {
        SocialPlatformIntegration::factory()->active()->create([
            'provider' => 'meta',
            'scopes' => ['facebook' => ['pages_show_list']],
        ]);

        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->putJson('/api/v1/admin/integrations/meta', [
            'scopes' => [
                'facebook' => ['pages_show_list', 'pages_manage_posts'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'meta' => [
                        'scope_changes',
                        'requires_reauth',
                        'affected_accounts',
                        'affected_tenants',
                    ],
                ],
            ]);
    });
});

// ── POST /api/v1/admin/integrations/{provider}/toggle ──────

describe('POST /api/v1/admin/integrations/{provider}/toggle', function () {
    it('disables an integration', function () {
        SocialPlatformIntegration::factory()->active()->create([
            'provider' => 'meta',
        ]);

        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->postJson('/api/v1/admin/integrations/meta/toggle', [
            'enabled' => false,
            'reason' => 'Scheduled maintenance',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_enabled', false);
    });

    it('enables a disabled integration', function () {
        SocialPlatformIntegration::factory()->disabled()->create([
            'provider' => 'meta',
        ]);

        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->postJson('/api/v1/admin/integrations/meta/toggle', [
            'enabled' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_enabled', true);
    });

    it('requires reason when disabling', function () {
        SocialPlatformIntegration::factory()->active()->create([
            'provider' => 'meta',
        ]);

        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->postJson('/api/v1/admin/integrations/meta/toggle', [
            'enabled' => false,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    });

    it('returns 404 for unknown provider', function () {
        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->postJson('/api/v1/admin/integrations/unknown/toggle', [
            'enabled' => false,
            'reason' => 'test',
        ]);

        $response->assertNotFound();
    });
});

// ── POST /api/v1/admin/integrations/{provider}/force-reauth ─

describe('POST /api/v1/admin/integrations/{provider}/force-reauth', function () {
    it('forces reauthorization for specified platforms', function () {
        SocialPlatformIntegration::factory()->active()->create([
            'provider' => 'meta',
        ]);

        // Create connected FB accounts that will be revoked
        $fbAccount = SocialAccount::factory()->facebook()->connected()->create();

        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->postJson('/api/v1/admin/integrations/meta/force-reauth', [
            'platforms' => ['facebook'],
            'reason' => 'Security audit requires token rotation',
            'notify_tenants' => true,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'accounts_revoked',
                    'tenants_affected',
                    'tenants_notified',
                    'platforms',
                ],
            ]);

        // Verify accounts were revoked
        $fbAccount->refresh();
        expect($fbAccount->status)->toBe(SocialAccountStatus::REVOKED);
    });

    it('denies force-reauth for viewer role', function () {
        SocialPlatformIntegration::factory()->active()->create([
            'provider' => 'meta',
        ]);

        Sanctum::actingAs($this->viewer, ['*'], 'sanctum');

        $response = $this->postJson('/api/v1/admin/integrations/meta/force-reauth', [
            'platforms' => ['facebook'],
            'reason' => 'Security audit requires token rotation',
        ]);

        $response->assertForbidden();
    });

    it('validates platform values', function () {
        SocialPlatformIntegration::factory()->active()->create([
            'provider' => 'meta',
        ]);

        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->postJson('/api/v1/admin/integrations/meta/force-reauth', [
            'platforms' => ['twitter'],
            'reason' => 'Security audit requires token rotation',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['platforms.0']);
    });

    it('validates reason minimum length', function () {
        SocialPlatformIntegration::factory()->active()->create([
            'provider' => 'meta',
        ]);

        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->postJson('/api/v1/admin/integrations/meta/force-reauth', [
            'platforms' => ['facebook'],
            'reason' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    });
});

// ── GET /api/v1/admin/integrations/{provider}/health ────────

describe('GET /api/v1/admin/integrations/{provider}/health', function () {
    it('returns health summary', function () {
        SocialPlatformIntegration::factory()->active()->create([
            'provider' => 'meta',
        ]);

        SocialAccount::factory()->facebook()->connected()->count(3)->create();
        SocialAccount::factory()->facebook()->tokenExpired()->create();
        SocialAccount::factory()->instagram()->connected()->count(2)->create();

        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->getJson('/api/v1/admin/integrations/meta/health');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'summary',
                    'accounts',
                ],
            ]);
    });

    it('returns 404 for unknown provider', function () {
        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->getJson('/api/v1/admin/integrations/unknown/health');

        $response->assertNotFound();
    });
});

// ── GET /api/v1/admin/integrations/{provider}/audit-log ─────

describe('GET /api/v1/admin/integrations/{provider}/audit-log', function () {
    it('returns audit log for integration', function () {
        SocialPlatformIntegration::factory()->active()->create([
            'provider' => 'meta',
        ]);

        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->getJson('/api/v1/admin/integrations/meta/audit-log');

        $response->assertOk();
    });

    it('returns 404 for unknown provider', function () {
        Sanctum::actingAs($this->superAdmin, ['*'], 'sanctum');

        $response = $this->getJson('/api/v1/admin/integrations/unknown/audit-log');

        $response->assertNotFound();
    });
});
