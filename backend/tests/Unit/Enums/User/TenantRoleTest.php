<?php

declare(strict_types=1);

/**
 * TenantRole Enum Unit Tests
 *
 * Tests for the TenantRole enum which defines the role
 * of users within a tenant organization.
 *
 * @see \App\Enums\User\TenantRole
 */

use App\Enums\User\TenantRole;

test('has all expected cases', function (): void {
    $cases = TenantRole::cases();

    expect($cases)->toHaveCount(3)
        ->and(TenantRole::OWNER->value)->toBe('owner')
        ->and(TenantRole::ADMIN->value)->toBe('admin')
        ->and(TenantRole::MEMBER->value)->toBe('member');
});

test('label returns correct labels', function (): void {
    expect(TenantRole::OWNER->label())->toBe('Owner')
        ->and(TenantRole::ADMIN->label())->toBe('Admin')
        ->and(TenantRole::MEMBER->label())->toBe('Member');
});

test('canManageBilling returns true only for owner', function (): void {
    expect(TenantRole::OWNER->canManageBilling())->toBeTrue()
        ->and(TenantRole::ADMIN->canManageBilling())->toBeFalse()
        ->and(TenantRole::MEMBER->canManageBilling())->toBeFalse();
});

test('canManageUsers returns true for owner and admin', function (): void {
    expect(TenantRole::OWNER->canManageUsers())->toBeTrue()
        ->and(TenantRole::ADMIN->canManageUsers())->toBeTrue()
        ->and(TenantRole::MEMBER->canManageUsers())->toBeFalse();
});

test('canDeleteTenant returns true only for owner', function (): void {
    expect(TenantRole::OWNER->canDeleteTenant())->toBeTrue()
        ->and(TenantRole::ADMIN->canDeleteTenant())->toBeFalse()
        ->and(TenantRole::MEMBER->canDeleteTenant())->toBeFalse();
});

test('isAtLeast works correctly for owner', function (): void {
    expect(TenantRole::OWNER->isAtLeast(TenantRole::OWNER))->toBeTrue()
        ->and(TenantRole::OWNER->isAtLeast(TenantRole::ADMIN))->toBeTrue()
        ->and(TenantRole::OWNER->isAtLeast(TenantRole::MEMBER))->toBeTrue();
});

test('isAtLeast works correctly for admin', function (): void {
    expect(TenantRole::ADMIN->isAtLeast(TenantRole::OWNER))->toBeFalse()
        ->and(TenantRole::ADMIN->isAtLeast(TenantRole::ADMIN))->toBeTrue()
        ->and(TenantRole::ADMIN->isAtLeast(TenantRole::MEMBER))->toBeTrue();
});

test('isAtLeast works correctly for member', function (): void {
    expect(TenantRole::MEMBER->isAtLeast(TenantRole::OWNER))->toBeFalse()
        ->and(TenantRole::MEMBER->isAtLeast(TenantRole::ADMIN))->toBeFalse()
        ->and(TenantRole::MEMBER->isAtLeast(TenantRole::MEMBER))->toBeTrue();
});

test('values returns all enum values', function (): void {
    $values = TenantRole::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(3)
        ->and($values)->toContain('owner')
        ->and($values)->toContain('admin')
        ->and($values)->toContain('member');
});

test('can create enum from string value', function (): void {
    $role = TenantRole::from('admin');

    expect($role)->toBe(TenantRole::ADMIN);
});

test('tryFrom returns null for invalid value', function (): void {
    $role = TenantRole::tryFrom('invalid');

    expect($role)->toBeNull();
});
