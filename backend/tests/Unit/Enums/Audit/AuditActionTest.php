<?php

declare(strict_types=1);

/**
 * AuditAction Enum Unit Tests
 *
 * Tests for the AuditAction enum which defines audit log actions.
 *
 * @see \App\Enums\Audit\AuditAction
 */

use App\Enums\Audit\AuditAction;

test('has all expected cases', function (): void {
    $cases = AuditAction::cases();

    expect($cases)->toHaveCount(12)
        ->and(AuditAction::CREATE->value)->toBe('create')
        ->and(AuditAction::UPDATE->value)->toBe('update')
        ->and(AuditAction::DELETE->value)->toBe('delete')
        ->and(AuditAction::RESTORE->value)->toBe('restore')
        ->and(AuditAction::VIEW->value)->toBe('view')
        ->and(AuditAction::EXPORT->value)->toBe('export')
        ->and(AuditAction::IMPORT->value)->toBe('import')
        ->and(AuditAction::LOGIN->value)->toBe('login')
        ->and(AuditAction::LOGOUT->value)->toBe('logout')
        ->and(AuditAction::PERMISSION_CHANGE->value)->toBe('permission_change')
        ->and(AuditAction::SETTINGS_CHANGE->value)->toBe('settings_change')
        ->and(AuditAction::SUBSCRIPTION_CHANGE->value)->toBe('subscription_change');
});

test('label returns correct labels', function (): void {
    expect(AuditAction::CREATE->label())->toBe('Create')
        ->and(AuditAction::UPDATE->label())->toBe('Update')
        ->and(AuditAction::DELETE->label())->toBe('Delete')
        ->and(AuditAction::LOGIN->label())->toBe('Login')
        ->and(AuditAction::PERMISSION_CHANGE->label())->toBe('Permission Change');
});

test('isWrite returns true for write operations', function (): void {
    expect(AuditAction::CREATE->isWrite())->toBeTrue()
        ->and(AuditAction::UPDATE->isWrite())->toBeTrue()
        ->and(AuditAction::DELETE->isWrite())->toBeTrue()
        ->and(AuditAction::VIEW->isWrite())->toBeFalse()
        ->and(AuditAction::LOGIN->isWrite())->toBeFalse();
});

test('isRead returns true for read operations', function (): void {
    expect(AuditAction::VIEW->isRead())->toBeTrue()
        ->and(AuditAction::EXPORT->isRead())->toBeTrue()
        ->and(AuditAction::CREATE->isRead())->toBeFalse()
        ->and(AuditAction::LOGIN->isRead())->toBeFalse();
});

test('isAuth returns true for auth operations', function (): void {
    expect(AuditAction::LOGIN->isAuth())->toBeTrue()
        ->and(AuditAction::LOGOUT->isAuth())->toBeTrue()
        ->and(AuditAction::CREATE->isAuth())->toBeFalse()
        ->and(AuditAction::VIEW->isAuth())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = AuditAction::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(12)
        ->and($values)->toContain('create')
        ->and($values)->toContain('login')
        ->and($values)->toContain('subscription_change');
});
