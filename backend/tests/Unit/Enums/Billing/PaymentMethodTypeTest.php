<?php

declare(strict_types=1);

/**
 * PaymentMethodType Enum Unit Tests
 *
 * Tests for the PaymentMethodType enum which defines the
 * types of payment methods.
 *
 * @see \App\Enums\Billing\PaymentMethodType
 */

use App\Enums\Billing\PaymentMethodType;

test('has all expected cases', function (): void {
    $cases = PaymentMethodType::cases();

    expect($cases)->toHaveCount(5)
        ->and(PaymentMethodType::CARD->value)->toBe('card')
        ->and(PaymentMethodType::UPI->value)->toBe('upi')
        ->and(PaymentMethodType::NETBANKING->value)->toBe('netbanking')
        ->and(PaymentMethodType::WALLET->value)->toBe('wallet')
        ->and(PaymentMethodType::EMANDATE->value)->toBe('emandate');
});

test('label returns correct labels', function (): void {
    expect(PaymentMethodType::CARD->label())->toBe('Card')
        ->and(PaymentMethodType::UPI->label())->toBe('UPI')
        ->and(PaymentMethodType::NETBANKING->label())->toBe('Net Banking')
        ->and(PaymentMethodType::WALLET->label())->toBe('Wallet')
        ->and(PaymentMethodType::EMANDATE->label())->toBe('e-Mandate');
});

test('icon returns correct icons', function (): void {
    expect(PaymentMethodType::CARD->icon())->toBe('credit-card')
        ->and(PaymentMethodType::UPI->icon())->toBe('smartphone')
        ->and(PaymentMethodType::NETBANKING->icon())->toBe('building')
        ->and(PaymentMethodType::WALLET->icon())->toBe('wallet')
        ->and(PaymentMethodType::EMANDATE->icon())->toBe('repeat');
});

test('supportsRecurring returns true for card and emandate', function (): void {
    expect(PaymentMethodType::CARD->supportsRecurring())->toBeTrue()
        ->and(PaymentMethodType::EMANDATE->supportsRecurring())->toBeTrue()
        ->and(PaymentMethodType::UPI->supportsRecurring())->toBeFalse()
        ->and(PaymentMethodType::NETBANKING->supportsRecurring())->toBeFalse()
        ->and(PaymentMethodType::WALLET->supportsRecurring())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = PaymentMethodType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(5)
        ->and($values)->toContain('card')
        ->and($values)->toContain('upi')
        ->and($values)->toContain('netbanking')
        ->and($values)->toContain('wallet')
        ->and($values)->toContain('emandate');
});

test('can create enum from string value', function (): void {
    $type = PaymentMethodType::from('upi');

    expect($type)->toBe(PaymentMethodType::UPI);
});

test('tryFrom returns null for invalid value', function (): void {
    $type = PaymentMethodType::tryFrom('invalid');

    expect($type)->toBeNull();
});
