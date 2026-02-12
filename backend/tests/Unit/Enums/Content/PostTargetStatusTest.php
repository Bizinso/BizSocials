<?php

declare(strict_types=1);

/**
 * PostTargetStatus Enum Unit Tests
 *
 * Tests for the PostTargetStatus enum which defines the publishing
 * status of a post to a specific social account.
 *
 * @see \App\Enums\Content\PostTargetStatus
 */

use App\Enums\Content\PostTargetStatus;

test('has all expected cases', function (): void {
    $cases = PostTargetStatus::cases();

    expect($cases)->toHaveCount(4)
        ->and(PostTargetStatus::PENDING->value)->toBe('pending')
        ->and(PostTargetStatus::PUBLISHING->value)->toBe('publishing')
        ->and(PostTargetStatus::PUBLISHED->value)->toBe('published')
        ->and(PostTargetStatus::FAILED->value)->toBe('failed');
});

test('label returns correct labels', function (): void {
    expect(PostTargetStatus::PENDING->label())->toBe('Pending')
        ->and(PostTargetStatus::PUBLISHING->label())->toBe('Publishing')
        ->and(PostTargetStatus::PUBLISHED->label())->toBe('Published')
        ->and(PostTargetStatus::FAILED->label())->toBe('Failed');
});

test('isPublished returns true only for PUBLISHED', function (): void {
    expect(PostTargetStatus::PUBLISHED->isPublished())->toBeTrue()
        ->and(PostTargetStatus::PENDING->isPublished())->toBeFalse()
        ->and(PostTargetStatus::PUBLISHING->isPublished())->toBeFalse()
        ->and(PostTargetStatus::FAILED->isPublished())->toBeFalse();
});

test('hasFailed returns true only for FAILED', function (): void {
    expect(PostTargetStatus::FAILED->hasFailed())->toBeTrue()
        ->and(PostTargetStatus::PENDING->hasFailed())->toBeFalse()
        ->and(PostTargetStatus::PUBLISHING->hasFailed())->toBeFalse()
        ->and(PostTargetStatus::PUBLISHED->hasFailed())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = PostTargetStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(4)
        ->and($values)->toContain('pending')
        ->and($values)->toContain('publishing')
        ->and($values)->toContain('published')
        ->and($values)->toContain('failed');
});

test('can create enum from string value', function (): void {
    $status = PostTargetStatus::from('pending');

    expect($status)->toBe(PostTargetStatus::PENDING);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = PostTargetStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});
