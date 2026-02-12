<?php

declare(strict_types=1);

/**
 * MediaProcessingStatus Enum Unit Tests
 *
 * Tests for the MediaProcessingStatus enum which defines
 * the processing status of uploaded media.
 *
 * @see \App\Enums\Content\MediaProcessingStatus
 */

use App\Enums\Content\MediaProcessingStatus;

test('has all expected cases', function (): void {
    $cases = MediaProcessingStatus::cases();

    expect($cases)->toHaveCount(4)
        ->and(MediaProcessingStatus::PENDING->value)->toBe('pending')
        ->and(MediaProcessingStatus::PROCESSING->value)->toBe('processing')
        ->and(MediaProcessingStatus::COMPLETED->value)->toBe('completed')
        ->and(MediaProcessingStatus::FAILED->value)->toBe('failed');
});

test('label returns correct labels', function (): void {
    expect(MediaProcessingStatus::PENDING->label())->toBe('Pending')
        ->and(MediaProcessingStatus::PROCESSING->label())->toBe('Processing')
        ->and(MediaProcessingStatus::COMPLETED->label())->toBe('Completed')
        ->and(MediaProcessingStatus::FAILED->label())->toBe('Failed');
});

test('isReady returns true only for COMPLETED', function (): void {
    expect(MediaProcessingStatus::COMPLETED->isReady())->toBeTrue()
        ->and(MediaProcessingStatus::PENDING->isReady())->toBeFalse()
        ->and(MediaProcessingStatus::PROCESSING->isReady())->toBeFalse()
        ->and(MediaProcessingStatus::FAILED->isReady())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = MediaProcessingStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(4)
        ->and($values)->toContain('pending')
        ->and($values)->toContain('processing')
        ->and($values)->toContain('completed')
        ->and($values)->toContain('failed');
});

test('can create enum from string value', function (): void {
    $status = MediaProcessingStatus::from('pending');

    expect($status)->toBe(MediaProcessingStatus::PENDING);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = MediaProcessingStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});
