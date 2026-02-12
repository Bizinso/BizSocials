<?php

declare(strict_types=1);

/**
 * SecurityEventType Enum Unit Tests
 *
 * Tests for the SecurityEventType enum which defines security event types.
 *
 * @see \App\Enums\Audit\SecurityEventType
 */

use App\Enums\Audit\SecurityEventType;

test('has all expected cases', function (): void {
    $cases = SecurityEventType::cases();

    expect($cases)->toHaveCount(18)
        ->and(SecurityEventType::LOGIN_SUCCESS->value)->toBe('login_success')
        ->and(SecurityEventType::LOGIN_FAILURE->value)->toBe('login_failure')
        ->and(SecurityEventType::LOGOUT->value)->toBe('logout')
        ->and(SecurityEventType::PASSWORD_CHANGE->value)->toBe('password_change')
        ->and(SecurityEventType::MFA_ENABLED->value)->toBe('mfa_enabled')
        ->and(SecurityEventType::SUSPICIOUS_ACTIVITY->value)->toBe('suspicious_activity')
        ->and(SecurityEventType::ACCOUNT_LOCKED->value)->toBe('account_locked')
        ->and(SecurityEventType::IP_BLOCKED->value)->toBe('ip_blocked');
});

test('label returns correct labels', function (): void {
    expect(SecurityEventType::LOGIN_SUCCESS->label())->toBe('Login Success')
        ->and(SecurityEventType::LOGIN_FAILURE->label())->toBe('Login Failure')
        ->and(SecurityEventType::MFA_ENABLED->label())->toBe('MFA Enabled')
        ->and(SecurityEventType::SUSPICIOUS_ACTIVITY->label())->toBe('Suspicious Activity')
        ->and(SecurityEventType::ACCOUNT_LOCKED->label())->toBe('Account Locked');
});

test('severity returns correct severity levels', function (): void {
    expect(SecurityEventType::LOGIN_SUCCESS->severity())->toBe('info')
        ->and(SecurityEventType::PASSWORD_CHANGE->severity())->toBe('low')
        ->and(SecurityEventType::LOGIN_FAILURE->severity())->toBe('medium')
        ->and(SecurityEventType::SUSPICIOUS_ACTIVITY->severity())->toBe('high')
        ->and(SecurityEventType::ACCOUNT_LOCKED->severity())->toBe('critical');
});

test('requiresAlert returns true for critical events', function (): void {
    expect(SecurityEventType::SUSPICIOUS_ACTIVITY->requiresAlert())->toBeTrue()
        ->and(SecurityEventType::ACCOUNT_LOCKED->requiresAlert())->toBeTrue()
        ->and(SecurityEventType::IP_BLOCKED->requiresAlert())->toBeTrue()
        ->and(SecurityEventType::MFA_DISABLED->requiresAlert())->toBeTrue()
        ->and(SecurityEventType::LOGIN_SUCCESS->requiresAlert())->toBeFalse()
        ->and(SecurityEventType::PASSWORD_CHANGE->requiresAlert())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = SecurityEventType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(18)
        ->and($values)->toContain('login_success')
        ->and($values)->toContain('account_locked');
});
