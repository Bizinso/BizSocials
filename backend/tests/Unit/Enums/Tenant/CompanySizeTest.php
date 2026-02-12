<?php

declare(strict_types=1);

/**
 * CompanySize Enum Unit Tests
 *
 * Tests for the CompanySize enum which defines company size
 * categories for tenant profiles.
 *
 * @see \App\Enums\Tenant\CompanySize
 */

use App\Enums\Tenant\CompanySize;

test('has all expected cases', function (): void {
    $cases = CompanySize::cases();

    expect($cases)->toHaveCount(5)
        ->and(CompanySize::SOLO->value)->toBe('solo')
        ->and(CompanySize::SMALL->value)->toBe('small')
        ->and(CompanySize::MEDIUM->value)->toBe('medium')
        ->and(CompanySize::LARGE->value)->toBe('large')
        ->and(CompanySize::ENTERPRISE->value)->toBe('enterprise');
});

test('label returns correct labels', function (): void {
    expect(CompanySize::SOLO->label())->toBe('Solo')
        ->and(CompanySize::SMALL->label())->toBe('Small')
        ->and(CompanySize::MEDIUM->label())->toBe('Medium')
        ->and(CompanySize::LARGE->label())->toBe('Large')
        ->and(CompanySize::ENTERPRISE->label())->toBe('Enterprise');
});

test('range returns correct employee ranges', function (): void {
    expect(CompanySize::SOLO->range())->toBe('1 person')
        ->and(CompanySize::SMALL->range())->toBe('2-10 employees')
        ->and(CompanySize::MEDIUM->range())->toBe('11-50 employees')
        ->and(CompanySize::LARGE->range())->toBe('51-200 employees')
        ->and(CompanySize::ENTERPRISE->range())->toBe('200+ employees');
});

test('minEmployees returns correct minimum', function (): void {
    expect(CompanySize::SOLO->minEmployees())->toBe(1)
        ->and(CompanySize::SMALL->minEmployees())->toBe(2)
        ->and(CompanySize::MEDIUM->minEmployees())->toBe(11)
        ->and(CompanySize::LARGE->minEmployees())->toBe(51)
        ->and(CompanySize::ENTERPRISE->minEmployees())->toBe(201);
});

test('maxEmployees returns correct maximum', function (): void {
    expect(CompanySize::SOLO->maxEmployees())->toBe(1)
        ->and(CompanySize::SMALL->maxEmployees())->toBe(10)
        ->and(CompanySize::MEDIUM->maxEmployees())->toBe(50)
        ->and(CompanySize::LARGE->maxEmployees())->toBe(200)
        ->and(CompanySize::ENTERPRISE->maxEmployees())->toBeNull();
});

test('enterprise has no upper limit', function (): void {
    expect(CompanySize::ENTERPRISE->maxEmployees())->toBeNull();
});

test('values returns all enum values', function (): void {
    $values = CompanySize::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(5)
        ->and($values)->toContain('solo')
        ->and($values)->toContain('small')
        ->and($values)->toContain('medium')
        ->and($values)->toContain('large')
        ->and($values)->toContain('enterprise');
});

test('can create enum from string value', function (): void {
    $size = CompanySize::from('medium');

    expect($size)->toBe(CompanySize::MEDIUM);
});

test('tryFrom returns null for invalid value', function (): void {
    $size = CompanySize::tryFrom('invalid');

    expect($size)->toBeNull();
});

test('size ranges are contiguous', function (): void {
    // Verify there are no gaps in the employee ranges
    expect(CompanySize::SOLO->maxEmployees())->toBe(CompanySize::SMALL->minEmployees() - 1)
        ->and(CompanySize::SMALL->maxEmployees())->toBe(CompanySize::MEDIUM->minEmployees() - 1)
        ->and(CompanySize::MEDIUM->maxEmployees())->toBe(CompanySize::LARGE->minEmployees() - 1)
        ->and(CompanySize::LARGE->maxEmployees())->toBe(CompanySize::ENTERPRISE->minEmployees() - 1);
});
