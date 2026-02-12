<?php

declare(strict_types=1);

/**
 * UserStatus Enum Unit Tests
 *
 * Tests for the UserStatus enum which defines the lifecycle
 * status of users in the platform.
 *
 * @see \App\Enums\User\UserStatus
 */

use App\Enums\User\UserStatus;

test('has all expected cases', function (): void {
    $cases = UserStatus::cases();

    expect($cases)->toHaveCount(4)
        ->and(UserStatus::PENDING->value)->toBe('pending')
        ->and(UserStatus::ACTIVE->value)->toBe('active')
        ->and(UserStatus::SUSPENDED->value)->toBe('suspended')
        ->and(UserStatus::DEACTIVATED->value)->toBe('deactivated');
});

test('label returns correct labels', function (): void {
    expect(UserStatus::PENDING->label())->toBe('Pending')
        ->and(UserStatus::ACTIVE->label())->toBe('Active')
        ->and(UserStatus::SUSPENDED->label())->toBe('Suspended')
        ->and(UserStatus::DEACTIVATED->label())->toBe('Deactivated');
});

test('canLogin returns true only for active status', function (): void {
    expect(UserStatus::ACTIVE->canLogin())->toBeTrue()
        ->and(UserStatus::PENDING->canLogin())->toBeFalse()
        ->and(UserStatus::SUSPENDED->canLogin())->toBeFalse()
        ->and(UserStatus::DEACTIVATED->canLogin())->toBeFalse();
});

test('pending can only transition to active', function (): void {
    expect(UserStatus::PENDING->canTransitionTo(UserStatus::ACTIVE))->toBeTrue()
        ->and(UserStatus::PENDING->canTransitionTo(UserStatus::SUSPENDED))->toBeFalse()
        ->and(UserStatus::PENDING->canTransitionTo(UserStatus::DEACTIVATED))->toBeFalse()
        ->and(UserStatus::PENDING->canTransitionTo(UserStatus::PENDING))->toBeFalse();
});

test('active can transition to suspended or deactivated', function (): void {
    expect(UserStatus::ACTIVE->canTransitionTo(UserStatus::SUSPENDED))->toBeTrue()
        ->and(UserStatus::ACTIVE->canTransitionTo(UserStatus::DEACTIVATED))->toBeTrue()
        ->and(UserStatus::ACTIVE->canTransitionTo(UserStatus::PENDING))->toBeFalse()
        ->and(UserStatus::ACTIVE->canTransitionTo(UserStatus::ACTIVE))->toBeFalse();
});

test('suspended can transition to active or deactivated', function (): void {
    expect(UserStatus::SUSPENDED->canTransitionTo(UserStatus::ACTIVE))->toBeTrue()
        ->and(UserStatus::SUSPENDED->canTransitionTo(UserStatus::DEACTIVATED))->toBeTrue()
        ->and(UserStatus::SUSPENDED->canTransitionTo(UserStatus::PENDING))->toBeFalse()
        ->and(UserStatus::SUSPENDED->canTransitionTo(UserStatus::SUSPENDED))->toBeFalse();
});

test('deactivated cannot transition to any status', function (): void {
    expect(UserStatus::DEACTIVATED->canTransitionTo(UserStatus::PENDING))->toBeFalse()
        ->and(UserStatus::DEACTIVATED->canTransitionTo(UserStatus::ACTIVE))->toBeFalse()
        ->and(UserStatus::DEACTIVATED->canTransitionTo(UserStatus::SUSPENDED))->toBeFalse()
        ->and(UserStatus::DEACTIVATED->canTransitionTo(UserStatus::DEACTIVATED))->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = UserStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(4)
        ->and($values)->toContain('pending')
        ->and($values)->toContain('active')
        ->and($values)->toContain('suspended')
        ->and($values)->toContain('deactivated');
});

test('can create enum from string value', function (): void {
    $status = UserStatus::from('active');

    expect($status)->toBe(UserStatus::ACTIVE);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = UserStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});
