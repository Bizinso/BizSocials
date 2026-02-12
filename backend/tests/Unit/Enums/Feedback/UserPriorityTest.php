<?php

declare(strict_types=1);

/**
 * UserPriority Enum Unit Tests
 *
 * Tests for the UserPriority enum which defines user-perceived priority.
 *
 * @see \App\Enums\Feedback\UserPriority
 */

use App\Enums\Feedback\UserPriority;

test('has all expected cases', function (): void {
    $cases = UserPriority::cases();

    expect($cases)->toHaveCount(3)
        ->and(UserPriority::NICE_TO_HAVE->value)->toBe('nice_to_have')
        ->and(UserPriority::IMPORTANT->value)->toBe('important')
        ->and(UserPriority::CRITICAL->value)->toBe('critical');
});

test('label returns correct labels', function (): void {
    expect(UserPriority::NICE_TO_HAVE->label())->toBe('Nice to Have')
        ->and(UserPriority::IMPORTANT->label())->toBe('Important')
        ->and(UserPriority::CRITICAL->label())->toBe('Critical');
});

test('weight returns correct weights', function (): void {
    expect(UserPriority::NICE_TO_HAVE->weight())->toBe(1)
        ->and(UserPriority::IMPORTANT->weight())->toBe(2)
        ->and(UserPriority::CRITICAL->weight())->toBe(3);
});

test('weights are in ascending order', function (): void {
    expect(UserPriority::NICE_TO_HAVE->weight())
        ->toBeLessThan(UserPriority::IMPORTANT->weight())
        ->and(UserPriority::IMPORTANT->weight())
        ->toBeLessThan(UserPriority::CRITICAL->weight());
});

test('values returns all enum values', function (): void {
    $values = UserPriority::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(3)
        ->and($values)->toContain('nice_to_have')
        ->and($values)->toContain('critical');
});

test('can create enum from string value', function (): void {
    $priority = UserPriority::from('critical');

    expect($priority)->toBe(UserPriority::CRITICAL);
});
