<?php

declare(strict_types=1);

/**
 * ReportType Enum Unit Tests
 *
 * Tests for the ReportType enum which defines types of analytics reports.
 *
 * @see \App\Enums\Analytics\ReportType
 */

use App\Enums\Analytics\ReportType;

test('has expected values', function (): void {
    $values = array_column(ReportType::cases(), 'value');

    expect($values)->toContain('performance')
        ->and($values)->toContain('engagement')
        ->and($values)->toContain('growth')
        ->and($values)->toContain('content')
        ->and($values)->toContain('audience')
        ->and($values)->toContain('custom');
});

test('can be created from string', function (): void {
    $type = ReportType::from('performance');

    expect($type)->toBe(ReportType::PERFORMANCE);
});

test('tryFrom returns null for invalid value', function (): void {
    $type = ReportType::tryFrom('invalid_type');

    expect($type)->toBeNull();
});

describe('label method', function (): void {
    test('returns human-readable labels', function (): void {
        expect(ReportType::PERFORMANCE->label())->toBe('Performance Report')
            ->and(ReportType::ENGAGEMENT->label())->toBe('Engagement Report')
            ->and(ReportType::GROWTH->label())->toBe('Growth Report')
            ->and(ReportType::CONTENT->label())->toBe('Content Report')
            ->and(ReportType::AUDIENCE->label())->toBe('Audience Report')
            ->and(ReportType::CUSTOM->label())->toBe('Custom Report');
    });
});

describe('description method', function (): void {
    test('returns descriptions for each type', function (): void {
        expect(ReportType::PERFORMANCE->description())->toBeString()
            ->and(ReportType::PERFORMANCE->description())->not->toBeEmpty()
            ->and(ReportType::ENGAGEMENT->description())->toBeString()
            ->and(ReportType::GROWTH->description())->toBeString();
    });
});

describe('defaultMetrics method', function (): void {
    test('returns array of default metrics for performance', function (): void {
        $metrics = ReportType::PERFORMANCE->defaultMetrics();

        expect($metrics)->toBeArray()
            ->and($metrics)->toContain('impressions')
            ->and($metrics)->toContain('reach')
            ->and($metrics)->toContain('engagements');
    });

    test('returns array of default metrics for engagement', function (): void {
        $metrics = ReportType::ENGAGEMENT->defaultMetrics();

        expect($metrics)->toBeArray()
            ->and($metrics)->toContain('likes')
            ->and($metrics)->toContain('comments')
            ->and($metrics)->toContain('shares');
    });

    test('returns array of default metrics for growth', function (): void {
        $metrics = ReportType::GROWTH->defaultMetrics();

        expect($metrics)->toBeArray()
            ->and($metrics)->toContain('followers_change')
            ->and($metrics)->toContain('posts_count');
    });

    test('returns empty array for custom reports', function (): void {
        $metrics = ReportType::CUSTOM->defaultMetrics();

        expect($metrics)->toBeArray();
    });
});

test('has exactly 6 report types', function (): void {
    expect(ReportType::cases())->toHaveCount(6);
});

test('all cases have unique values', function (): void {
    $values = array_column(ReportType::cases(), 'value');

    expect(count($values))->toBe(count(array_unique($values)));
});
