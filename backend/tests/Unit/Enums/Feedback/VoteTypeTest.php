<?php

declare(strict_types=1);

/**
 * VoteType Enum Unit Tests
 *
 * Tests for the VoteType enum which defines vote types.
 *
 * @see \App\Enums\Feedback\VoteType
 */

use App\Enums\Feedback\VoteType;

test('has all expected cases', function (): void {
    $cases = VoteType::cases();

    expect($cases)->toHaveCount(2)
        ->and(VoteType::UPVOTE->value)->toBe('upvote')
        ->and(VoteType::DOWNVOTE->value)->toBe('downvote');
});

test('label returns correct labels', function (): void {
    expect(VoteType::UPVOTE->label())->toBe('Upvote')
        ->and(VoteType::DOWNVOTE->label())->toBe('Downvote');
});

test('value returns correct numeric values', function (): void {
    expect(VoteType::UPVOTE->value())->toBe(1)
        ->and(VoteType::DOWNVOTE->value())->toBe(-1);
});

test('upvote value is positive', function (): void {
    expect(VoteType::UPVOTE->value())->toBeGreaterThan(0);
});

test('downvote value is negative', function (): void {
    expect(VoteType::DOWNVOTE->value())->toBeLessThan(0);
});

test('values returns all enum string values', function (): void {
    $values = VoteType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(2)
        ->and($values)->toContain('upvote')
        ->and($values)->toContain('downvote');
});

test('can create enum from string value', function (): void {
    $vote = VoteType::from('upvote');

    expect($vote)->toBe(VoteType::UPVOTE);
});
