<?php

declare(strict_types=1);

use App\Data\Social\PlatformCredentials;
use App\Enums\Social\SocialPlatform;
use App\Models\Platform\SocialPlatformIntegration;
use App\Services\Social\PlatformCredentialResolver;

beforeEach(function () {
    $this->resolver = new PlatformCredentialResolver();
});

describe('PlatformCredentialResolver', function () {
    it('resolves credentials from database when integration exists', function () {
        $integration = SocialPlatformIntegration::factory()
            ->active()
            ->withAppId('1234567890')
            ->withAppSecret('secret-from-db')
            ->create([
                'provider' => 'meta',
                'api_version' => 'v24.0',
                'scopes' => [
                    'facebook' => ['pages_show_list', 'pages_manage_posts'],
                    'instagram' => ['instagram_basic'],
                ],
                'redirect_uris' => [
                    'facebook' => 'https://app.test/api/v1/oauth/facebook/callback',
                    'instagram' => 'https://app.test/api/v1/oauth/instagram/callback',
                ],
            ]);

        $credentials = $this->resolver->resolve(SocialPlatform::FACEBOOK);

        expect($credentials)->toBeInstanceOf(PlatformCredentials::class);
        expect($credentials->appId)->toBe('1234567890');
        expect($credentials->appSecret)->toBe('secret-from-db');
        expect($credentials->apiVersion)->toBe('v24.0');
        expect($credentials->scopes)->toBe(['pages_show_list', 'pages_manage_posts']);
        expect($credentials->redirectUri)->toBe('https://app.test/api/v1/oauth/facebook/callback');
    });

    it('resolves Instagram credentials from same Meta integration', function () {
        SocialPlatformIntegration::factory()
            ->active()
            ->withAppId('1234567890')
            ->withAppSecret('secret-from-db')
            ->create([
                'provider' => 'meta',
                'scopes' => [
                    'facebook' => ['pages_show_list'],
                    'instagram' => ['instagram_basic', 'instagram_content_publish'],
                ],
                'redirect_uris' => [
                    'facebook' => 'https://app.test/api/v1/oauth/facebook/callback',
                    'instagram' => 'https://app.test/api/v1/oauth/instagram/callback',
                ],
            ]);

        $credentials = $this->resolver->resolve(SocialPlatform::INSTAGRAM);

        expect($credentials->appId)->toBe('1234567890');
        expect($credentials->scopes)->toBe(['instagram_basic', 'instagram_content_publish']);
        expect($credentials->redirectUri)->toBe('https://app.test/api/v1/oauth/instagram/callback');
    });

    it('falls back to config when no active integration exists', function () {
        config([
            'services.facebook.client_id' => 'env-app-id',
            'services.facebook.client_secret' => 'env-app-secret',
            'services.facebook.redirect' => 'https://env.test/callback',
        ]);

        $credentials = $this->resolver->resolve(SocialPlatform::FACEBOOK);

        expect($credentials->appId)->toBe('env-app-id');
        expect($credentials->appSecret)->toBe('env-app-secret');
        expect($credentials->redirectUri)->toBe('https://env.test/callback');
        expect($credentials->apiVersion)->toBe('v24.0');
    });

    it('falls back to config when integration is disabled', function () {
        SocialPlatformIntegration::factory()->disabled()->create([
            'provider' => 'meta',
        ]);

        config([
            'services.facebook.client_id' => 'env-fallback-id',
            'services.facebook.client_secret' => 'env-fallback-secret',
        ]);

        $credentials = $this->resolver->resolve(SocialPlatform::FACEBOOK);

        expect($credentials->appId)->toBe('env-fallback-id');
    });

    it('builds redirect URI from app URL when config redirect is empty', function () {
        config([
            'app.url' => 'https://myapp.test',
            'services.facebook.client_id' => 'test-id',
            'services.facebook.client_secret' => 'test-secret',
            'services.facebook.redirect' => '',
        ]);

        $credentials = $this->resolver->resolve(SocialPlatform::FACEBOOK);

        expect($credentials->redirectUri)->toBe('https://myapp.test/api/v1/oauth/facebook/callback');
    });
});
