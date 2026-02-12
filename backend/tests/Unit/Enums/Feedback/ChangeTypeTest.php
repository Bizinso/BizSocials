<?php

declare(strict_types=1);

/**
 * ChangeType Enum Unit Tests
 *
 * Tests for the ChangeType enum which defines change types in release notes.
 *
 * @see \App\Enums\Feedback\ChangeType
 */

use App\Enums\Feedback\ChangeType;

test('has all expected cases', function (): void {
    $cases = ChangeType::cases();

    expect($cases)->toHaveCount(7)
        ->and(ChangeType::NEW_FEATURE->value)->toBe('new_feature')
        ->and(ChangeType::IMPROVEMENT->value)->toBe('improvement')
        ->and(ChangeType::BUG_FIX->value)->toBe('bug_fix')
        ->and(ChangeType::SECURITY->value)->toBe('security')
        ->and(ChangeType::PERFORMANCE->value)->toBe('performance')
        ->and(ChangeType::DEPRECATION->value)->toBe('deprecation')
        ->and(ChangeType::BREAKING_CHANGE->value)->toBe('breaking_change');
});

test('label returns correct labels', function (): void {
    expect(ChangeType::NEW_FEATURE->label())->toBe('New Feature')
        ->and(ChangeType::IMPROVEMENT->label())->toBe('Improvement')
        ->and(ChangeType::BUG_FIX->label())->toBe('Bug Fix')
        ->and(ChangeType::SECURITY->label())->toBe('Security')
        ->and(ChangeType::PERFORMANCE->label())->toBe('Performance')
        ->and(ChangeType::DEPRECATION->label())->toBe('Deprecation')
        ->and(ChangeType::BREAKING_CHANGE->label())->toBe('Breaking Change');
});

test('icon returns correct icons', function (): void {
    expect(ChangeType::NEW_FEATURE->icon())->toBe('sparkles')
        ->and(ChangeType::BUG_FIX->icon())->toBe('bug')
        ->and(ChangeType::SECURITY->icon())->toBe('shield')
        ->and(ChangeType::PERFORMANCE->icon())->toBe('zap')
        ->and(ChangeType::BREAKING_CHANGE->icon())->toBe('alert-octagon');
});

test('color returns hex color codes', function (): void {
    foreach (ChangeType::cases() as $type) {
        expect($type->color())->toMatch('/^#[0-9A-F]{6}$/i');
    }
});

test('values returns all enum values', function (): void {
    $values = ChangeType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(7)
        ->and($values)->toContain('new_feature')
        ->and($values)->toContain('breaking_change');
});

test('can create enum from string value', function (): void {
    $type = ChangeType::from('bug_fix');

    expect($type)->toBe(ChangeType::BUG_FIX);
});
