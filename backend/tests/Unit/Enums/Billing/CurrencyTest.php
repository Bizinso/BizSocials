<?php

declare(strict_types=1);

/**
 * Currency Enum Unit Tests
 *
 * Tests for the Currency enum which defines the
 * supported currencies for billing.
 *
 * @see \App\Enums\Billing\Currency
 */

use App\Enums\Billing\Currency;

test('has all expected cases', function (): void {
    $cases = Currency::cases();

    expect($cases)->toHaveCount(2)
        ->and(Currency::INR->value)->toBe('INR')
        ->and(Currency::USD->value)->toBe('USD');
});

test('label returns correct labels', function (): void {
    expect(Currency::INR->label())->toBe('Indian Rupee')
        ->and(Currency::USD->label())->toBe('US Dollar');
});

test('symbol returns correct symbols', function (): void {
    expect(Currency::INR->symbol())->toBe('₹')
        ->and(Currency::USD->symbol())->toBe('$');
});

test('minorUnits returns 100 for both currencies', function (): void {
    expect(Currency::INR->minorUnits())->toBe(100)
        ->and(Currency::USD->minorUnits())->toBe(100);
});

test('values returns all enum values', function (): void {
    $values = Currency::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(2)
        ->and($values)->toContain('INR')
        ->and($values)->toContain('USD');
});

test('can create enum from string value', function (): void {
    $currency = Currency::from('INR');

    expect($currency)->toBe(Currency::INR);
});

test('tryFrom returns null for invalid value', function (): void {
    $currency = Currency::tryFrom('invalid');

    expect($currency)->toBeNull();
});
