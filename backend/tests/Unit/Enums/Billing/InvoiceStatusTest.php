<?php

declare(strict_types=1);

/**
 * InvoiceStatus Enum Unit Tests
 *
 * Tests for the InvoiceStatus enum which defines the
 * status of invoices.
 *
 * @see \App\Enums\Billing\InvoiceStatus
 */

use App\Enums\Billing\InvoiceStatus;

test('has all expected cases', function (): void {
    $cases = InvoiceStatus::cases();

    expect($cases)->toHaveCount(5)
        ->and(InvoiceStatus::DRAFT->value)->toBe('draft')
        ->and(InvoiceStatus::ISSUED->value)->toBe('issued')
        ->and(InvoiceStatus::PAID->value)->toBe('paid')
        ->and(InvoiceStatus::CANCELLED->value)->toBe('cancelled')
        ->and(InvoiceStatus::EXPIRED->value)->toBe('expired');
});

test('label returns correct labels', function (): void {
    expect(InvoiceStatus::DRAFT->label())->toBe('Draft')
        ->and(InvoiceStatus::ISSUED->label())->toBe('Issued')
        ->and(InvoiceStatus::PAID->label())->toBe('Paid')
        ->and(InvoiceStatus::CANCELLED->label())->toBe('Cancelled')
        ->and(InvoiceStatus::EXPIRED->label())->toBe('Expired');
});

test('isPaid returns true only for paid status', function (): void {
    expect(InvoiceStatus::PAID->isPaid())->toBeTrue()
        ->and(InvoiceStatus::DRAFT->isPaid())->toBeFalse()
        ->and(InvoiceStatus::ISSUED->isPaid())->toBeFalse()
        ->and(InvoiceStatus::CANCELLED->isPaid())->toBeFalse()
        ->and(InvoiceStatus::EXPIRED->isPaid())->toBeFalse();
});

test('isPayable returns true only for issued status', function (): void {
    expect(InvoiceStatus::ISSUED->isPayable())->toBeTrue()
        ->and(InvoiceStatus::DRAFT->isPayable())->toBeFalse()
        ->and(InvoiceStatus::PAID->isPayable())->toBeFalse()
        ->and(InvoiceStatus::CANCELLED->isPayable())->toBeFalse()
        ->and(InvoiceStatus::EXPIRED->isPayable())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = InvoiceStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(5)
        ->and($values)->toContain('draft')
        ->and($values)->toContain('issued')
        ->and($values)->toContain('paid')
        ->and($values)->toContain('cancelled')
        ->and($values)->toContain('expired');
});

test('can create enum from string value', function (): void {
    $status = InvoiceStatus::from('paid');

    expect($status)->toBe(InvoiceStatus::PAID);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = InvoiceStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});
