<?php

declare(strict_types=1);

/**
 * SessionStatus Enum Unit Tests
 *
 * Tests for the SessionStatus enum which defines session statuses.
 *
 * @see \App\Enums\Audit\SessionStatus
 */

use App\Enums\Audit\SessionStatus;

test('has all expected cases', function (): void {
    $cases = SessionStatus::cases();

    expect($cases)->toHaveCount(4)
        ->and(SessionStatus::ACTIVE->value)->toBe('active')
        ->and(SessionStatus::EXPIRED->value)->toBe('expired')
        ->and(SessionStatus::REVOKED->value)->toBe('revoked')
        ->and(SessionStatus::LOGGED_OUT->value)->toBe('logged_out');
});

test('label returns correct labels', function (): void {
    expect(SessionStatus::ACTIVE->label())->toBe('Active')
        ->and(SessionStatus::EXPIRED->label())->toBe('Expired')
        ->and(SessionStatus::REVOKED->label())->toBe('Revoked')
        ->and(SessionStatus::LOGGED_OUT->label())->toBe('Logged Out');
});

test('isActive returns true only for active status', function (): void {
    expect(SessionStatus::ACTIVE->isActive())->toBeTrue()
        ->and(SessionStatus::EXPIRED->isActive())->toBeFalse()
        ->and(SessionStatus::REVOKED->isActive())->toBeFalse()
        ->and(SessionStatus::LOGGED_OUT->isActive())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = SessionStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(4)
        ->and($values)->toContain('active')
        ->and($values)->toContain('expired')
        ->and($values)->toContain('revoked')
        ->and($values)->toContain('logged_out');
});
