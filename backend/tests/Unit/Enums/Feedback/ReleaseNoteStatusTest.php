<?php

declare(strict_types=1);

/**
 * ReleaseNoteStatus Enum Unit Tests
 *
 * Tests for the ReleaseNoteStatus enum which defines release note status.
 *
 * @see \App\Enums\Feedback\ReleaseNoteStatus
 */

use App\Enums\Feedback\ReleaseNoteStatus;

test('has all expected cases', function (): void {
    $cases = ReleaseNoteStatus::cases();

    expect($cases)->toHaveCount(3)
        ->and(ReleaseNoteStatus::DRAFT->value)->toBe('draft')
        ->and(ReleaseNoteStatus::SCHEDULED->value)->toBe('scheduled')
        ->and(ReleaseNoteStatus::PUBLISHED->value)->toBe('published');
});

test('label returns correct labels', function (): void {
    expect(ReleaseNoteStatus::DRAFT->label())->toBe('Draft')
        ->and(ReleaseNoteStatus::SCHEDULED->label())->toBe('Scheduled')
        ->and(ReleaseNoteStatus::PUBLISHED->label())->toBe('Published');
});

test('isVisible returns true only for PUBLISHED', function (): void {
    expect(ReleaseNoteStatus::PUBLISHED->isVisible())->toBeTrue()
        ->and(ReleaseNoteStatus::DRAFT->isVisible())->toBeFalse()
        ->and(ReleaseNoteStatus::SCHEDULED->isVisible())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = ReleaseNoteStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(3)
        ->and($values)->toContain('draft')
        ->and($values)->toContain('published');
});

test('can create enum from string value', function (): void {
    $status = ReleaseNoteStatus::from('published');

    expect($status)->toBe(ReleaseNoteStatus::PUBLISHED);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = ReleaseNoteStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});
