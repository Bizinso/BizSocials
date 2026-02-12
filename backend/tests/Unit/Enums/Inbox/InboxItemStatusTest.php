<?php

declare(strict_types=1);

/**
 * InboxItemStatus Enum Unit Tests
 *
 * Tests for the InboxItemStatus enum which defines the status of an inbox item.
 *
 * @see \App\Enums\Inbox\InboxItemStatus
 */

use App\Enums\Inbox\InboxItemStatus;

test('has all expected cases', function (): void {
    $cases = InboxItemStatus::cases();

    expect($cases)->toHaveCount(4)
        ->and(InboxItemStatus::UNREAD->value)->toBe('unread')
        ->and(InboxItemStatus::READ->value)->toBe('read')
        ->and(InboxItemStatus::RESOLVED->value)->toBe('resolved')
        ->and(InboxItemStatus::ARCHIVED->value)->toBe('archived');
});

test('label returns correct labels', function (): void {
    expect(InboxItemStatus::UNREAD->label())->toBe('Unread')
        ->and(InboxItemStatus::READ->label())->toBe('Read')
        ->and(InboxItemStatus::RESOLVED->label())->toBe('Resolved')
        ->and(InboxItemStatus::ARCHIVED->label())->toBe('Archived');
});

test('isActive returns true for UNREAD, READ, and RESOLVED', function (): void {
    expect(InboxItemStatus::UNREAD->isActive())->toBeTrue()
        ->and(InboxItemStatus::READ->isActive())->toBeTrue()
        ->and(InboxItemStatus::RESOLVED->isActive())->toBeTrue();
});

test('isActive returns false for ARCHIVED', function (): void {
    expect(InboxItemStatus::ARCHIVED->isActive())->toBeFalse();
});

test('canTransitionTo from UNREAD allows READ and ARCHIVED', function (): void {
    expect(InboxItemStatus::UNREAD->canTransitionTo(InboxItemStatus::READ))->toBeTrue()
        ->and(InboxItemStatus::UNREAD->canTransitionTo(InboxItemStatus::ARCHIVED))->toBeTrue()
        ->and(InboxItemStatus::UNREAD->canTransitionTo(InboxItemStatus::RESOLVED))->toBeFalse()
        ->and(InboxItemStatus::UNREAD->canTransitionTo(InboxItemStatus::UNREAD))->toBeFalse();
});

test('canTransitionTo from READ allows RESOLVED and ARCHIVED', function (): void {
    expect(InboxItemStatus::READ->canTransitionTo(InboxItemStatus::RESOLVED))->toBeTrue()
        ->and(InboxItemStatus::READ->canTransitionTo(InboxItemStatus::ARCHIVED))->toBeTrue()
        ->and(InboxItemStatus::READ->canTransitionTo(InboxItemStatus::UNREAD))->toBeFalse()
        ->and(InboxItemStatus::READ->canTransitionTo(InboxItemStatus::READ))->toBeFalse();
});

test('canTransitionTo from RESOLVED allows READ (reopen) and ARCHIVED', function (): void {
    expect(InboxItemStatus::RESOLVED->canTransitionTo(InboxItemStatus::READ))->toBeTrue()
        ->and(InboxItemStatus::RESOLVED->canTransitionTo(InboxItemStatus::ARCHIVED))->toBeTrue()
        ->and(InboxItemStatus::RESOLVED->canTransitionTo(InboxItemStatus::UNREAD))->toBeFalse()
        ->and(InboxItemStatus::RESOLVED->canTransitionTo(InboxItemStatus::RESOLVED))->toBeFalse();
});

test('canTransitionTo from ARCHIVED allows only READ (reopen)', function (): void {
    expect(InboxItemStatus::ARCHIVED->canTransitionTo(InboxItemStatus::READ))->toBeTrue()
        ->and(InboxItemStatus::ARCHIVED->canTransitionTo(InboxItemStatus::UNREAD))->toBeFalse()
        ->and(InboxItemStatus::ARCHIVED->canTransitionTo(InboxItemStatus::RESOLVED))->toBeFalse()
        ->and(InboxItemStatus::ARCHIVED->canTransitionTo(InboxItemStatus::ARCHIVED))->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = InboxItemStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(4)
        ->and($values)->toContain('unread')
        ->and($values)->toContain('read')
        ->and($values)->toContain('resolved')
        ->and($values)->toContain('archived');
});

test('can create enum from string value', function (): void {
    $status = InboxItemStatus::from('unread');

    expect($status)->toBe(InboxItemStatus::UNREAD);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = InboxItemStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});
