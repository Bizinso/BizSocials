<?php

declare(strict_types=1);

/**
 * InboxItemType Enum Unit Tests
 *
 * Tests for the InboxItemType enum which defines the type of inbox item.
 *
 * @see \App\Enums\Inbox\InboxItemType
 */

use App\Enums\Inbox\InboxItemType;

test('has all expected cases', function (): void {
    $cases = InboxItemType::cases();

    expect($cases)->toHaveCount(5)
        ->and(InboxItemType::COMMENT->value)->toBe('comment')
        ->and(InboxItemType::MENTION->value)->toBe('mention')
        ->and(InboxItemType::DM->value)->toBe('dm')
        ->and(InboxItemType::WHATSAPP_MESSAGE->value)->toBe('whatsapp_message')
        ->and(InboxItemType::REVIEW->value)->toBe('review');
});

test('label returns correct labels', function (): void {
    expect(InboxItemType::COMMENT->label())->toBe('Comment')
        ->and(InboxItemType::MENTION->label())->toBe('Mention')
        ->and(InboxItemType::DM->label())->toBe('Direct Message')
        ->and(InboxItemType::WHATSAPP_MESSAGE->label())->toBe('WhatsApp Message')
        ->and(InboxItemType::REVIEW->label())->toBe('Review');
});

test('canReply returns correct values for all types', function (): void {
    expect(InboxItemType::COMMENT->canReply())->toBeTrue()
        ->and(InboxItemType::DM->canReply())->toBeTrue()
        ->and(InboxItemType::WHATSAPP_MESSAGE->canReply())->toBeTrue()
        ->and(InboxItemType::MENTION->canReply())->toBeFalse()
        ->and(InboxItemType::REVIEW->canReply())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = InboxItemType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(5)
        ->and($values)->toContain('comment')
        ->and($values)->toContain('mention')
        ->and($values)->toContain('dm')
        ->and($values)->toContain('whatsapp_message')
        ->and($values)->toContain('review');
});

test('can create enum from string value', function (): void {
    $type = InboxItemType::from('comment');

    expect($type)->toBe(InboxItemType::COMMENT);
});

test('tryFrom returns null for invalid value', function (): void {
    $type = InboxItemType::tryFrom('invalid');

    expect($type)->toBeNull();
});

test('COMMENT can be replied to', function (): void {
    expect(InboxItemType::COMMENT->canReply())->toBeTrue();
});

test('MENTION cannot be replied to', function (): void {
    expect(InboxItemType::MENTION->canReply())->toBeFalse();
});
