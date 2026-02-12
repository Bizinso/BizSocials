<?php

declare(strict_types=1);

/**
 * PeriodType Enum Unit Tests
 *
 * Tests for the PeriodType enum which defines aggregation periods for analytics.
 *
 * @see \App\Enums\Analytics\PeriodType
 */

use App\Enums\Analytics\PeriodType;

test('has expected values', function (): void {
    $values = array_column(PeriodType::cases(), 'value');

    expect($values)->toContain('daily')
        ->and($values)->toContain('weekly')
        ->and($values)->toContain('monthly');
});

test('can be created from string', function (): void {
    $period = PeriodType::from('daily');

    expect($period)->toBe(PeriodType::DAILY);
});

test('tryFrom returns null for invalid value', function (): void {
    $period = PeriodType::tryFrom('yearly');

    expect($period)->toBeNull();
});

describe('label method', function (): void {
    test('returns human-readable labels', function (): void {
        expect(PeriodType::DAILY->label())->toBe('Daily')
            ->and(PeriodType::WEEKLY->label())->toBe('Weekly')
            ->and(PeriodType::MONTHLY->label())->toBe('Monthly');
    });
});

describe('days method', function (): void {
    test('returns correct number of days for each period', function (): void {
        expect(PeriodType::DAILY->days())->toBe(1)
            ->and(PeriodType::WEEKLY->days())->toBe(7)
            ->and(PeriodType::MONTHLY->days())->toBe(30);
    });
});

describe('dateFormat method', function (): void {
    test('returns appropriate date format strings', function (): void {
        expect(PeriodType::DAILY->dateFormat())->toBe('Y-m-d')
            ->and(PeriodType::WEEKLY->dateFormat())->toBeString()
            ->and(PeriodType::MONTHLY->dateFormat())->toBe('Y-m');
    });
});

test('has exactly 3 period types', function (): void {
    expect(PeriodType::cases())->toHaveCount(3);
});

test('all cases have unique values', function (): void {
    $values = array_column(PeriodType::cases(), 'value');

    expect(count($values))->toBe(count(array_unique($values)));
});
