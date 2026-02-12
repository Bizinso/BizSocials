<?php

declare(strict_types=1);

use App\Data\Social\OAuthTokenData;
use App\Enums\Social\SocialPlatform;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Social\Contracts\SocialPlatformAdapter;
use App\Services\Social\OAuthService;
use App\Services\Social\SocialPlatformAdapterFactory;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Mock the SocialPlatformAdapterFactory so no real HTTP calls are made
    $mockAdapter = Mockery::mock(SocialPlatformAdapter::class);
    $mockAdapter->shouldReceive('exchangeCode')->andReturnUsing(function (string $code, string $redirectUri) {
        return new OAuthTokenData(
            access_token: 'test_access_token_' . $code,
            refresh_token: null,
            expires_in: 3600,
            platform_account_id: 'acct_123456',
            account_name: 'Test Account',
            account_username: 'testaccount',
            profile_image_url: null,
            metadata: null,
        );
    });
    $mockAdapter->shouldReceive('refreshToken')->andReturnUsing(function (string $refreshToken) {
        return new OAuthTokenData(
            access_token: 'refreshed_' . $refreshToken,
            refresh_token: 'new_refresh_token',
            expires_in: 3600,
            platform_account_id: 'acct_123456',
            account_name: 'Test Account',
            account_username: 'testaccount',
            profile_image_url: null,
            metadata: null,
        );
    });
    $mockAdapter->shouldReceive('revokeToken')->andReturn(null);

    // Mock for Facebook (no refresh token)
    $mockFacebookAdapter = Mockery::mock(SocialPlatformAdapter::class);
    $mockFacebookAdapter->shouldReceive('exchangeCode')->andReturn(new OAuthTokenData(
        access_token: 'fb_access_token',
        refresh_token: null,
        expires_in: 5184000,
        platform_account_id: 'fb_123456',
        account_name: 'FB Page',
        account_username: 'fbpage',
        profile_image_url: null,
        metadata: null,
    ));
    $mockFacebookAdapter->shouldReceive('refreshToken')->andReturnUsing(function (string $refreshToken) {
        return new OAuthTokenData(
            access_token: 'refreshed_' . $refreshToken,
            refresh_token: null,
            expires_in: 5184000,
            platform_account_id: 'fb_123456',
            account_name: 'FB Page',
            account_username: 'fbpage',
            profile_image_url: null,
            metadata: null,
        );
    });
    $mockFacebookAdapter->shouldReceive('revokeToken')->andReturn(null);

    // Mock for Twitter (has refresh token)
    $mockTwitterAdapter = Mockery::mock(SocialPlatformAdapter::class);
    $mockTwitterAdapter->shouldReceive('exchangeCode')->andReturn(new OAuthTokenData(
        access_token: 'tw_access_token',
        refresh_token: 'tw_refresh_token',
        expires_in: 7200,
        platform_account_id: 'tw_123456',
        account_name: 'TW Account',
        account_username: 'twaccount',
        profile_image_url: null,
        metadata: null,
    ));
    $mockTwitterAdapter->shouldReceive('refreshToken')->andReturnUsing(function (string $refreshToken) {
        return new OAuthTokenData(
            access_token: 'refreshed_' . $refreshToken,
            refresh_token: 'new_tw_refresh',
            expires_in: 7200,
            platform_account_id: 'tw_123456',
            account_name: 'TW Account',
            account_username: 'twaccount',
            profile_image_url: null,
            metadata: null,
        );
    });
    $mockTwitterAdapter->shouldReceive('revokeToken')->andReturn(null);

    $mockFactory = Mockery::mock(SocialPlatformAdapterFactory::class);
    $mockFactory->shouldReceive('create')->with(SocialPlatform::LINKEDIN)->andReturn($mockAdapter);
    $mockFactory->shouldReceive('create')->with(SocialPlatform::FACEBOOK)->andReturn($mockFacebookAdapter);
    $mockFactory->shouldReceive('create')->with(SocialPlatform::INSTAGRAM)->andReturn($mockFacebookAdapter);
    $mockFactory->shouldReceive('create')->with(SocialPlatform::TWITTER)->andReturn($mockTwitterAdapter);
    $mockFactory->shouldReceive('create')->with(SocialPlatform::WHATSAPP)->andReturn($mockAdapter);

    $this->app->instance(SocialPlatformAdapterFactory::class, $mockFactory);

    // Set config so client_id appears in URLs
    config([
        'services.linkedin.client_id' => 'test_linkedin_client_id',
        'services.facebook.client_id' => 'test_facebook_client_id',
        'services.twitter.client_id' => 'test_twitter_client_id',
    ]);

    $this->service = app(OAuthService::class);
    $this->tenant = Tenant::factory()->active()->create();
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
});

describe('generateState', function () {
    it('generates random state string', function () {
        $state1 = $this->service->generateState();
        $state2 = $this->service->generateState();

        expect($state1)->toBeString();
        expect($state1)->toHaveLength(40);
        expect($state1)->not->toBe($state2);
    });
});

describe('getAuthorizationUrl', function () {
    it('returns OAuth URL data for LinkedIn', function () {
        $state = $this->service->generateState();

        $result = $this->service->getAuthorizationUrl(SocialPlatform::LINKEDIN, $state);

        expect($result->platform)->toBe('linkedin');
        expect($result->state)->toBe($state);
        expect($result->url)->toContain('linkedin.com');
        expect($result->url)->toContain('oauth');
        expect($result->url)->toContain($state);
    });

    it('returns OAuth URL data for Facebook', function () {
        $state = $this->service->generateState();

        $result = $this->service->getAuthorizationUrl(SocialPlatform::FACEBOOK, $state);

        expect($result->platform)->toBe('facebook');
        expect($result->url)->toContain('facebook.com');
    });

    it('returns OAuth URL data for Instagram', function () {
        $state = $this->service->generateState();

        $result = $this->service->getAuthorizationUrl(SocialPlatform::INSTAGRAM, $state);

        expect($result->platform)->toBe('instagram');
        expect($result->url)->toContain('facebook.com'); // Instagram uses Facebook OAuth
    });

    it('returns OAuth URL data for Twitter', function () {
        $state = $this->service->generateState();

        $result = $this->service->getAuthorizationUrl(SocialPlatform::TWITTER, $state);

        expect($result->platform)->toBe('twitter');
        expect($result->url)->toContain('twitter.com');
    });

    it('stores state in cache', function () {
        $state = $this->service->generateState();

        $this->service->getAuthorizationUrl(SocialPlatform::LINKEDIN, $state);

        $cachedData = Cache::get('oauth_state:' . $state);
        expect($cachedData)->not->toBeNull();
        expect($cachedData['platform'])->toBe('linkedin');
        expect($cachedData)->toHaveKey('created_at');
    });

    it('includes required OAuth parameters in URL', function () {
        $state = $this->service->generateState();

        $result = $this->service->getAuthorizationUrl(SocialPlatform::LINKEDIN, $state);

        expect($result->url)->toContain('client_id=');
        expect($result->url)->toContain('redirect_uri=');
        expect($result->url)->toContain('scope=');
        expect($result->url)->toContain('state=' . $state);
    });
});

describe('handleCallback', function () {
    it('returns token data for valid callback', function () {
        $state = $this->service->generateState();
        $this->service->getAuthorizationUrl(SocialPlatform::LINKEDIN, $state);

        $result = $this->service->handleCallback(SocialPlatform::LINKEDIN, 'test_code', $state);

        expect($result)->toBeInstanceOf(OAuthTokenData::class);
        expect($result->access_token)->toBeString();
        expect($result->access_token)->not->toBeEmpty();
        expect($result->platform_account_id)->toBeString();
        expect($result->account_name)->toBeString();
    });

    it('throws exception for invalid state', function () {
        $this->service->handleCallback(SocialPlatform::LINKEDIN, 'test_code', 'invalid_state');
    })->throws(\Illuminate\Validation\ValidationException::class);

    it('throws exception for expired state', function () {
        $state = $this->service->generateState();
        $this->service->getAuthorizationUrl(SocialPlatform::LINKEDIN, $state);

        // Clear the cache to simulate expiration
        Cache::forget('oauth_state:' . $state);

        $this->service->handleCallback(SocialPlatform::LINKEDIN, 'test_code', $state);
    })->throws(\Illuminate\Validation\ValidationException::class);

    it('throws exception for platform mismatch', function () {
        $state = $this->service->generateState();
        $this->service->getAuthorizationUrl(SocialPlatform::LINKEDIN, $state);

        $this->service->handleCallback(SocialPlatform::FACEBOOK, 'test_code', $state);
    })->throws(\Illuminate\Validation\ValidationException::class);

    it('clears state from cache after use', function () {
        $state = $this->service->generateState();
        $this->service->getAuthorizationUrl(SocialPlatform::LINKEDIN, $state);

        $this->service->handleCallback(SocialPlatform::LINKEDIN, 'test_code', $state);

        expect(Cache::has('oauth_state:' . $state))->toBeFalse();
    });

    it('returns platform-specific token data for Facebook', function () {
        $state = $this->service->generateState();
        $this->service->getAuthorizationUrl(SocialPlatform::FACEBOOK, $state);

        $result = $this->service->handleCallback(SocialPlatform::FACEBOOK, 'test_code', $state);

        expect($result->refresh_token)->toBeNull(); // Facebook uses long-lived tokens
    });

    it('returns platform-specific token data for Twitter', function () {
        $state = $this->service->generateState();
        $this->service->getAuthorizationUrl(SocialPlatform::TWITTER, $state);

        $result = $this->service->handleCallback(SocialPlatform::TWITTER, 'test_code', $state);

        expect($result->refresh_token)->not->toBeNull(); // Twitter uses refresh tokens
        expect($result->expires_in)->toBe(7200); // Twitter tokens expire in 2 hours
    });
});

describe('refreshToken', function () {
    it('returns new token data', function () {
        $account = SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
            'refresh_token_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString('test_refresh_token'),
        ]);

        $result = $this->service->refreshToken($account);

        expect($result)->toBeInstanceOf(OAuthTokenData::class);
        expect($result->access_token)->toBeString();
        expect($result->access_token)->toContain('refreshed_');
    });

    it('throws exception when no refresh token', function () {
        $account = SocialAccount::factory()->linkedin()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
            'refresh_token_encrypted' => null,
        ]);

        $this->service->refreshToken($account);
    })->throws(\Illuminate\Validation\ValidationException::class);

    it('preserves account information in refreshed token', function () {
        $account = SocialAccount::factory()->linkedin()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
            'account_name' => 'My Company Page',
            'account_username' => 'mycompany',
            'refresh_token_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString('test_refresh_token'),
        ]);

        $result = $this->service->refreshToken($account);

        expect($result)->toBeInstanceOf(OAuthTokenData::class);
        expect($result->access_token)->toContain('refreshed_');
        expect($result->platform_account_id)->toBeString();
        expect($result->account_name)->toBeString();
    });
});

describe('revokeToken', function () {
    it('does not throw exception', function () {
        $account = SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        // Should not throw - stubbed implementation
        $this->service->revokeToken($account);

        expect(true)->toBeTrue();
    });
});

describe('validateToken', function () {
    it('returns true for connected account with valid token', function () {
        $account = SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
            'token_expires_at' => now()->addDays(30),
        ]);

        $result = $this->service->validateToken($account);

        expect($result)->toBeTrue();
    });

    it('returns false for expired token', function () {
        $account = SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
            'token_expires_at' => now()->subDay(),
        ]);

        $result = $this->service->validateToken($account);

        expect($result)->toBeFalse();
    });

    it('returns true for account with no expiration', function () {
        $account = SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
            'token_expires_at' => null,
        ]);

        $result = $this->service->validateToken($account);

        expect($result)->toBeTrue();
    });

    it('returns false for disconnected account', function () {
        $account = SocialAccount::factory()->disconnected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $result = $this->service->validateToken($account);

        expect($result)->toBeFalse();
    });
});

describe('platform-specific OAuth scopes', function () {
    it('includes correct scopes for LinkedIn', function () {
        $state = $this->service->generateState();
        $result = $this->service->getAuthorizationUrl(SocialPlatform::LINKEDIN, $state);

        expect($result->url)->toContain('r_liteprofile');
        expect($result->url)->toContain('w_member_social');
    });

    it('includes correct scopes for Facebook', function () {
        $state = $this->service->generateState();
        $result = $this->service->getAuthorizationUrl(SocialPlatform::FACEBOOK, $state);

        expect($result->url)->toContain('pages_manage_posts');
        expect($result->url)->toContain('pages_read_engagement');
    });

    it('includes correct scopes for Twitter', function () {
        $state = $this->service->generateState();
        $result = $this->service->getAuthorizationUrl(SocialPlatform::TWITTER, $state);

        expect($result->url)->toContain('tweet.read');
        expect($result->url)->toContain('tweet.write');
        expect($result->url)->toContain('offline.access');
    });
});
