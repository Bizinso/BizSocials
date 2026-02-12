<?php

declare(strict_types=1);

/**
 * ActivityCategory Enum Unit Tests
 *
 * Tests for the ActivityCategory enum which categorizes user activities.
 *
 * @see \App\Enums\Analytics\ActivityCategory
 */

use App\Enums\Analytics\ActivityCategory;

test('has expected values', function (): void {
    $values = array_column(ActivityCategory::cases(), 'value');

    expect($values)->toContain('content_creation')
        ->and($values)->toContain('publishing')
        ->and($values)->toContain('engagement')
        ->and($values)->toContain('analytics')
        ->and($values)->toContain('settings')
        ->and($values)->toContain('ai_features')
        ->and($values)->toContain('authentication');
});

test('can be created from string', function (): void {
    $category = ActivityCategory::from('content_creation');

    expect($category)->toBe(ActivityCategory::CONTENT_CREATION);
});

test('tryFrom returns null for invalid value', function (): void {
    $category = ActivityCategory::tryFrom('invalid_category');

    expect($category)->toBeNull();
});

describe('label method', function (): void {
    test('returns human-readable labels', function (): void {
        expect(ActivityCategory::CONTENT_CREATION->label())->toBe('Content Creation')
            ->and(ActivityCategory::PUBLISHING->label())->toBe('Publishing')
            ->and(ActivityCategory::ENGAGEMENT->label())->toBe('Engagement')
            ->and(ActivityCategory::ANALYTICS->label())->toBe('Analytics')
            ->and(ActivityCategory::SETTINGS->label())->toBe('Settings')
            ->and(ActivityCategory::AI_FEATURES->label())->toBe('AI Features')
            ->and(ActivityCategory::AUTHENTICATION->label())->toBe('Authentication');
    });
});

describe('icon method', function (): void {
    test('returns icon names', function (): void {
        expect(ActivityCategory::CONTENT_CREATION->icon())->toBeString()
            ->and(ActivityCategory::PUBLISHING->icon())->toBeString()
            ->and(ActivityCategory::ANALYTICS->icon())->toBeString();
    });
});

describe('color method', function (): void {
    test('returns color codes', function (): void {
        expect(ActivityCategory::CONTENT_CREATION->color())->toBeString()
            ->and(ActivityCategory::PUBLISHING->color())->toBeString()
            ->and(ActivityCategory::AI_FEATURES->color())->toBeString();
    });
});

test('has exactly 7 categories', function (): void {
    expect(ActivityCategory::cases())->toHaveCount(7);
});

test('all cases have unique values', function (): void {
    $values = array_column(ActivityCategory::cases(), 'value');

    expect(count($values))->toBe(count(array_unique($values)));
});
