<?php

declare(strict_types=1);

/**
 * SubscriptionStatus Enum Unit Tests
 *
 * Tests for the SubscriptionStatus enum which defines the lifecycle
 * status of subscriptions in the platform.
 *
 * @see \App\Enums\Billing\SubscriptionStatus
 */

use App\Enums\Billing\SubscriptionStatus;

test('has all expected cases', function (): void {
    $cases = SubscriptionStatus::cases();

    expect($cases)->toHaveCount(8)
        ->and(SubscriptionStatus::CREATED->value)->toBe('created')
        ->and(SubscriptionStatus::AUTHENTICATED->value)->toBe('authenticated')
        ->and(SubscriptionStatus::ACTIVE->value)->toBe('active')
        ->and(SubscriptionStatus::PENDING->value)->toBe('pending')
        ->and(SubscriptionStatus::HALTED->value)->toBe('halted')
        ->and(SubscriptionStatus::CANCELLED->value)->toBe('cancelled')
        ->and(SubscriptionStatus::COMPLETED->value)->toBe('completed')
        ->and(SubscriptionStatus::EXPIRED->value)->toBe('expired');
});

test('label returns correct labels', function (): void {
    expect(SubscriptionStatus::CREATED->label())->toBe('Created')
        ->and(SubscriptionStatus::AUTHENTICATED->label())->toBe('Authenticated')
        ->and(SubscriptionStatus::ACTIVE->label())->toBe('Active')
        ->and(SubscriptionStatus::PENDING->label())->toBe('Pending')
        ->and(SubscriptionStatus::HALTED->label())->toBe('Halted')
        ->and(SubscriptionStatus::CANCELLED->label())->toBe('Cancelled')
        ->and(SubscriptionStatus::COMPLETED->label())->toBe('Completed')
        ->and(SubscriptionStatus::EXPIRED->label())->toBe('Expired');
});

test('isActive returns true only for active status', function (): void {
    expect(SubscriptionStatus::ACTIVE->isActive())->toBeTrue()
        ->and(SubscriptionStatus::CREATED->isActive())->toBeFalse()
        ->and(SubscriptionStatus::AUTHENTICATED->isActive())->toBeFalse()
        ->and(SubscriptionStatus::PENDING->isActive())->toBeFalse()
        ->and(SubscriptionStatus::HALTED->isActive())->toBeFalse()
        ->and(SubscriptionStatus::CANCELLED->isActive())->toBeFalse()
        ->and(SubscriptionStatus::COMPLETED->isActive())->toBeFalse()
        ->and(SubscriptionStatus::EXPIRED->isActive())->toBeFalse();
});

test('hasAccess returns true for active, pending, and authenticated statuses', function (): void {
    expect(SubscriptionStatus::ACTIVE->hasAccess())->toBeTrue()
        ->and(SubscriptionStatus::PENDING->hasAccess())->toBeTrue()
        ->and(SubscriptionStatus::AUTHENTICATED->hasAccess())->toBeTrue()
        ->and(SubscriptionStatus::CREATED->hasAccess())->toBeFalse()
        ->and(SubscriptionStatus::HALTED->hasAccess())->toBeFalse()
        ->and(SubscriptionStatus::CANCELLED->hasAccess())->toBeFalse()
        ->and(SubscriptionStatus::COMPLETED->hasAccess())->toBeFalse()
        ->and(SubscriptionStatus::EXPIRED->hasAccess())->toBeFalse();
});

test('isFinal returns true for cancelled, completed, and expired statuses', function (): void {
    expect(SubscriptionStatus::CANCELLED->isFinal())->toBeTrue()
        ->and(SubscriptionStatus::COMPLETED->isFinal())->toBeTrue()
        ->and(SubscriptionStatus::EXPIRED->isFinal())->toBeTrue()
        ->and(SubscriptionStatus::CREATED->isFinal())->toBeFalse()
        ->and(SubscriptionStatus::AUTHENTICATED->isFinal())->toBeFalse()
        ->and(SubscriptionStatus::ACTIVE->isFinal())->toBeFalse()
        ->and(SubscriptionStatus::PENDING->isFinal())->toBeFalse()
        ->and(SubscriptionStatus::HALTED->isFinal())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = SubscriptionStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(8)
        ->and($values)->toContain('created')
        ->and($values)->toContain('authenticated')
        ->and($values)->toContain('active')
        ->and($values)->toContain('pending')
        ->and($values)->toContain('halted')
        ->and($values)->toContain('cancelled')
        ->and($values)->toContain('completed')
        ->and($values)->toContain('expired');
});

test('can create enum from string value', function (): void {
    $status = SubscriptionStatus::from('active');

    expect($status)->toBe(SubscriptionStatus::ACTIVE);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = SubscriptionStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});
