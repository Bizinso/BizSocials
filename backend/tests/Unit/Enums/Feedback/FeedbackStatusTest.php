<?php

declare(strict_types=1);

/**
 * FeedbackStatus Enum Unit Tests
 *
 * Tests for the FeedbackStatus enum which defines feedback processing status.
 *
 * @see \App\Enums\Feedback\FeedbackStatus
 */

use App\Enums\Feedback\FeedbackStatus;

test('has all expected cases', function (): void {
    $cases = FeedbackStatus::cases();

    expect($cases)->toHaveCount(8)
        ->and(FeedbackStatus::NEW->value)->toBe('new')
        ->and(FeedbackStatus::UNDER_REVIEW->value)->toBe('under_review')
        ->and(FeedbackStatus::PLANNED->value)->toBe('planned')
        ->and(FeedbackStatus::IN_PROGRESS->value)->toBe('in_progress')
        ->and(FeedbackStatus::SHIPPED->value)->toBe('shipped')
        ->and(FeedbackStatus::DECLINED->value)->toBe('declined')
        ->and(FeedbackStatus::DUPLICATE->value)->toBe('duplicate')
        ->and(FeedbackStatus::ARCHIVED->value)->toBe('archived');
});

test('label returns correct labels', function (): void {
    expect(FeedbackStatus::NEW->label())->toBe('New')
        ->and(FeedbackStatus::UNDER_REVIEW->label())->toBe('Under Review')
        ->and(FeedbackStatus::IN_PROGRESS->label())->toBe('In Progress')
        ->and(FeedbackStatus::SHIPPED->label())->toBe('Shipped');
});

test('isOpen returns true for NEW and UNDER_REVIEW', function (): void {
    expect(FeedbackStatus::NEW->isOpen())->toBeTrue()
        ->and(FeedbackStatus::UNDER_REVIEW->isOpen())->toBeTrue()
        ->and(FeedbackStatus::PLANNED->isOpen())->toBeFalse()
        ->and(FeedbackStatus::SHIPPED->isOpen())->toBeFalse();
});

test('isActive returns true for PLANNED and IN_PROGRESS', function (): void {
    expect(FeedbackStatus::PLANNED->isActive())->toBeTrue()
        ->and(FeedbackStatus::IN_PROGRESS->isActive())->toBeTrue()
        ->and(FeedbackStatus::NEW->isActive())->toBeFalse()
        ->and(FeedbackStatus::SHIPPED->isActive())->toBeFalse();
});

test('isClosed returns true for terminal statuses', function (): void {
    expect(FeedbackStatus::SHIPPED->isClosed())->toBeTrue()
        ->and(FeedbackStatus::DECLINED->isClosed())->toBeTrue()
        ->and(FeedbackStatus::DUPLICATE->isClosed())->toBeTrue()
        ->and(FeedbackStatus::ARCHIVED->isClosed())->toBeTrue()
        ->and(FeedbackStatus::NEW->isClosed())->toBeFalse()
        ->and(FeedbackStatus::PLANNED->isClosed())->toBeFalse();
});

test('canTransitionTo from NEW allows correct transitions', function (): void {
    expect(FeedbackStatus::NEW->canTransitionTo(FeedbackStatus::UNDER_REVIEW))->toBeTrue()
        ->and(FeedbackStatus::NEW->canTransitionTo(FeedbackStatus::DUPLICATE))->toBeTrue()
        ->and(FeedbackStatus::NEW->canTransitionTo(FeedbackStatus::ARCHIVED))->toBeTrue()
        ->and(FeedbackStatus::NEW->canTransitionTo(FeedbackStatus::SHIPPED))->toBeFalse()
        ->and(FeedbackStatus::NEW->canTransitionTo(FeedbackStatus::NEW))->toBeFalse();
});

test('canTransitionTo from UNDER_REVIEW allows correct transitions', function (): void {
    expect(FeedbackStatus::UNDER_REVIEW->canTransitionTo(FeedbackStatus::PLANNED))->toBeTrue()
        ->and(FeedbackStatus::UNDER_REVIEW->canTransitionTo(FeedbackStatus::DECLINED))->toBeTrue()
        ->and(FeedbackStatus::UNDER_REVIEW->canTransitionTo(FeedbackStatus::DUPLICATE))->toBeTrue()
        ->and(FeedbackStatus::UNDER_REVIEW->canTransitionTo(FeedbackStatus::NEW))->toBeFalse();
});

test('canTransitionTo from SHIPPED only allows ARCHIVED', function (): void {
    expect(FeedbackStatus::SHIPPED->canTransitionTo(FeedbackStatus::ARCHIVED))->toBeTrue()
        ->and(FeedbackStatus::SHIPPED->canTransitionTo(FeedbackStatus::NEW))->toBeFalse()
        ->and(FeedbackStatus::SHIPPED->canTransitionTo(FeedbackStatus::PLANNED))->toBeFalse();
});

test('canTransitionTo returns false for same status', function (): void {
    foreach (FeedbackStatus::cases() as $status) {
        expect($status->canTransitionTo($status))->toBeFalse();
    }
});

test('values returns all enum values', function (): void {
    $values = FeedbackStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(8)
        ->and($values)->toContain('new')
        ->and($values)->toContain('shipped');
});
