<?php

declare(strict_types=1);

/**
 * PostStatus Enum Unit Tests
 *
 * Tests for the PostStatus enum which defines the workflow status
 * of a social media post.
 *
 * @see \App\Enums\Content\PostStatus
 */

use App\Enums\Content\PostStatus;

test('has all expected cases', function (): void {
    $cases = PostStatus::cases();

    expect($cases)->toHaveCount(9)
        ->and(PostStatus::DRAFT->value)->toBe('draft')
        ->and(PostStatus::SUBMITTED->value)->toBe('submitted')
        ->and(PostStatus::APPROVED->value)->toBe('approved')
        ->and(PostStatus::REJECTED->value)->toBe('rejected')
        ->and(PostStatus::SCHEDULED->value)->toBe('scheduled')
        ->and(PostStatus::PUBLISHING->value)->toBe('publishing')
        ->and(PostStatus::PUBLISHED->value)->toBe('published')
        ->and(PostStatus::FAILED->value)->toBe('failed')
        ->and(PostStatus::CANCELLED->value)->toBe('cancelled');
});

test('label returns correct labels', function (): void {
    expect(PostStatus::DRAFT->label())->toBe('Draft')
        ->and(PostStatus::SUBMITTED->label())->toBe('Submitted')
        ->and(PostStatus::APPROVED->label())->toBe('Approved')
        ->and(PostStatus::REJECTED->label())->toBe('Rejected')
        ->and(PostStatus::SCHEDULED->label())->toBe('Scheduled')
        ->and(PostStatus::PUBLISHING->label())->toBe('Publishing')
        ->and(PostStatus::PUBLISHED->label())->toBe('Published')
        ->and(PostStatus::FAILED->label())->toBe('Failed')
        ->and(PostStatus::CANCELLED->label())->toBe('Cancelled');
});

test('canEdit returns true only for DRAFT and REJECTED', function (): void {
    expect(PostStatus::DRAFT->canEdit())->toBeTrue()
        ->and(PostStatus::REJECTED->canEdit())->toBeTrue()
        ->and(PostStatus::SUBMITTED->canEdit())->toBeFalse()
        ->and(PostStatus::APPROVED->canEdit())->toBeFalse()
        ->and(PostStatus::SCHEDULED->canEdit())->toBeFalse()
        ->and(PostStatus::PUBLISHING->canEdit())->toBeFalse()
        ->and(PostStatus::PUBLISHED->canEdit())->toBeFalse()
        ->and(PostStatus::FAILED->canEdit())->toBeFalse()
        ->and(PostStatus::CANCELLED->canEdit())->toBeFalse();
});

test('canDelete returns false only for PUBLISHED', function (): void {
    expect(PostStatus::PUBLISHED->canDelete())->toBeFalse()
        ->and(PostStatus::DRAFT->canDelete())->toBeTrue()
        ->and(PostStatus::SUBMITTED->canDelete())->toBeTrue()
        ->and(PostStatus::APPROVED->canDelete())->toBeTrue()
        ->and(PostStatus::REJECTED->canDelete())->toBeTrue()
        ->and(PostStatus::SCHEDULED->canDelete())->toBeTrue()
        ->and(PostStatus::PUBLISHING->canDelete())->toBeTrue()
        ->and(PostStatus::FAILED->canDelete())->toBeTrue()
        ->and(PostStatus::CANCELLED->canDelete())->toBeTrue();
});

test('canPublish returns true only for APPROVED and SCHEDULED', function (): void {
    expect(PostStatus::APPROVED->canPublish())->toBeTrue()
        ->and(PostStatus::SCHEDULED->canPublish())->toBeTrue()
        ->and(PostStatus::DRAFT->canPublish())->toBeFalse()
        ->and(PostStatus::SUBMITTED->canPublish())->toBeFalse()
        ->and(PostStatus::REJECTED->canPublish())->toBeFalse()
        ->and(PostStatus::PUBLISHING->canPublish())->toBeFalse()
        ->and(PostStatus::PUBLISHED->canPublish())->toBeFalse()
        ->and(PostStatus::FAILED->canPublish())->toBeFalse()
        ->and(PostStatus::CANCELLED->canPublish())->toBeFalse();
});

test('isTerminal returns true only for PUBLISHED and CANCELLED', function (): void {
    expect(PostStatus::PUBLISHED->isTerminal())->toBeTrue()
        ->and(PostStatus::CANCELLED->isTerminal())->toBeTrue()
        ->and(PostStatus::DRAFT->isTerminal())->toBeFalse()
        ->and(PostStatus::SUBMITTED->isTerminal())->toBeFalse()
        ->and(PostStatus::APPROVED->isTerminal())->toBeFalse()
        ->and(PostStatus::REJECTED->isTerminal())->toBeFalse()
        ->and(PostStatus::SCHEDULED->isTerminal())->toBeFalse()
        ->and(PostStatus::PUBLISHING->isTerminal())->toBeFalse()
        ->and(PostStatus::FAILED->isTerminal())->toBeFalse();
});

test('canTransitionTo from DRAFT allows SUBMITTED and CANCELLED', function (): void {
    expect(PostStatus::DRAFT->canTransitionTo(PostStatus::SUBMITTED))->toBeTrue()
        ->and(PostStatus::DRAFT->canTransitionTo(PostStatus::CANCELLED))->toBeTrue()
        ->and(PostStatus::DRAFT->canTransitionTo(PostStatus::APPROVED))->toBeFalse()
        ->and(PostStatus::DRAFT->canTransitionTo(PostStatus::REJECTED))->toBeFalse()
        ->and(PostStatus::DRAFT->canTransitionTo(PostStatus::SCHEDULED))->toBeFalse()
        ->and(PostStatus::DRAFT->canTransitionTo(PostStatus::PUBLISHING))->toBeFalse()
        ->and(PostStatus::DRAFT->canTransitionTo(PostStatus::PUBLISHED))->toBeFalse()
        ->and(PostStatus::DRAFT->canTransitionTo(PostStatus::FAILED))->toBeFalse();
});

test('canTransitionTo from SUBMITTED allows APPROVED and REJECTED', function (): void {
    expect(PostStatus::SUBMITTED->canTransitionTo(PostStatus::APPROVED))->toBeTrue()
        ->and(PostStatus::SUBMITTED->canTransitionTo(PostStatus::REJECTED))->toBeTrue()
        ->and(PostStatus::SUBMITTED->canTransitionTo(PostStatus::DRAFT))->toBeFalse()
        ->and(PostStatus::SUBMITTED->canTransitionTo(PostStatus::SCHEDULED))->toBeFalse()
        ->and(PostStatus::SUBMITTED->canTransitionTo(PostStatus::PUBLISHING))->toBeFalse()
        ->and(PostStatus::SUBMITTED->canTransitionTo(PostStatus::PUBLISHED))->toBeFalse()
        ->and(PostStatus::SUBMITTED->canTransitionTo(PostStatus::CANCELLED))->toBeFalse();
});

test('canTransitionTo from APPROVED allows SCHEDULED and PUBLISHING', function (): void {
    expect(PostStatus::APPROVED->canTransitionTo(PostStatus::SCHEDULED))->toBeTrue()
        ->and(PostStatus::APPROVED->canTransitionTo(PostStatus::PUBLISHING))->toBeTrue()
        ->and(PostStatus::APPROVED->canTransitionTo(PostStatus::REJECTED))->toBeFalse()
        ->and(PostStatus::APPROVED->canTransitionTo(PostStatus::DRAFT))->toBeFalse()
        ->and(PostStatus::APPROVED->canTransitionTo(PostStatus::SUBMITTED))->toBeFalse()
        ->and(PostStatus::APPROVED->canTransitionTo(PostStatus::PUBLISHED))->toBeFalse()
        ->and(PostStatus::APPROVED->canTransitionTo(PostStatus::CANCELLED))->toBeFalse();
});

test('canTransitionTo from REJECTED allows only DRAFT', function (): void {
    expect(PostStatus::REJECTED->canTransitionTo(PostStatus::DRAFT))->toBeTrue()
        ->and(PostStatus::REJECTED->canTransitionTo(PostStatus::SUBMITTED))->toBeFalse()
        ->and(PostStatus::REJECTED->canTransitionTo(PostStatus::APPROVED))->toBeFalse()
        ->and(PostStatus::REJECTED->canTransitionTo(PostStatus::SCHEDULED))->toBeFalse()
        ->and(PostStatus::REJECTED->canTransitionTo(PostStatus::PUBLISHING))->toBeFalse()
        ->and(PostStatus::REJECTED->canTransitionTo(PostStatus::PUBLISHED))->toBeFalse()
        ->and(PostStatus::REJECTED->canTransitionTo(PostStatus::CANCELLED))->toBeFalse();
});

test('canTransitionTo from SCHEDULED allows PUBLISHING and CANCELLED', function (): void {
    expect(PostStatus::SCHEDULED->canTransitionTo(PostStatus::PUBLISHING))->toBeTrue()
        ->and(PostStatus::SCHEDULED->canTransitionTo(PostStatus::CANCELLED))->toBeTrue()
        ->and(PostStatus::SCHEDULED->canTransitionTo(PostStatus::DRAFT))->toBeFalse()
        ->and(PostStatus::SCHEDULED->canTransitionTo(PostStatus::SUBMITTED))->toBeFalse()
        ->and(PostStatus::SCHEDULED->canTransitionTo(PostStatus::APPROVED))->toBeFalse()
        ->and(PostStatus::SCHEDULED->canTransitionTo(PostStatus::PUBLISHED))->toBeFalse();
});

test('canTransitionTo from PUBLISHING allows PUBLISHED and FAILED', function (): void {
    expect(PostStatus::PUBLISHING->canTransitionTo(PostStatus::PUBLISHED))->toBeTrue()
        ->and(PostStatus::PUBLISHING->canTransitionTo(PostStatus::FAILED))->toBeTrue()
        ->and(PostStatus::PUBLISHING->canTransitionTo(PostStatus::DRAFT))->toBeFalse()
        ->and(PostStatus::PUBLISHING->canTransitionTo(PostStatus::SUBMITTED))->toBeFalse()
        ->and(PostStatus::PUBLISHING->canTransitionTo(PostStatus::APPROVED))->toBeFalse()
        ->and(PostStatus::PUBLISHING->canTransitionTo(PostStatus::CANCELLED))->toBeFalse();
});

test('canTransitionTo from FAILED allows only PUBLISHING', function (): void {
    expect(PostStatus::FAILED->canTransitionTo(PostStatus::PUBLISHING))->toBeTrue()
        ->and(PostStatus::FAILED->canTransitionTo(PostStatus::DRAFT))->toBeFalse()
        ->and(PostStatus::FAILED->canTransitionTo(PostStatus::SUBMITTED))->toBeFalse()
        ->and(PostStatus::FAILED->canTransitionTo(PostStatus::APPROVED))->toBeFalse()
        ->and(PostStatus::FAILED->canTransitionTo(PostStatus::SCHEDULED))->toBeFalse()
        ->and(PostStatus::FAILED->canTransitionTo(PostStatus::PUBLISHED))->toBeFalse()
        ->and(PostStatus::FAILED->canTransitionTo(PostStatus::CANCELLED))->toBeFalse();
});

test('canTransitionTo from PUBLISHED is always false (terminal)', function (): void {
    foreach (PostStatus::cases() as $status) {
        expect(PostStatus::PUBLISHED->canTransitionTo($status))->toBeFalse();
    }
});

test('canTransitionTo from CANCELLED is always false (terminal)', function (): void {
    foreach (PostStatus::cases() as $status) {
        expect(PostStatus::CANCELLED->canTransitionTo($status))->toBeFalse();
    }
});

test('values returns all enum values', function (): void {
    $values = PostStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(9)
        ->and($values)->toContain('draft')
        ->and($values)->toContain('submitted')
        ->and($values)->toContain('approved')
        ->and($values)->toContain('rejected')
        ->and($values)->toContain('scheduled')
        ->and($values)->toContain('publishing')
        ->and($values)->toContain('published')
        ->and($values)->toContain('failed')
        ->and($values)->toContain('cancelled');
});

test('can create enum from string value', function (): void {
    $status = PostStatus::from('draft');

    expect($status)->toBe(PostStatus::DRAFT);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = PostStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});
