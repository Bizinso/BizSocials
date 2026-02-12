<?php

declare(strict_types=1);

/**
 * TenantStatus Enum Unit Tests
 *
 * Tests for the TenantStatus enum which defines the lifecycle
 * status of tenants in the platform.
 *
 * @see \App\Enums\Tenant\TenantStatus
 */

use App\Enums\Tenant\TenantStatus;

test('has all expected cases', function (): void {
    $cases = TenantStatus::cases();

    expect($cases)->toHaveCount(4)
        ->and(TenantStatus::PENDING->value)->toBe('pending')
        ->and(TenantStatus::ACTIVE->value)->toBe('active')
        ->and(TenantStatus::SUSPENDED->value)->toBe('suspended')
        ->and(TenantStatus::TERMINATED->value)->toBe('terminated');
});

test('label returns correct labels', function (): void {
    expect(TenantStatus::PENDING->label())->toBe('Pending')
        ->and(TenantStatus::ACTIVE->label())->toBe('Active')
        ->and(TenantStatus::SUSPENDED->label())->toBe('Suspended')
        ->and(TenantStatus::TERMINATED->label())->toBe('Terminated');
});

test('canAccess returns true only for active status', function (): void {
    expect(TenantStatus::ACTIVE->canAccess())->toBeTrue()
        ->and(TenantStatus::PENDING->canAccess())->toBeFalse()
        ->and(TenantStatus::SUSPENDED->canAccess())->toBeFalse()
        ->and(TenantStatus::TERMINATED->canAccess())->toBeFalse();
});

test('pending can only transition to active', function (): void {
    expect(TenantStatus::PENDING->canTransitionTo(TenantStatus::ACTIVE))->toBeTrue()
        ->and(TenantStatus::PENDING->canTransitionTo(TenantStatus::SUSPENDED))->toBeFalse()
        ->and(TenantStatus::PENDING->canTransitionTo(TenantStatus::TERMINATED))->toBeFalse()
        ->and(TenantStatus::PENDING->canTransitionTo(TenantStatus::PENDING))->toBeFalse();
});

test('active can transition to suspended or terminated', function (): void {
    expect(TenantStatus::ACTIVE->canTransitionTo(TenantStatus::SUSPENDED))->toBeTrue()
        ->and(TenantStatus::ACTIVE->canTransitionTo(TenantStatus::TERMINATED))->toBeTrue()
        ->and(TenantStatus::ACTIVE->canTransitionTo(TenantStatus::PENDING))->toBeFalse()
        ->and(TenantStatus::ACTIVE->canTransitionTo(TenantStatus::ACTIVE))->toBeFalse();
});

test('suspended can transition to active or terminated', function (): void {
    expect(TenantStatus::SUSPENDED->canTransitionTo(TenantStatus::ACTIVE))->toBeTrue()
        ->and(TenantStatus::SUSPENDED->canTransitionTo(TenantStatus::TERMINATED))->toBeTrue()
        ->and(TenantStatus::SUSPENDED->canTransitionTo(TenantStatus::PENDING))->toBeFalse()
        ->and(TenantStatus::SUSPENDED->canTransitionTo(TenantStatus::SUSPENDED))->toBeFalse();
});

test('terminated cannot transition to any status', function (): void {
    expect(TenantStatus::TERMINATED->canTransitionTo(TenantStatus::PENDING))->toBeFalse()
        ->and(TenantStatus::TERMINATED->canTransitionTo(TenantStatus::ACTIVE))->toBeFalse()
        ->and(TenantStatus::TERMINATED->canTransitionTo(TenantStatus::SUSPENDED))->toBeFalse()
        ->and(TenantStatus::TERMINATED->canTransitionTo(TenantStatus::TERMINATED))->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = TenantStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(4)
        ->and($values)->toContain('pending')
        ->and($values)->toContain('active')
        ->and($values)->toContain('suspended')
        ->and($values)->toContain('terminated');
});

test('can create enum from string value', function (): void {
    $status = TenantStatus::from('active');

    expect($status)->toBe(TenantStatus::ACTIVE);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = TenantStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});
