<?php

declare(strict_types=1);

/**
 * SupportCommentType Enum Unit Tests
 *
 * Tests for the SupportCommentType enum which defines comment types.
 *
 * @see \App\Enums\Support\SupportCommentType
 */

use App\Enums\Support\SupportCommentType;

test('has all expected cases', function (): void {
    $cases = SupportCommentType::cases();

    expect($cases)->toHaveCount(5)
        ->and(SupportCommentType::REPLY->value)->toBe('reply')
        ->and(SupportCommentType::NOTE->value)->toBe('note')
        ->and(SupportCommentType::STATUS_CHANGE->value)->toBe('status_change')
        ->and(SupportCommentType::ASSIGNMENT->value)->toBe('assignment')
        ->and(SupportCommentType::SYSTEM->value)->toBe('system');
});

test('label returns correct labels', function (): void {
    expect(SupportCommentType::REPLY->label())->toBe('Reply')
        ->and(SupportCommentType::NOTE->label())->toBe('Internal Note')
        ->and(SupportCommentType::STATUS_CHANGE->label())->toBe('Status Change')
        ->and(SupportCommentType::ASSIGNMENT->label())->toBe('Assignment')
        ->and(SupportCommentType::SYSTEM->label())->toBe('System');
});

test('isPublic returns true only for REPLY', function (): void {
    expect(SupportCommentType::REPLY->isPublic())->toBeTrue()
        ->and(SupportCommentType::NOTE->isPublic())->toBeFalse()
        ->and(SupportCommentType::STATUS_CHANGE->isPublic())->toBeFalse()
        ->and(SupportCommentType::ASSIGNMENT->isPublic())->toBeFalse()
        ->and(SupportCommentType::SYSTEM->isPublic())->toBeFalse();
});

test('isInternal returns true for NOTE and ASSIGNMENT', function (): void {
    expect(SupportCommentType::NOTE->isInternal())->toBeTrue()
        ->and(SupportCommentType::ASSIGNMENT->isInternal())->toBeTrue()
        ->and(SupportCommentType::REPLY->isInternal())->toBeFalse()
        ->and(SupportCommentType::STATUS_CHANGE->isInternal())->toBeFalse()
        ->and(SupportCommentType::SYSTEM->isInternal())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = SupportCommentType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(5)
        ->and($values)->toContain('reply')
        ->and($values)->toContain('note');
});
