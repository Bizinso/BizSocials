<?php

declare(strict_types=1);

/**
 * AdminPriority Enum Unit Tests
 *
 * Tests for the AdminPriority enum which defines admin-assigned priority.
 *
 * @see \App\Enums\Feedback\AdminPriority
 */

use App\Enums\Feedback\AdminPriority;

test('has all expected cases', function (): void {
    $cases = AdminPriority::cases();

    expect($cases)->toHaveCount(4)
        ->and(AdminPriority::LOW->value)->toBe('low')
        ->and(AdminPriority::MEDIUM->value)->toBe('medium')
        ->and(AdminPriority::HIGH->value)->toBe('high')
        ->and(AdminPriority::CRITICAL->value)->toBe('critical');
});

test('label returns correct labels', function (): void {
    expect(AdminPriority::LOW->label())->toBe('Low')
        ->and(AdminPriority::MEDIUM->label())->toBe('Medium')
        ->and(AdminPriority::HIGH->label())->toBe('High')
        ->and(AdminPriority::CRITICAL->label())->toBe('Critical');
});

test('weight returns correct weights', function (): void {
    expect(AdminPriority::LOW->weight())->toBe(1)
        ->and(AdminPriority::MEDIUM->weight())->toBe(2)
        ->and(AdminPriority::HIGH->weight())->toBe(3)
        ->and(AdminPriority::CRITICAL->weight())->toBe(4);
});

test('weights are in ascending order', function (): void {
    expect(AdminPriority::LOW->weight())
        ->toBeLessThan(AdminPriority::MEDIUM->weight())
        ->and(AdminPriority::MEDIUM->weight())
        ->toBeLessThan(AdminPriority::HIGH->weight())
        ->and(AdminPriority::HIGH->weight())
        ->toBeLessThan(AdminPriority::CRITICAL->weight());
});

test('values returns all enum values', function (): void {
    $values = AdminPriority::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(4)
        ->and($values)->toContain('low')
        ->and($values)->toContain('critical');
});

test('can create enum from string value', function (): void {
    $priority = AdminPriority::from('high');

    expect($priority)->toBe(AdminPriority::HIGH);
});
