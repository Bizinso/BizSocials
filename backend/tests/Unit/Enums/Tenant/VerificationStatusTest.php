<?php

declare(strict_types=1);

/**
 * VerificationStatus Enum Unit Tests
 *
 * Tests for the VerificationStatus enum which defines the
 * verification status for tenant business profiles.
 *
 * @see \App\Enums\Tenant\VerificationStatus
 */

use App\Enums\Tenant\VerificationStatus;

test('has all expected cases', function (): void {
    $cases = VerificationStatus::cases();

    expect($cases)->toHaveCount(3)
        ->and(VerificationStatus::PENDING->value)->toBe('pending')
        ->and(VerificationStatus::VERIFIED->value)->toBe('verified')
        ->and(VerificationStatus::FAILED->value)->toBe('failed');
});

test('label returns correct labels', function (): void {
    expect(VerificationStatus::PENDING->label())->toBe('Pending')
        ->and(VerificationStatus::VERIFIED->label())->toBe('Verified')
        ->and(VerificationStatus::FAILED->label())->toBe('Failed');
});

test('isVerified returns true only for verified status', function (): void {
    expect(VerificationStatus::VERIFIED->isVerified())->toBeTrue()
        ->and(VerificationStatus::PENDING->isVerified())->toBeFalse()
        ->and(VerificationStatus::FAILED->isVerified())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = VerificationStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(3)
        ->and($values)->toContain('pending')
        ->and($values)->toContain('verified')
        ->and($values)->toContain('failed');
});

test('can create enum from string value', function (): void {
    $status = VerificationStatus::from('verified');

    expect($status)->toBe(VerificationStatus::VERIFIED);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = VerificationStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});
