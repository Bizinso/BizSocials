<?php

declare(strict_types=1);

/**
 * SocialAccountStatus Enum Unit Tests
 *
 * Tests for the SocialAccountStatus enum which defines the connection
 * status of social accounts in the system.
 *
 * @see \App\Enums\Social\SocialAccountStatus
 */

use App\Enums\Social\SocialAccountStatus;

test('has all expected cases', function (): void {
    $cases = SocialAccountStatus::cases();

    expect($cases)->toHaveCount(4)
        ->and(SocialAccountStatus::CONNECTED->value)->toBe('connected')
        ->and(SocialAccountStatus::TOKEN_EXPIRED->value)->toBe('token_expired')
        ->and(SocialAccountStatus::REVOKED->value)->toBe('revoked')
        ->and(SocialAccountStatus::DISCONNECTED->value)->toBe('disconnected');
});

test('label returns correct labels', function (): void {
    expect(SocialAccountStatus::CONNECTED->label())->toBe('Connected')
        ->and(SocialAccountStatus::TOKEN_EXPIRED->label())->toBe('Token Expired')
        ->and(SocialAccountStatus::REVOKED->label())->toBe('Revoked')
        ->and(SocialAccountStatus::DISCONNECTED->label())->toBe('Disconnected');
});

test('isHealthy returns true only for connected status', function (): void {
    expect(SocialAccountStatus::CONNECTED->isHealthy())->toBeTrue()
        ->and(SocialAccountStatus::TOKEN_EXPIRED->isHealthy())->toBeFalse()
        ->and(SocialAccountStatus::REVOKED->isHealthy())->toBeFalse()
        ->and(SocialAccountStatus::DISCONNECTED->isHealthy())->toBeFalse();
});

test('canPublish returns true only for connected status', function (): void {
    expect(SocialAccountStatus::CONNECTED->canPublish())->toBeTrue()
        ->and(SocialAccountStatus::TOKEN_EXPIRED->canPublish())->toBeFalse()
        ->and(SocialAccountStatus::REVOKED->canPublish())->toBeFalse()
        ->and(SocialAccountStatus::DISCONNECTED->canPublish())->toBeFalse();
});

test('requiresReconnect returns true for token expired and revoked', function (): void {
    expect(SocialAccountStatus::TOKEN_EXPIRED->requiresReconnect())->toBeTrue()
        ->and(SocialAccountStatus::REVOKED->requiresReconnect())->toBeTrue();
});

test('requiresReconnect returns false for connected and disconnected', function (): void {
    expect(SocialAccountStatus::CONNECTED->requiresReconnect())->toBeFalse()
        ->and(SocialAccountStatus::DISCONNECTED->requiresReconnect())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = SocialAccountStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(4)
        ->and($values)->toContain('connected')
        ->and($values)->toContain('token_expired')
        ->and($values)->toContain('revoked')
        ->and($values)->toContain('disconnected');
});

test('can create enum from string value', function (): void {
    $status = SocialAccountStatus::from('connected');

    expect($status)->toBe(SocialAccountStatus::CONNECTED);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = SocialAccountStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});
