<?php

declare(strict_types=1);

/**
 * DataRequestStatus Enum Unit Tests
 *
 * Tests for the DataRequestStatus enum which defines GDPR request statuses.
 *
 * @see \App\Enums\Audit\DataRequestStatus
 */

use App\Enums\Audit\DataRequestStatus;

test('has all expected cases', function (): void {
    $cases = DataRequestStatus::cases();

    expect($cases)->toHaveCount(5)
        ->and(DataRequestStatus::PENDING->value)->toBe('pending')
        ->and(DataRequestStatus::PROCESSING->value)->toBe('processing')
        ->and(DataRequestStatus::COMPLETED->value)->toBe('completed')
        ->and(DataRequestStatus::FAILED->value)->toBe('failed')
        ->and(DataRequestStatus::CANCELLED->value)->toBe('cancelled');
});

test('label returns correct labels', function (): void {
    expect(DataRequestStatus::PENDING->label())->toBe('Pending')
        ->and(DataRequestStatus::PROCESSING->label())->toBe('Processing')
        ->and(DataRequestStatus::COMPLETED->label())->toBe('Completed')
        ->and(DataRequestStatus::FAILED->label())->toBe('Failed')
        ->and(DataRequestStatus::CANCELLED->label())->toBe('Cancelled');
});

test('isFinal returns true for final statuses', function (): void {
    expect(DataRequestStatus::COMPLETED->isFinal())->toBeTrue()
        ->and(DataRequestStatus::FAILED->isFinal())->toBeTrue()
        ->and(DataRequestStatus::CANCELLED->isFinal())->toBeTrue()
        ->and(DataRequestStatus::PENDING->isFinal())->toBeFalse()
        ->and(DataRequestStatus::PROCESSING->isFinal())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = DataRequestStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(5)
        ->and($values)->toContain('pending')
        ->and($values)->toContain('completed')
        ->and($values)->toContain('cancelled');
});
