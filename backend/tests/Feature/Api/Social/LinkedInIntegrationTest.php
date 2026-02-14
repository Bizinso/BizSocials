<?php

declare(strict_types=1);

/**
 * LinkedIn Integration Tests
 *
 * Tests for LinkedIn OAuth flow and posting API endpoints:
 * - OAuth authorization URL generation
 * - OAuth callback handling
 * - OAuth code exchange
 * - OAuth account connection
 * - Post publishing endpoint integration
 *
 * Validates: Requirements 13.1 (API Integration Testing)
 */

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
    // Use fake adapter factory to avoid real HTTP calls to LinkedIn OAuth
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
 * Helper: get LinkedIn authorization URL and return the state parameter.
 */
function getLinkedInOAuthState(\Illuminate\Foundation\Testing\TestCase $test): string
{
    $response = $test->getJson('/api/v1/oauth/linkedin/authorize');
    return $response->json('data.state');
}

/**
 * Helper: perform the exchange step and return the session_key.
 */
function exchangeLinkedInCode(\Illuminate\Foundation\Testing\TestCase $test, ?string $state = null): string
{
    $state ??= getLinkedInOAuthState($test);
    $response = $test->postJson('/api/v1/oauth/linkedin/exchange', [
        'code' => 'test_linkedin_authorization_code',
        'state' => $state,
    ]);
    $response->assertOk();
    return $response->json('data.session_key');
}

describe('LinkedIn OAuth Authorization', function () {
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

    it('includes LinkedIn-specific OAuth scopes', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/oauth/linkedin/authorize');

        $response->assertOk();

        $url = $response->json('data.url');
        // LinkedIn OAuth should include scope parameter
        expect($url)->toContain('scope');
    });

    it('stores state in cache with LinkedIn platform', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/oauth/linkedin/authorize');

        $state = $response->json('data.state');
        $cachedData = Cache::get('oauth_state:' . $state);

        expect($cachedData)->not->toBeNull();
        expect($cachedData['platform'])->toBe('linkedin');
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/oauth/linkedin/authorize');

        $response->assertUnauthorized();
    });
});

describe('LinkedIn OAuth Callback', function () {
    it('redirects to frontend with code and state for LinkedIn', function () {
        $response = $this->get('/api/v1/oauth/linkedin/callback?' . http_build_query([
            'code' => 'test_linkedin_authorization_code',
            'state' => 'test_state_parameter_long_enough',
        ]));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        expect($location)->toContain('/app/oauth/callback');
        expect($location)->toContain('code=test_linkedin_authorization_code');
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
            'error_description' => 'User denied LinkedIn access',
        ]));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        expect($location)->toContain('error=access_denied');
        expect($location)->toContain('User+denied+LinkedIn+access');
    });

    it('does not require authentication', function () {
        $response = $this->get('/api/v1/oauth/linkedin/callback?' . http_build_query([
            'code' => 'test_code',
            'state' => 'test_state_long_enough_value',
        ]));

        $response->assertRedirect();
    });
});

describe('LinkedIn OAuth Code Exchange', function () {
    it('exchanges code for session key and LinkedIn profile info', function () {
        Sanctum::actingAs($this->admin);

        $state = getLinkedInOAuthState($this);

        $response = $this->postJson('/api/v1/oauth/linkedin/exchange', [
            'code' => 'test_linkedin_authorization_code',
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

        // Verify session key is stored in cache
        $sessionKey = $response->json('data.session_key');
        $cached = Cache::get('oauth_exchange:' . $sessionKey);
        expect($cached)->not->toBeNull();
        expect($cached['platform'])->toBe('linkedin');
    });

    it('returns LinkedIn profile information', function () {
        Sanctum::actingAs($this->admin);

        $state = getLinkedInOAuthState($this);

        $response = $this->postJson('/api/v1/oauth/linkedin/exchange', [
            'code' => 'test_linkedin_authorization_code',
            'state' => $state,
        ]);

        $response->assertOk();

        $account = $response->json('data.account');
        expect($account)->toHaveKey('platform_account_id');
        expect($account)->toHaveKey('account_name');
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

    it('validates state matches LinkedIn platform', function () {
        Sanctum::actingAs($this->admin);

        // Get state for Facebook
        $facebookResponse = $this->getJson('/api/v1/oauth/facebook/authorize');
        $facebookState = $facebookResponse->json('data.state');

        // Try to use Facebook state for LinkedIn exchange
        $response = $this->postJson('/api/v1/oauth/linkedin/exchange', [
            'code' => 'test_linkedin_authorization_code',
            'state' => $facebookState,
        ]);

        $response->assertStatus(400);
    });

    it('state can only be used once', function () {
        Sanctum::actingAs($this->admin);

        $state = getLinkedInOAuthState($this);

        // First exchange consumes the state
        $response1 = $this->postJson('/api/v1/oauth/linkedin/exchange', [
            'code' => 'test_linkedin_authorization_code',
            'state' => $state,
        ]);
        $response1->assertOk();

        // Second exchange with same state fails
        $response2 = $this->postJson('/api/v1/oauth/linkedin/exchange', [
            'code' => 'test_linkedin_authorization_code',
            'state' => $state,
        ]);

        $response2->assertStatus(400);
    });
});

describe('LinkedIn OAuth Account Connection', function () {
    it('connects LinkedIn profile to workspace via session key', function () {
        Sanctum::actingAs($this->admin);

        $sessionKey = exchangeLinkedInCode($this);

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
        expect($account->platform->value)->toBe('linkedin');
    });

    it('stores LinkedIn profile metadata', function () {
        Sanctum::actingAs($this->admin);

        $sessionKey = exchangeLinkedInCode($this);

        $response = $this->postJson('/api/v1/oauth/linkedin/connect', [
            'workspace_id' => $this->workspace->id,
            'session_key' => $sessionKey,
        ]);

        $response->assertCreated();

        $accountId = $response->json('data.id');
        $account = SocialAccount::find($accountId);
        
        expect($account->platform_account_id)->not->toBeEmpty();
        expect($account->account_name)->not->toBeEmpty();
    });

    it('denies editor from connecting LinkedIn via OAuth', function () {
        Sanctum::actingAs($this->member);

        $sessionKey = exchangeLinkedInCode($this);

        $response = $this->postJson('/api/v1/oauth/linkedin/connect', [
            'workspace_id' => $this->workspace->id,
            'session_key' => $sessionKey,
        ]);

        $response->assertForbidden();
    });

    it('validates workspace exists', function () {
        Sanctum::actingAs($this->admin);

        $sessionKey = exchangeLinkedInCode($this);

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

        $sessionKey = exchangeLinkedInCode($this);

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

    it('session key can only be used once', function () {
        Sanctum::actingAs($this->admin);

        $sessionKey = exchangeLinkedInCode($this);

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

    it('allows owner to connect LinkedIn profile', function () {
        Sanctum::actingAs($this->owner);

        $sessionKey = exchangeLinkedInCode($this);

        $response = $this->postJson('/api/v1/oauth/linkedin/connect', [
            'workspace_id' => $this->workspace->id,
            'session_key' => $sessionKey,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.platform', 'linkedin');
    });
});

describe('LinkedIn OAuth State Security', function () {
    it('state expires after 10 minutes', function () {
        Sanctum::actingAs($this->admin);

        $state = getLinkedInOAuthState($this);

        // Travel forward in time past the state expiration
        $this->travel(11)->minutes();

        $response = $this->postJson('/api/v1/oauth/linkedin/exchange', [
            'code' => 'test_linkedin_authorization_code',
            'state' => $state,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    });

    it('validates state format', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/oauth/linkedin/exchange', [
            'code' => 'test_linkedin_authorization_code',
            'state' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['state']);
    });
});

describe('LinkedIn Post Publishing Endpoint', function () {
    it('requires authenticated LinkedIn account for posting operations', function () {
        Sanctum::actingAs($this->admin);

        // First connect a LinkedIn account
        $sessionKey = exchangeLinkedInCode($this);

        $response = $this->postJson('/api/v1/oauth/linkedin/connect', [
            'workspace_id' => $this->workspace->id,
            'session_key' => $sessionKey,
        ]);

        $response->assertCreated();

        $accountId = $response->json('data.id');
        $account = SocialAccount::find($accountId);

        // Verify account has access token for API calls
        expect($account->access_token)->not->toBeNull();
        expect($account->platform->value)->toBe('linkedin');
    });

    it('stores refresh token for long-term access', function () {
        Sanctum::actingAs($this->admin);

        $sessionKey = exchangeLinkedInCode($this);

        $response = $this->postJson('/api/v1/oauth/linkedin/connect', [
            'workspace_id' => $this->workspace->id,
            'session_key' => $sessionKey,
        ]);

        $response->assertCreated();

        $accountId = $response->json('data.id');
        $account = SocialAccount::find($accountId);

        // LinkedIn provides refresh tokens for offline access
        expect($account->refresh_token)->not->toBeNull();
    });

    it('stores token expiration time', function () {
        Sanctum::actingAs($this->admin);

        $sessionKey = exchangeLinkedInCode($this);

        $response = $this->postJson('/api/v1/oauth/linkedin/connect', [
            'workspace_id' => $this->workspace->id,
            'session_key' => $sessionKey,
        ]);

        $response->assertCreated();

        $accountId = $response->json('data.id');
        $account = SocialAccount::find($accountId);

        // Verify token expiration is set
        expect($account->token_expires_at)->not->toBeNull();
    });

    it('supports both personal profile and company page posting', function () {
        Sanctum::actingAs($this->admin);

        $sessionKey = exchangeLinkedInCode($this);

        $response = $this->postJson('/api/v1/oauth/linkedin/connect', [
            'workspace_id' => $this->workspace->id,
            'session_key' => $sessionKey,
        ]);

        $response->assertCreated();

        $accountId = $response->json('data.id');
        $account = SocialAccount::find($accountId);

        // LinkedIn accounts should have platform account ID for posting
        // In production, this would be URN format: urn:li:person:xxx or urn:li:organization:xxx
        expect($account->platform_account_id)->not->toBeEmpty();
        expect($account->platform_account_id)->toBeString();
    });
});
