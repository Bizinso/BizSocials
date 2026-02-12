<?php

declare(strict_types=1);

/**
 * SupportTicketStatus Enum Unit Tests
 *
 * Tests for the SupportTicketStatus enum which defines ticket status.
 *
 * @see \App\Enums\Support\SupportTicketStatus
 */

use App\Enums\Support\SupportTicketStatus;

test('has all expected cases', function (): void {
    $cases = SupportTicketStatus::cases();

    expect($cases)->toHaveCount(8)
        ->and(SupportTicketStatus::NEW->value)->toBe('new')
        ->and(SupportTicketStatus::OPEN->value)->toBe('open')
        ->and(SupportTicketStatus::IN_PROGRESS->value)->toBe('in_progress')
        ->and(SupportTicketStatus::WAITING_CUSTOMER->value)->toBe('waiting_customer')
        ->and(SupportTicketStatus::WAITING_INTERNAL->value)->toBe('waiting_internal')
        ->and(SupportTicketStatus::RESOLVED->value)->toBe('resolved')
        ->and(SupportTicketStatus::CLOSED->value)->toBe('closed')
        ->and(SupportTicketStatus::REOPENED->value)->toBe('reopened');
});

test('label returns correct labels', function (): void {
    expect(SupportTicketStatus::NEW->label())->toBe('New')
        ->and(SupportTicketStatus::OPEN->label())->toBe('Open')
        ->and(SupportTicketStatus::IN_PROGRESS->label())->toBe('In Progress')
        ->and(SupportTicketStatus::WAITING_CUSTOMER->label())->toBe('Waiting on Customer')
        ->and(SupportTicketStatus::RESOLVED->label())->toBe('Resolved')
        ->and(SupportTicketStatus::CLOSED->label())->toBe('Closed');
});

test('isOpen returns true for open statuses', function (): void {
    expect(SupportTicketStatus::NEW->isOpen())->toBeTrue()
        ->and(SupportTicketStatus::OPEN->isOpen())->toBeTrue()
        ->and(SupportTicketStatus::IN_PROGRESS->isOpen())->toBeTrue()
        ->and(SupportTicketStatus::REOPENED->isOpen())->toBeTrue()
        ->and(SupportTicketStatus::WAITING_CUSTOMER->isOpen())->toBeFalse()
        ->and(SupportTicketStatus::RESOLVED->isOpen())->toBeFalse();
});

test('isPending returns true for waiting statuses', function (): void {
    expect(SupportTicketStatus::WAITING_CUSTOMER->isPending())->toBeTrue()
        ->and(SupportTicketStatus::WAITING_INTERNAL->isPending())->toBeTrue()
        ->and(SupportTicketStatus::NEW->isPending())->toBeFalse()
        ->and(SupportTicketStatus::OPEN->isPending())->toBeFalse();
});

test('isClosed returns true for closed statuses', function (): void {
    expect(SupportTicketStatus::RESOLVED->isClosed())->toBeTrue()
        ->and(SupportTicketStatus::CLOSED->isClosed())->toBeTrue()
        ->and(SupportTicketStatus::NEW->isClosed())->toBeFalse()
        ->and(SupportTicketStatus::IN_PROGRESS->isClosed())->toBeFalse();
});

test('canTransitionTo from NEW allows correct transitions', function (): void {
    expect(SupportTicketStatus::NEW->canTransitionTo(SupportTicketStatus::OPEN))->toBeTrue()
        ->and(SupportTicketStatus::NEW->canTransitionTo(SupportTicketStatus::IN_PROGRESS))->toBeTrue()
        ->and(SupportTicketStatus::NEW->canTransitionTo(SupportTicketStatus::WAITING_CUSTOMER))->toBeTrue()
        ->and(SupportTicketStatus::NEW->canTransitionTo(SupportTicketStatus::CLOSED))->toBeTrue()
        ->and(SupportTicketStatus::NEW->canTransitionTo(SupportTicketStatus::RESOLVED))->toBeFalse()
        ->and(SupportTicketStatus::NEW->canTransitionTo(SupportTicketStatus::NEW))->toBeFalse();
});

test('canTransitionTo from CLOSED only allows REOPENED', function (): void {
    expect(SupportTicketStatus::CLOSED->canTransitionTo(SupportTicketStatus::REOPENED))->toBeTrue()
        ->and(SupportTicketStatus::CLOSED->canTransitionTo(SupportTicketStatus::NEW))->toBeFalse()
        ->and(SupportTicketStatus::CLOSED->canTransitionTo(SupportTicketStatus::OPEN))->toBeFalse();
});

test('canTransitionTo from RESOLVED allows CLOSED and REOPENED', function (): void {
    expect(SupportTicketStatus::RESOLVED->canTransitionTo(SupportTicketStatus::CLOSED))->toBeTrue()
        ->and(SupportTicketStatus::RESOLVED->canTransitionTo(SupportTicketStatus::REOPENED))->toBeTrue()
        ->and(SupportTicketStatus::RESOLVED->canTransitionTo(SupportTicketStatus::NEW))->toBeFalse();
});

test('canTransitionTo returns false for same status', function (): void {
    foreach (SupportTicketStatus::cases() as $status) {
        expect($status->canTransitionTo($status))->toBeFalse();
    }
});

test('values returns all enum values', function (): void {
    $values = SupportTicketStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(8)
        ->and($values)->toContain('new')
        ->and($values)->toContain('closed');
});
