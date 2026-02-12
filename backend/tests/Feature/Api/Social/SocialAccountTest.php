<?php

declare(strict_types=1);

use App\Enums\Social\SocialAccountStatus;
use App\Enums\Social\SocialPlatform;
use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Social\SocialPlatformAdapterFactory;
use Laravel\Sanctum\Sanctum;
use Tests\Stubs\Services\FakeSocialPlatformAdapterFactory;

beforeEach(function () {
    // Use fake adapter factory to avoid real HTTP calls to OAuth providers
    app()->instance(SocialPlatformAdapterFactory::class, new FakeSocialPlatformAdapterFactory());

    $this->tenant = Tenant::factory()->active()->create();
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->admin = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::ADMIN,
    ]);
    $this->member = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::MEMBER,
    ]);
    $this->viewer = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::MEMBER,
    ]);

    // Add users to workspace with different roles
    $this->workspace->addMember($this->owner, WorkspaceRole::OWNER);
    $this->workspace->addMember($this->admin, WorkspaceRole::ADMIN);
    $this->workspace->addMember($this->member, WorkspaceRole::EDITOR);
    $this->workspace->addMember($this->viewer, WorkspaceRole::VIEWER);
});

describe('GET /api/v1/workspaces/{workspace}/social-accounts', function () {
    it('returns list of social accounts for workspace members', function () {
        SocialAccount::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);

        Sanctum::actingAs($this->member);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'workspace_id',
                        'platform',
                        'platform_account_id',
                        'account_name',
                        'account_username',
                        'profile_image_url',
                        'status',
                        'is_healthy',
                        'can_publish',
                        'requires_reconnect',
                        'token_expires_at',
                        'connected_at',
                        'last_refreshed_at',
                    ],
                ],
                'meta',
                'links',
            ]);
    });

    it('filters by platform', function () {
        SocialAccount::factory()->linkedin()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);
        SocialAccount::factory()->twitter()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);

        Sanctum::actingAs($this->member);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts?platform=linkedin");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.platform', 'linkedin');
    });

    it('filters by status', function () {
        SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);
        SocialAccount::factory()->tokenExpired()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);

        Sanctum::actingAs($this->member);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts?status=connected");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'connected');
    });

    it('denies access for non-workspace members', function () {
        $outsider = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        Sanctum::actingAs($outsider);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts");

        $response->assertForbidden();
    });

    it('denies access for users from different tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts");

        $response->assertNotFound();
    });

    it('allows tenant admin to view any workspace', function () {
        SocialAccount::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);

        // Create a tenant admin who is not a workspace member
        $tenantAdmin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::ADMIN,
        ]);

        Sanctum::actingAs($tenantAdmin);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });
});

describe('GET /api/v1/workspaces/{workspace}/social-accounts/{id}', function () {
    it('returns social account details for workspace members', function () {
        $account = SocialAccount::factory()->linkedin()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);

        Sanctum::actingAs($this->viewer);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts/{$account->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $account->id)
            ->assertJsonPath('data.platform', 'linkedin');
    });

    it('returns 404 for social account not in workspace', function () {
        $otherWorkspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $account = SocialAccount::factory()->create([
            'workspace_id' => $otherWorkspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);

        Sanctum::actingAs($this->member);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts/{$account->id}");

        $response->assertNotFound();
    });
});

describe('POST /api/v1/workspaces/{workspace}/social-accounts', function () {
    it('allows workspace admin to connect social account', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts", [
            'platform' => 'linkedin',
            'platform_account_id' => '123456789',
            'account_name' => 'Test Company Page',
            'account_username' => 'testcompany',
            'profile_image_url' => 'https://example.com/avatar.jpg',
            'access_token' => 'test_access_token_abc123',
            'refresh_token' => 'test_refresh_token_xyz789',
            'token_expires_at' => now()->addDays(60)->toIso8601String(),
            'metadata' => ['organization_id' => 'urn:li:organization:12345'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.platform', 'linkedin')
            ->assertJsonPath('data.account_name', 'Test Company Page')
            ->assertJsonPath('data.status', 'connected')
            ->assertJsonPath('data.is_healthy', true)
            ->assertJsonPath('data.can_publish', true);

        // Verify in database
        $accountId = $response->json('data.id');
        $account = SocialAccount::find($accountId);
        expect($account)->not->toBeNull();
        expect($account->workspace_id)->toBe($this->workspace->id);
        expect($account->connected_by_user_id)->toBe($this->admin->id);
    });

    it('denies editor from connecting social account', function () {
        Sanctum::actingAs($this->member);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts", [
            'platform' => 'linkedin',
            'platform_account_id' => '123456789',
            'account_name' => 'Test Company Page',
            'access_token' => 'test_access_token',
        ]);

        $response->assertForbidden();
    });

    it('denies viewer from connecting social account', function () {
        Sanctum::actingAs($this->viewer);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts", [
            'platform' => 'linkedin',
            'platform_account_id' => '123456789',
            'account_name' => 'Test Company Page',
            'access_token' => 'test_access_token',
        ]);

        $response->assertForbidden();
    });

    it('validates required fields', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['platform', 'platform_account_id', 'account_name', 'access_token']);
    });

    it('validates platform is supported', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts", [
            'platform' => 'tiktok',
            'platform_account_id' => '123456789',
            'account_name' => 'Test Account',
            'access_token' => 'test_access_token',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['platform']);
    });

    it('reconnects existing disconnected account', function () {
        $existingAccount = SocialAccount::factory()->disconnected()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::LINKEDIN,
            'platform_account_id' => '123456789',
            'connected_by_user_id' => $this->owner->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts", [
            'platform' => 'linkedin',
            'platform_account_id' => '123456789',
            'account_name' => 'Reconnected Company Page',
            'access_token' => 'new_access_token',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.id', $existingAccount->id)
            ->assertJsonPath('data.status', 'connected')
            ->assertJsonPath('data.account_name', 'Reconnected Company Page');
    });
});

describe('DELETE /api/v1/workspaces/{workspace}/social-accounts/{id}', function () {
    it('allows workspace admin to disconnect social account', function () {
        $account = SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts/{$account->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Social account disconnected successfully');

        $account->refresh();
        expect($account->status)->toBe(SocialAccountStatus::DISCONNECTED);
        expect($account->disconnected_at)->not->toBeNull();
    });

    it('denies editor from disconnecting social account', function () {
        $account = SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);

        Sanctum::actingAs($this->member);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts/{$account->id}");

        $response->assertForbidden();
    });

    it('returns 404 for social account not in workspace', function () {
        $otherWorkspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $account = SocialAccount::factory()->create([
            'workspace_id' => $otherWorkspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts/{$account->id}");

        $response->assertNotFound();
    });
});

describe('POST /api/v1/workspaces/{workspace}/social-accounts/{id}/refresh', function () {
    it('allows workspace admin to refresh tokens', function () {
        $account = SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
            'last_refreshed_at' => now()->subDays(7),
            'refresh_token_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString('test_refresh_token'),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts/{$account->id}/refresh");

        $response->assertOk()
            ->assertJsonPath('data.status', 'connected');

        $account->refresh();
        expect($account->last_refreshed_at->isToday())->toBeTrue();
    });

    it('denies editor from refreshing tokens', function () {
        $account = SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);

        Sanctum::actingAs($this->member);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts/{$account->id}/refresh");

        $response->assertForbidden();
    });

    it('fails for account without refresh token', function () {
        $account = SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
            'refresh_token_encrypted' => null,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts/{$account->id}/refresh");

        $response->assertUnprocessable();
    });
});

describe('GET /api/v1/workspaces/{workspace}/social-accounts/health', function () {
    it('returns health status for workspace', function () {
        // Create accounts with different statuses
        SocialAccount::factory()->connected()->linkedin()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);
        SocialAccount::factory()->connected()->facebook()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);
        SocialAccount::factory()->tokenExpired()->twitter()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);
        SocialAccount::factory()->disconnected()->instagram()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);

        Sanctum::actingAs($this->member);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts/health");

        $response->assertOk()
            ->assertJsonPath('data.total_accounts', 4)
            ->assertJsonPath('data.connected_count', 2)
            ->assertJsonPath('data.expired_count', 1)
            ->assertJsonPath('data.disconnected_count', 1)
            ->assertJsonPath('data.revoked_count', 0)
            ->assertJsonStructure([
                'data' => [
                    'total_accounts',
                    'connected_count',
                    'expired_count',
                    'revoked_count',
                    'disconnected_count',
                    'by_platform' => [
                        'linkedin',
                        'facebook',
                        'instagram',
                        'twitter',
                    ],
                ],
            ]);

        // Check platform breakdown
        expect($response->json('data.by_platform.linkedin.total'))->toBe(1);
        expect($response->json('data.by_platform.linkedin.connected'))->toBe(1);
        expect($response->json('data.by_platform.twitter.total'))->toBe(1);
        expect($response->json('data.by_platform.twitter.connected'))->toBe(0);
    });

    it('returns empty health status for workspace with no accounts', function () {
        Sanctum::actingAs($this->member);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/social-accounts/health");

        $response->assertOk()
            ->assertJsonPath('data.total_accounts', 0)
            ->assertJsonPath('data.connected_count', 0);
    });
});
