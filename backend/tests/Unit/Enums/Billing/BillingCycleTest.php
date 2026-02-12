<?php

declare(strict_types=1);

/**
 * BillingCycle Enum Unit Tests
 *
 * Tests for the BillingCycle enum which defines the billing
 * interval for subscriptions.
 *
 * @see \App\Enums\Billing\BillingCycle
 */

use App\Enums\Billing\BillingCycle;

test('has all expected cases', function (): void {
    $cases = BillingCycle::cases();

    expect($cases)->toHaveCount(2)
        ->and(BillingCycle::MONTHLY->value)->toBe('monthly')
        ->and(BillingCycle::YEARLY->value)->toBe('yearly');
});

test('label returns correct labels', function (): void {
    expect(BillingCycle::MONTHLY->label())->toBe('Monthly')
        ->and(BillingCycle::YEARLY->label())->toBe('Yearly');
});

test('intervalMonths returns correct interval', function (): void {
    expect(BillingCycle::MONTHLY->intervalMonths())->toBe(1)
        ->and(BillingCycle::YEARLY->intervalMonths())->toBe(12);
});

test('discountLabel returns correct labels', function (): void {
    expect(BillingCycle::MONTHLY->discountLabel())->toBe('')
        ->and(BillingCycle::YEARLY->discountLabel())->toBe('2 months free');
});

test('values returns all enum values', function (): void {
    $values = BillingCycle::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(2)
        ->and($values)->toContain('monthly')
        ->and($values)->toContain('yearly');
});

test('can create enum from string value', function (): void {
    $cycle = BillingCycle::from('yearly');

    expect($cycle)->toBe(BillingCycle::YEARLY);
});

test('tryFrom returns null for invalid value', function (): void {
    $cycle = BillingCycle::tryFrom('invalid');

    expect($cycle)->toBeNull();
});
