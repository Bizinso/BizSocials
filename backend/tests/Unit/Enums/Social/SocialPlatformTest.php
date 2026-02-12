<?php

declare(strict_types=1);

/**
 * SocialPlatform Enum Unit Tests
 *
 * Tests for the SocialPlatform enum which defines the supported
 * social media platforms in the system.
 *
 * @see \App\Enums\Social\SocialPlatform
 */

use App\Enums\Social\SocialPlatform;

test('has all expected cases', function (): void {
    $cases = SocialPlatform::cases();

    expect($cases)->toHaveCount(5)
        ->and(SocialPlatform::LINKEDIN->value)->toBe('linkedin')
        ->and(SocialPlatform::FACEBOOK->value)->toBe('facebook')
        ->and(SocialPlatform::INSTAGRAM->value)->toBe('instagram')
        ->and(SocialPlatform::TWITTER->value)->toBe('twitter')
        ->and(SocialPlatform::WHATSAPP->value)->toBe('whatsapp');
});

test('label returns correct labels', function (): void {
    expect(SocialPlatform::LINKEDIN->label())->toBe('LinkedIn')
        ->and(SocialPlatform::FACEBOOK->label())->toBe('Facebook')
        ->and(SocialPlatform::INSTAGRAM->label())->toBe('Instagram')
        ->and(SocialPlatform::TWITTER->label())->toBe('Twitter')
        ->and(SocialPlatform::WHATSAPP->label())->toBe('WhatsApp');
});

test('icon returns correct icons', function (): void {
    expect(SocialPlatform::LINKEDIN->icon())->toBe('linkedin')
        ->and(SocialPlatform::FACEBOOK->icon())->toBe('facebook')
        ->and(SocialPlatform::INSTAGRAM->icon())->toBe('instagram')
        ->and(SocialPlatform::TWITTER->icon())->toBe('twitter')
        ->and(SocialPlatform::WHATSAPP->icon())->toBe('whatsapp');
});

test('color returns correct brand colors', function (): void {
    expect(SocialPlatform::LINKEDIN->color())->toBe('#0A66C2')
        ->and(SocialPlatform::FACEBOOK->color())->toBe('#1877F2')
        ->and(SocialPlatform::INSTAGRAM->color())->toBe('#E4405F')
        ->and(SocialPlatform::TWITTER->color())->toBe('#1DA1F2')
        ->and(SocialPlatform::WHATSAPP->color())->toBe('#25D366');
});

test('supportsScheduling returns correct values', function (): void {
    expect(SocialPlatform::LINKEDIN->supportsScheduling())->toBeTrue()
        ->and(SocialPlatform::FACEBOOK->supportsScheduling())->toBeTrue()
        ->and(SocialPlatform::INSTAGRAM->supportsScheduling())->toBeTrue()
        ->and(SocialPlatform::TWITTER->supportsScheduling())->toBeTrue()
        ->and(SocialPlatform::WHATSAPP->supportsScheduling())->toBeFalse();
});

test('supportsImages returns true for all platforms', function (): void {
    foreach (SocialPlatform::cases() as $platform) {
        expect($platform->supportsImages())->toBeTrue();
    }
});

test('supportsVideos returns true for all platforms', function (): void {
    foreach (SocialPlatform::cases() as $platform) {
        expect($platform->supportsVideos())->toBeTrue();
    }
});

test('supportsCarousel returns true for LinkedIn and Instagram', function (): void {
    expect(SocialPlatform::LINKEDIN->supportsCarousel())->toBeTrue()
        ->and(SocialPlatform::INSTAGRAM->supportsCarousel())->toBeTrue();
});

test('supportsCarousel returns false for Facebook, Twitter and WhatsApp', function (): void {
    expect(SocialPlatform::FACEBOOK->supportsCarousel())->toBeFalse()
        ->and(SocialPlatform::TWITTER->supportsCarousel())->toBeFalse()
        ->and(SocialPlatform::WHATSAPP->supportsCarousel())->toBeFalse();
});

test('maxPostLength returns correct values for each platform', function (): void {
    expect(SocialPlatform::LINKEDIN->maxPostLength())->toBe(3000)
        ->and(SocialPlatform::FACEBOOK->maxPostLength())->toBe(63206)
        ->and(SocialPlatform::INSTAGRAM->maxPostLength())->toBe(2200)
        ->and(SocialPlatform::TWITTER->maxPostLength())->toBe(280)
        ->and(SocialPlatform::WHATSAPP->maxPostLength())->toBe(4096);
});

test('oauthScopes returns array for all platforms', function (): void {
    foreach (SocialPlatform::cases() as $platform) {
        $scopes = $platform->oauthScopes();
        expect($scopes)->toBeArray()
            ->and($scopes)->not->toBeEmpty();
    }
});

test('oauthScopes returns correct scopes for LinkedIn', function (): void {
    $scopes = SocialPlatform::LINKEDIN->oauthScopes();

    expect($scopes)->toContain('r_liteprofile')
        ->and($scopes)->toContain('r_emailaddress')
        ->and($scopes)->toContain('w_member_social')
        ->and($scopes)->toContain('r_organization_social')
        ->and($scopes)->toContain('w_organization_social');
});

test('oauthScopes returns correct scopes for Facebook', function (): void {
    $scopes = SocialPlatform::FACEBOOK->oauthScopes();

    expect($scopes)->toContain('pages_manage_posts')
        ->and($scopes)->toContain('pages_read_engagement')
        ->and($scopes)->toContain('pages_show_list')
        ->and($scopes)->toContain('public_profile');
});

test('oauthScopes returns correct scopes for Instagram', function (): void {
    $scopes = SocialPlatform::INSTAGRAM->oauthScopes();

    expect($scopes)->toContain('instagram_basic')
        ->and($scopes)->toContain('instagram_content_publish')
        ->and($scopes)->toContain('pages_show_list');
});

test('oauthScopes returns correct scopes for Twitter', function (): void {
    $scopes = SocialPlatform::TWITTER->oauthScopes();

    expect($scopes)->toContain('tweet.read')
        ->and($scopes)->toContain('tweet.write')
        ->and($scopes)->toContain('users.read')
        ->and($scopes)->toContain('offline.access');
});

test('values returns all enum values', function (): void {
    $values = SocialPlatform::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(5)
        ->and($values)->toContain('linkedin')
        ->and($values)->toContain('facebook')
        ->and($values)->toContain('instagram')
        ->and($values)->toContain('twitter')
        ->and($values)->toContain('whatsapp');
});

test('can create enum from string value', function (): void {
    $platform = SocialPlatform::from('linkedin');

    expect($platform)->toBe(SocialPlatform::LINKEDIN);
});

test('oauthScopes returns correct scopes for WhatsApp', function (): void {
    $scopes = SocialPlatform::WHATSAPP->oauthScopes();

    expect($scopes)->toContain('business_management')
        ->and($scopes)->toContain('whatsapp_business_management')
        ->and($scopes)->toContain('whatsapp_business_messaging');
});

test('isConversational returns true only for WhatsApp', function (): void {
    expect(SocialPlatform::WHATSAPP->isConversational())->toBeTrue()
        ->and(SocialPlatform::LINKEDIN->isConversational())->toBeFalse()
        ->and(SocialPlatform::FACEBOOK->isConversational())->toBeFalse()
        ->and(SocialPlatform::INSTAGRAM->isConversational())->toBeFalse()
        ->and(SocialPlatform::TWITTER->isConversational())->toBeFalse();
});

test('tryFrom returns null for invalid value', function (): void {
    $platform = SocialPlatform::tryFrom('invalid');

    expect($platform)->toBeNull();
});
