<?php

declare(strict_types=1);

/**
 * PaymentStatus Enum Unit Tests
 *
 * Tests for the PaymentStatus enum which defines the
 * status of payment transactions.
 *
 * @see \App\Enums\Billing\PaymentStatus
 */

use App\Enums\Billing\PaymentStatus;

test('has all expected cases', function (): void {
    $cases = PaymentStatus::cases();

    expect($cases)->toHaveCount(5)
        ->and(PaymentStatus::CREATED->value)->toBe('created')
        ->and(PaymentStatus::AUTHORIZED->value)->toBe('authorized')
        ->and(PaymentStatus::CAPTURED->value)->toBe('captured')
        ->and(PaymentStatus::FAILED->value)->toBe('failed')
        ->and(PaymentStatus::REFUNDED->value)->toBe('refunded');
});

test('label returns correct labels', function (): void {
    expect(PaymentStatus::CREATED->label())->toBe('Created')
        ->and(PaymentStatus::AUTHORIZED->label())->toBe('Authorized')
        ->and(PaymentStatus::CAPTURED->label())->toBe('Captured')
        ->and(PaymentStatus::FAILED->label())->toBe('Failed')
        ->and(PaymentStatus::REFUNDED->label())->toBe('Refunded');
});

test('isSuccessful returns true only for captured status', function (): void {
    expect(PaymentStatus::CAPTURED->isSuccessful())->toBeTrue()
        ->and(PaymentStatus::CREATED->isSuccessful())->toBeFalse()
        ->and(PaymentStatus::AUTHORIZED->isSuccessful())->toBeFalse()
        ->and(PaymentStatus::FAILED->isSuccessful())->toBeFalse()
        ->and(PaymentStatus::REFUNDED->isSuccessful())->toBeFalse();
});

test('isFinal returns true for captured, failed, and refunded statuses', function (): void {
    expect(PaymentStatus::CAPTURED->isFinal())->toBeTrue()
        ->and(PaymentStatus::FAILED->isFinal())->toBeTrue()
        ->and(PaymentStatus::REFUNDED->isFinal())->toBeTrue()
        ->and(PaymentStatus::CREATED->isFinal())->toBeFalse()
        ->and(PaymentStatus::AUTHORIZED->isFinal())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = PaymentStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(5)
        ->and($values)->toContain('created')
        ->and($values)->toContain('authorized')
        ->and($values)->toContain('captured')
        ->and($values)->toContain('failed')
        ->and($values)->toContain('refunded');
});

test('can create enum from string value', function (): void {
    $status = PaymentStatus::from('captured');

    expect($status)->toBe(PaymentStatus::CAPTURED);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = PaymentStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});
