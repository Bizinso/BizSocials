<?php

declare(strict_types=1);

/**
 * RoadmapStatus Enum Unit Tests
 *
 * Tests for the RoadmapStatus enum which defines roadmap item status.
 *
 * @see \App\Enums\Feedback\RoadmapStatus
 */

use App\Enums\Feedback\RoadmapStatus;

test('has all expected cases', function (): void {
    $cases = RoadmapStatus::cases();

    expect($cases)->toHaveCount(6)
        ->and(RoadmapStatus::CONSIDERING->value)->toBe('considering')
        ->and(RoadmapStatus::PLANNED->value)->toBe('planned')
        ->and(RoadmapStatus::IN_PROGRESS->value)->toBe('in_progress')
        ->and(RoadmapStatus::BETA->value)->toBe('beta')
        ->and(RoadmapStatus::SHIPPED->value)->toBe('shipped')
        ->and(RoadmapStatus::CANCELLED->value)->toBe('cancelled');
});

test('label returns correct labels', function (): void {
    expect(RoadmapStatus::CONSIDERING->label())->toBe('Considering')
        ->and(RoadmapStatus::PLANNED->label())->toBe('Planned')
        ->and(RoadmapStatus::IN_PROGRESS->label())->toBe('In Progress')
        ->and(RoadmapStatus::BETA->label())->toBe('Beta')
        ->and(RoadmapStatus::SHIPPED->label())->toBe('Shipped')
        ->and(RoadmapStatus::CANCELLED->label())->toBe('Cancelled');
});

test('isActive returns true for active statuses', function (): void {
    expect(RoadmapStatus::PLANNED->isActive())->toBeTrue()
        ->and(RoadmapStatus::IN_PROGRESS->isActive())->toBeTrue()
        ->and(RoadmapStatus::BETA->isActive())->toBeTrue()
        ->and(RoadmapStatus::CONSIDERING->isActive())->toBeFalse()
        ->and(RoadmapStatus::SHIPPED->isActive())->toBeFalse()
        ->and(RoadmapStatus::CANCELLED->isActive())->toBeFalse();
});

test('isPublic returns false only for CANCELLED', function (): void {
    expect(RoadmapStatus::CANCELLED->isPublic())->toBeFalse()
        ->and(RoadmapStatus::CONSIDERING->isPublic())->toBeTrue()
        ->and(RoadmapStatus::PLANNED->isPublic())->toBeTrue()
        ->and(RoadmapStatus::SHIPPED->isPublic())->toBeTrue();
});

test('canTransitionTo from CONSIDERING allows correct transitions', function (): void {
    expect(RoadmapStatus::CONSIDERING->canTransitionTo(RoadmapStatus::PLANNED))->toBeTrue()
        ->and(RoadmapStatus::CONSIDERING->canTransitionTo(RoadmapStatus::CANCELLED))->toBeTrue()
        ->and(RoadmapStatus::CONSIDERING->canTransitionTo(RoadmapStatus::SHIPPED))->toBeFalse();
});

test('canTransitionTo from SHIPPED allows no transitions', function (): void {
    foreach (RoadmapStatus::cases() as $status) {
        expect(RoadmapStatus::SHIPPED->canTransitionTo($status))->toBeFalse();
    }
});

test('canTransitionTo from CANCELLED only allows CONSIDERING', function (): void {
    expect(RoadmapStatus::CANCELLED->canTransitionTo(RoadmapStatus::CONSIDERING))->toBeTrue()
        ->and(RoadmapStatus::CANCELLED->canTransitionTo(RoadmapStatus::PLANNED))->toBeFalse()
        ->and(RoadmapStatus::CANCELLED->canTransitionTo(RoadmapStatus::SHIPPED))->toBeFalse();
});

test('canTransitionTo returns false for same status', function (): void {
    foreach (RoadmapStatus::cases() as $status) {
        expect($status->canTransitionTo($status))->toBeFalse();
    }
});

test('values returns all enum values', function (): void {
    $values = RoadmapStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(6)
        ->and($values)->toContain('considering')
        ->and($values)->toContain('shipped');
});
