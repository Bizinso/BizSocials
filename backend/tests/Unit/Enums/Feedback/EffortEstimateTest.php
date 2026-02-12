<?php

declare(strict_types=1);

/**
 * EffortEstimate Enum Unit Tests
 *
 * Tests for the EffortEstimate enum which defines effort estimation.
 *
 * @see \App\Enums\Feedback\EffortEstimate
 */

use App\Enums\Feedback\EffortEstimate;

test('has all expected cases', function (): void {
    $cases = EffortEstimate::cases();

    expect($cases)->toHaveCount(5)
        ->and(EffortEstimate::XS->value)->toBe('xs')
        ->and(EffortEstimate::S->value)->toBe('s')
        ->and(EffortEstimate::M->value)->toBe('m')
        ->and(EffortEstimate::L->value)->toBe('l')
        ->and(EffortEstimate::XL->value)->toBe('xl');
});

test('label returns correct labels', function (): void {
    expect(EffortEstimate::XS->label())->toBe('XS')
        ->and(EffortEstimate::S->label())->toBe('S')
        ->and(EffortEstimate::M->label())->toBe('M')
        ->and(EffortEstimate::L->label())->toBe('L')
        ->and(EffortEstimate::XL->label())->toBe('XL');
});

test('description returns time estimates', function (): void {
    expect(EffortEstimate::XS->description())->toContain('2 hours')
        ->and(EffortEstimate::S->description())->toContain('2-8 hours')
        ->and(EffortEstimate::M->description())->toContain('1-3 days')
        ->and(EffortEstimate::L->description())->toContain('1-2 weeks')
        ->and(EffortEstimate::XL->description())->toContain('2+ weeks');
});

test('values returns all enum values', function (): void {
    $values = EffortEstimate::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(5)
        ->and($values)->toContain('xs')
        ->and($values)->toContain('xl');
});

test('can create enum from string value', function (): void {
    $estimate = EffortEstimate::from('m');

    expect($estimate)->toBe(EffortEstimate::M);
});
