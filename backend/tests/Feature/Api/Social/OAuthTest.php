<?php

declare(strict_types=1);

use App\Enums\Social\SocialPlatform;
use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Social\SocialPlatformAdapterFactory;
use Illuminate\Support\Facades\Cache;
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

    // Add users to workspace
    $this->workspace->addMember($this->owner, WorkspaceRole::OWNER);
    $this->workspace->addMember($this->admin, WorkspaceRole::ADMIN);
    $this->workspace->addMember($this->member, WorkspaceRole::EDITOR);
});

/**
 * Helper: get authorization URL and return the state parameter.
 */
function getOAuthState(\Illuminate\Foundation\Testing\TestCase $test, string $platform = 'linkedin'): string
{
    $response = $test->getJson("/api/v1/oauth/{$platform}/authorize");
    return $response->json('data.state');
}

/**
 * Helper: perform the exchange step and return the session_key.
 */
function exchangeCode(\Illuminate\Foundation\Testing\TestCase $test, string $platform = 'linkedin', ?string $state = null): string
{
    $state ??= getOAuthState($test, $platform);
    $response = $test->postJson("/api/v1/oauth/{$platform}/exchange", [
        'code' => 'test_authorization_code',
        'state' => $state,
    ]);
    $response->assertOk();
    return $response->json('data.session_key');
}

describe('GET /api/v1/oauth/{platform}/authorize', function () {
    it('returns authorization URL for LinkedIn', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/oauth/linkedin/authorize');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'url',
                    'state',
                    'platform',
                ],
            ])
            ->assertJsonPath('data.platform', 'linkedin');

        $url = $response->json('data.url');
        expect($url)->toContain('linkedin.com');
        expect($url)->toContain('oauth');
    });

    it('returns authorization URL for Facebook', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/oauth/facebook/authorize');

        $response->assertOk()
            ->assertJsonPath('data.platform', 'facebook');

        $url = $response->json('data.url');
        expect($url)->toContain('facebook.com');
    });

    it('returns authorization URL for Instagram', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/oauth/instagram/authorize');

        $response->assertOk()
            ->assertJsonPath('data.platform', 'instagram');
    });

    it('returns authorization URL for Twitter', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/oauth/twitter/authorize');

        $response->assertOk()
            ->assertJsonPath('data.platform', 'twitter');

        $url = $response->json('data.url');
        expect($url)->toContain('twitter.com');
    });

    it('stores state in cache', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/oauth/linkedin/authorize');

        $state = $response->json('data.state');
        $cachedData = Cache::get('oauth_state:' . $state);

        expect($cachedData)->not->toBeNull();
        expect($cachedData['platform'])->toBe('linkedin');
    });

    it('returns error for unsupported platform', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/oauth/tiktok/authorize');

        $response->assertNotFound();
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/oauth/linkedin/authorize');

        $response->assertUnauthorized();
    });
});

describe('GET /api/v1/oauth/{platform}/callback', function () {
    it('redirects to frontend with code and state', function () {
        $response = $this->get('/api/v1/oauth/linkedin/callback?' . http_build_query([
            'code' => 'test_authorization_code',
            'state' => 'test_state_parameter_long_enough',
        ]));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        expect($location)->toContain('/app/oauth/callback');
        expect($location)->toContain('code=test_authorization_code');
        expect($location)->toContain('platform=linkedin');
    });

    it('redirects with error on missing code', function () {
        $response = $this->get('/api/v1/oauth/linkedin/callback?' . http_build_query([
            'state' => 'test_state_parameter_long_enough',
        ]));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        expect($location)->toContain('error=missing_params');
    });

    it('redirects with error on OAuth provider error', function () {
        $response = $this->get('/api/v1/oauth/linkedin/callback?' . http_build_query([
            'error' => 'access_denied',
            'error_description' => 'User denied access',
        ]));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        expect($location)->toContain('error=access_denied');
    });

    it('does not require authentication', function () {
        $response = $this->get('/api/v1/oauth/linkedin/callback?' . http_build_query([
            'code' => 'test_code',
            'state' => 'test_state_long_enough_value',
        ]));

        $response->assertRedirect();
    });
});

describe('POST /api/v1/oauth/{platform}/exchange', function () {
    it('exchanges code for session key and account info', function () {
        Sanctum::actingAs($this->admin);

        $state = getOAuthState($this, 'linkedin');

        $response = $this->postJson('/api/v1/oauth/linkedin/exchange', [
            'code' => 'test_authorization_code',
            'state' => $state,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'session_key',
                    'platform',
                    'account' => [
                        'platform_account_id',
                        'account_name',
                    ],
                ],
            ])
            ->assertJsonPath('data.platform', 'linkedin');
    });

    it('requires authentication', function () {
        $response = $this->postJson('/api/v1/oauth/linkedin/exchange', [
            'code' => 'test_code',
            'state' => 'test_state_long_enough_value',
        ]);

        $response->assertUnauthorized();
    });

    it('validates required fields', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/oauth/linkedin/exchange', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code', 'state']);
    });
});

describe('POST /api/v1/oauth/{platform}/connect', function () {
    it('connects account to workspace via session key', function () {
        Sanctum::actingAs($this->admin);

        $sessionKey = exchangeCode($this, 'linkedin');

        $response = $this->postJson('/api/v1/oauth/linkedin/connect', [
            'workspace_id' => $this->workspace->id,
            'session_key' => $sessionKey,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.platform', 'linkedin')
            ->assertJsonPath('data.workspace_id', $this->workspace->id)
            ->assertJsonPath('data.status', 'connected');

        // Verify in database
        $accountId = $response->json('data.id');
        $account = SocialAccount::find($accountId);
        expect($account)->not->toBeNull();
        expect($account->workspace_id)->toBe($this->workspace->id);
    });

    it('denies editor from connecting via OAuth', function () {
        Sanctum::actingAs($this->member);

        $sessionKey = exchangeCode($this, 'linkedin');

        $response = $this->postJson('/api/v1/oauth/linkedin/connect', [
            'workspace_id' => $this->workspace->id,
            'session_key' => $sessionKey,
        ]);

        $response->assertForbidden();
    });

    it('validates workspace exists', function () {
        Sanctum::actingAs($this->admin);

        $sessionKey = exchangeCode($this, 'linkedin');

        $response = $this->postJson('/api/v1/oauth/linkedin/connect', [
            'workspace_id' => '00000000-0000-0000-0000-000000000000',
            'session_key' => $sessionKey,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['workspace_id']);
    });

    it('validates required fields', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/oauth/linkedin/connect', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['workspace_id', 'session_key']);
    });

    it('prevents connecting to workspace from different tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $otherWorkspace = Workspace::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        Sanctum::actingAs($this->admin);

        $sessionKey = exchangeCode($this, 'linkedin');

        $response = $this->postJson('/api/v1/oauth/linkedin/connect', [
            'workspace_id' => $otherWorkspace->id,
            'session_key' => $sessionKey,
        ]);

        $response->assertNotFound();
    });

    it('returns error for expired session key', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/oauth/linkedin/connect', [
            'workspace_id' => $this->workspace->id,
            'session_key' => 'expired_or_nonexistent_session_key_that_is_long_enough',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'OAuth session expired. Please try connecting again.');
    });
});

describe('OAuth State Security', function () {
    it('state expires after 10 minutes', function () {
        Sanctum::actingAs($this->admin);

        $state = getOAuthState($this, 'linkedin');

        // Travel forward in time past the state expiration
        $this->travel(11)->minutes();

        $response = $this->postJson('/api/v1/oauth/linkedin/exchange', [
            'code' => 'test_authorization_code',
            'state' => $state,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    });

    it('state can only be used once', function () {
        Sanctum::actingAs($this->admin);

        $state = getOAuthState($this, 'linkedin');

        // First exchange consumes the state
        $response1 = $this->postJson('/api/v1/oauth/linkedin/exchange', [
            'code' => 'test_authorization_code',
            'state' => $state,
        ]);
        $response1->assertOk();

        // Second exchange with same state fails
        $response2 = $this->postJson('/api/v1/oauth/linkedin/exchange', [
            'code' => 'test_authorization_code',
            'state' => $state,
        ]);

        $response2->assertStatus(400);
    });

    it('validates state matches platform', function () {
        Sanctum::actingAs($this->admin);

        // Get state for LinkedIn
        $linkedinState = getOAuthState($this, 'linkedin');

        // Try to use LinkedIn state for Facebook exchange
        $response = $this->postJson('/api/v1/oauth/facebook/exchange', [
            'code' => 'test_authorization_code',
            'state' => $linkedinState,
        ]);

        $response->assertStatus(400);
    });

    it('session key can only be used once', function () {
        Sanctum::actingAs($this->admin);

        $sessionKey = exchangeCode($this, 'linkedin');

        // First connect consumes the session
        $response1 = $this->postJson('/api/v1/oauth/linkedin/connect', [
            'workspace_id' => $this->workspace->id,
            'session_key' => $sessionKey,
        ]);
        $response1->assertCreated();

        // Second connect with same session_key fails
        $response2 = $this->postJson('/api/v1/oauth/linkedin/connect', [
            'workspace_id' => $this->workspace->id,
            'session_key' => $sessionKey,
        ]);

        $response2->assertStatus(400);
    });
});
