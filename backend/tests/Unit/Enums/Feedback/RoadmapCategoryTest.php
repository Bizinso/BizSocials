<?php

declare(strict_types=1);

/**
 * RoadmapCategory Enum Unit Tests
 *
 * Tests for the RoadmapCategory enum which defines roadmap categories.
 *
 * @see \App\Enums\Feedback\RoadmapCategory
 */

use App\Enums\Feedback\RoadmapCategory;

test('has all expected cases', function (): void {
    $cases = RoadmapCategory::cases();

    expect($cases)->toHaveCount(11)
        ->and(RoadmapCategory::PUBLISHING->value)->toBe('publishing')
        ->and(RoadmapCategory::SCHEDULING->value)->toBe('scheduling')
        ->and(RoadmapCategory::ANALYTICS->value)->toBe('analytics')
        ->and(RoadmapCategory::INBOX->value)->toBe('inbox')
        ->and(RoadmapCategory::TEAM_COLLABORATION->value)->toBe('team_collaboration')
        ->and(RoadmapCategory::INTEGRATIONS->value)->toBe('integrations')
        ->and(RoadmapCategory::MOBILE_APP->value)->toBe('mobile_app')
        ->and(RoadmapCategory::API->value)->toBe('api')
        ->and(RoadmapCategory::PLATFORM->value)->toBe('platform')
        ->and(RoadmapCategory::SECURITY->value)->toBe('security')
        ->and(RoadmapCategory::PERFORMANCE->value)->toBe('performance');
});

test('label returns correct labels', function (): void {
    expect(RoadmapCategory::PUBLISHING->label())->toBe('Publishing')
        ->and(RoadmapCategory::TEAM_COLLABORATION->label())->toBe('Team Collaboration')
        ->and(RoadmapCategory::SECURITY->label())->toBe('Security')
        ->and(RoadmapCategory::PERFORMANCE->label())->toBe('Performance');
});

test('color returns hex color codes', function (): void {
    foreach (RoadmapCategory::cases() as $category) {
        expect($category->color())->toMatch('/^#[0-9A-F]{6}$/i');
    }
});

test('values returns all enum values', function (): void {
    $values = RoadmapCategory::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(11)
        ->and($values)->toContain('publishing')
        ->and($values)->toContain('performance');
});

test('can create enum from string value', function (): void {
    $category = RoadmapCategory::from('security');

    expect($category)->toBe(RoadmapCategory::SECURITY);
});
