<?php

declare(strict_types=1);

/**
 * SecuritySeverity Enum Unit Tests
 *
 * Tests for the SecuritySeverity enum which defines security severity levels.
 *
 * @see \App\Enums\Audit\SecuritySeverity
 */

use App\Enums\Audit\SecuritySeverity;

test('has all expected cases', function (): void {
    $cases = SecuritySeverity::cases();

    expect($cases)->toHaveCount(5)
        ->and(SecuritySeverity::INFO->value)->toBe('info')
        ->and(SecuritySeverity::LOW->value)->toBe('low')
        ->and(SecuritySeverity::MEDIUM->value)->toBe('medium')
        ->and(SecuritySeverity::HIGH->value)->toBe('high')
        ->and(SecuritySeverity::CRITICAL->value)->toBe('critical');
});

test('label returns correct labels', function (): void {
    expect(SecuritySeverity::INFO->label())->toBe('Info')
        ->and(SecuritySeverity::LOW->label())->toBe('Low')
        ->and(SecuritySeverity::MEDIUM->label())->toBe('Medium')
        ->and(SecuritySeverity::HIGH->label())->toBe('High')
        ->and(SecuritySeverity::CRITICAL->label())->toBe('Critical');
});

test('color returns correct colors', function (): void {
    expect(SecuritySeverity::INFO->color())->toBe('blue')
        ->and(SecuritySeverity::LOW->color())->toBe('green')
        ->and(SecuritySeverity::MEDIUM->color())->toBe('yellow')
        ->and(SecuritySeverity::HIGH->color())->toBe('orange')
        ->and(SecuritySeverity::CRITICAL->color())->toBe('red');
});

test('weight returns correct weights', function (): void {
    expect(SecuritySeverity::INFO->weight())->toBe(1)
        ->and(SecuritySeverity::LOW->weight())->toBe(2)
        ->and(SecuritySeverity::MEDIUM->weight())->toBe(3)
        ->and(SecuritySeverity::HIGH->weight())->toBe(4)
        ->and(SecuritySeverity::CRITICAL->weight())->toBe(5);
});

test('weights are in ascending order', function (): void {
    expect(SecuritySeverity::INFO->weight())
        ->toBeLessThan(SecuritySeverity::LOW->weight())
        ->and(SecuritySeverity::LOW->weight())
        ->toBeLessThan(SecuritySeverity::MEDIUM->weight())
        ->and(SecuritySeverity::MEDIUM->weight())
        ->toBeLessThan(SecuritySeverity::HIGH->weight())
        ->and(SecuritySeverity::HIGH->weight())
        ->toBeLessThan(SecuritySeverity::CRITICAL->weight());
});

test('values returns all enum values', function (): void {
    $values = SecuritySeverity::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(5)
        ->and($values)->toContain('info')
        ->and($values)->toContain('critical');
});
