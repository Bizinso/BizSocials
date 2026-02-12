<?php

declare(strict_types=1);

/**
 * ApprovalDecisionType Enum Unit Tests
 *
 * Tests for the ApprovalDecisionType enum which defines the type
 * of approval decision made on a post.
 *
 * @see \App\Enums\Content\ApprovalDecisionType
 */

use App\Enums\Content\ApprovalDecisionType;

test('has all expected cases', function (): void {
    $cases = ApprovalDecisionType::cases();

    expect($cases)->toHaveCount(2)
        ->and(ApprovalDecisionType::APPROVED->value)->toBe('approved')
        ->and(ApprovalDecisionType::REJECTED->value)->toBe('rejected');
});

test('label returns correct labels', function (): void {
    expect(ApprovalDecisionType::APPROVED->label())->toBe('Approved')
        ->and(ApprovalDecisionType::REJECTED->label())->toBe('Rejected');
});

test('values returns all enum values', function (): void {
    $values = ApprovalDecisionType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(2)
        ->and($values)->toContain('approved')
        ->and($values)->toContain('rejected');
});

test('can create enum from string value', function (): void {
    $type = ApprovalDecisionType::from('approved');

    expect($type)->toBe(ApprovalDecisionType::APPROVED);
});

test('tryFrom returns null for invalid value', function (): void {
    $type = ApprovalDecisionType::tryFrom('invalid');

    expect($type)->toBeNull();
});
