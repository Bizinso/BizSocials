<?php

declare(strict_types=1);

use App\Enums\Platform\IntegrationStatus;
use App\Enums\Social\SocialPlatform;
use App\Models\Platform\SocialPlatformIntegration;

describe('SocialPlatformIntegration', function () {
    it('encrypts and decrypts app_id', function () {
        $integration = SocialPlatformIntegration::factory()->create();
        $integration->setAppId('test-app-id-12345');
        $integration->save();

        $integration->refresh();

        expect($integration->getAppId())->toBe('test-app-id-12345');
        // Encrypted value should differ from plaintext
        expect($integration->app_id_encrypted)->not->toBe('test-app-id-12345');
    });

    it('encrypts and decrypts app_secret', function () {
        $integration = SocialPlatformIntegration::factory()->create();
        $integration->setAppSecret('super-secret-value');
        $integration->save();

        $integration->refresh();

        expect($integration->getAppSecret())->toBe('super-secret-value');
    });

    it('masks app_id correctly', function () {
        $integration = SocialPlatformIntegration::factory()->create();
        $integration->setAppId('1234567890123456');
        $integration->save();
        $integration->refresh();

        $masked = $integration->getMaskedAppId();

        expect($masked)->toBe('12345...3456');
    });

    it('masks short app_id with asterisks', function () {
        $integration = SocialPlatformIntegration::factory()->create();
        $integration->setAppId('short');
        $integration->save();
        $integration->refresh();

        $masked = $integration->getMaskedAppId();

        expect($masked)->toBe('*****');
    });

    it('returns scopes for specific platform', function () {
        $integration = SocialPlatformIntegration::factory()->create([
            'scopes' => [
                'facebook' => ['pages_show_list', 'pages_manage_posts'],
                'instagram' => ['instagram_basic'],
            ],
        ]);

        expect($integration->getScopesFor(SocialPlatform::FACEBOOK))
            ->toBe(['pages_show_list', 'pages_manage_posts']);

        expect($integration->getScopesFor(SocialPlatform::INSTAGRAM))
            ->toBe(['instagram_basic']);
    });

    it('returns empty array for unconfigured platform scopes', function () {
        $integration = SocialPlatformIntegration::factory()->create([
            'scopes' => ['facebook' => ['pages_show_list']],
        ]);

        expect($integration->getScopesFor(SocialPlatform::LINKEDIN))->toBe([]);
    });

    it('returns redirect URI for platform', function () {
        $integration = SocialPlatformIntegration::factory()->create([
            'redirect_uris' => [
                'facebook' => 'https://app.test/oauth/fb/callback',
                'instagram' => 'https://app.test/oauth/ig/callback',
            ],
        ]);

        expect($integration->getRedirectUri(SocialPlatform::FACEBOOK))
            ->toBe('https://app.test/oauth/fb/callback');
    });

    it('detects active status correctly', function () {
        $active = SocialPlatformIntegration::factory()->active()->create(['provider' => 'meta_active']);
        $disabled = SocialPlatformIntegration::factory()->disabled()->create(['provider' => 'meta_disabled']);
        $maintenance = SocialPlatformIntegration::factory()->maintenance()->create(['provider' => 'meta_maint']);

        expect($active->isActive())->toBeTrue();
        expect($disabled->isActive())->toBeFalse();
        expect($maintenance->isActive())->toBeFalse();
    });

    it('checks platform coverage', function () {
        $integration = SocialPlatformIntegration::factory()->create([
            'platforms' => ['facebook', 'instagram'],
        ]);

        expect($integration->coversPlatform(SocialPlatform::FACEBOOK))->toBeTrue();
        expect($integration->coversPlatform(SocialPlatform::INSTAGRAM))->toBeTrue();
        expect($integration->coversPlatform(SocialPlatform::LINKEDIN))->toBeFalse();
    });

    it('filters by platform scope', function () {
        SocialPlatformIntegration::factory()->create([
            'provider' => 'meta',
            'platforms' => ['facebook', 'instagram'],
        ]);

        $result = SocialPlatformIntegration::forPlatform(SocialPlatform::FACEBOOK)->first();
        expect($result)->not->toBeNull();
        expect($result->provider)->toBe('meta');

        $noResult = SocialPlatformIntegration::forPlatform(SocialPlatform::LINKEDIN)->first();
        expect($noResult)->toBeNull();
    });

    it('filters active integrations', function () {
        SocialPlatformIntegration::factory()->active()->create(['provider' => 'meta']);
        SocialPlatformIntegration::factory()->disabled()->create(['provider' => 'disabled_provider']);

        $activeOnly = SocialPlatformIntegration::active()->get();

        expect($activeOnly)->toHaveCount(1);
        expect($activeOnly->first()->provider)->toBe('meta');
    });

    it('handles webhook secret encryption', function () {
        $integration = SocialPlatformIntegration::factory()->create();

        $integration->setWebhookSecret('webhook-secret-123');
        $integration->save();
        $integration->refresh();

        expect($integration->getWebhookSecret())->toBe('webhook-secret-123');
    });

    it('returns null for unset webhook secret', function () {
        $integration = SocialPlatformIntegration::factory()->create();

        expect($integration->getWebhookSecret())->toBeNull();
    });
});
