<?php

declare(strict_types=1);

/**
 * KBFeedbackStatus Enum Unit Tests
 *
 * Tests for the KBFeedbackStatus enum which defines the processing
 * status of article feedback.
 *
 * @see \App\Enums\KnowledgeBase\KBFeedbackStatus
 */

use App\Enums\KnowledgeBase\KBFeedbackStatus;

test('has all expected cases', function (): void {
    $cases = KBFeedbackStatus::cases();

    expect($cases)->toHaveCount(4)
        ->and(KBFeedbackStatus::PENDING->value)->toBe('pending')
        ->and(KBFeedbackStatus::REVIEWED->value)->toBe('reviewed')
        ->and(KBFeedbackStatus::ACTIONED->value)->toBe('actioned')
        ->and(KBFeedbackStatus::DISMISSED->value)->toBe('dismissed');
});

test('label returns correct labels', function (): void {
    expect(KBFeedbackStatus::PENDING->label())->toBe('Pending')
        ->and(KBFeedbackStatus::REVIEWED->label())->toBe('Reviewed')
        ->and(KBFeedbackStatus::ACTIONED->label())->toBe('Actioned')
        ->and(KBFeedbackStatus::DISMISSED->label())->toBe('Dismissed');
});

test('isOpen returns true only for PENDING', function (): void {
    expect(KBFeedbackStatus::PENDING->isOpen())->toBeTrue()
        ->and(KBFeedbackStatus::REVIEWED->isOpen())->toBeFalse()
        ->and(KBFeedbackStatus::ACTIONED->isOpen())->toBeFalse()
        ->and(KBFeedbackStatus::DISMISSED->isOpen())->toBeFalse();
});

test('isClosed returns true for closed statuses', function (): void {
    expect(KBFeedbackStatus::PENDING->isClosed())->toBeFalse()
        ->and(KBFeedbackStatus::REVIEWED->isClosed())->toBeTrue()
        ->and(KBFeedbackStatus::ACTIONED->isClosed())->toBeTrue()
        ->and(KBFeedbackStatus::DISMISSED->isClosed())->toBeTrue();
});

test('isOpen and isClosed are mutually exclusive', function (): void {
    foreach (KBFeedbackStatus::cases() as $status) {
        expect($status->isOpen())->not->toBe($status->isClosed());
    }
});

test('values returns all enum values', function (): void {
    $values = KBFeedbackStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(4)
        ->and($values)->toContain('pending')
        ->and($values)->toContain('reviewed')
        ->and($values)->toContain('actioned')
        ->and($values)->toContain('dismissed');
});

test('can create enum from string value', function (): void {
    $status = KBFeedbackStatus::from('pending');

    expect($status)->toBe(KBFeedbackStatus::PENDING);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = KBFeedbackStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});
