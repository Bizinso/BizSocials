<?php

declare(strict_types=1);

/**
 * FeedbackCategory Enum Unit Tests
 *
 * Tests for the FeedbackCategory enum which defines product categories.
 *
 * @see \App\Enums\Feedback\FeedbackCategory
 */

use App\Enums\Feedback\FeedbackCategory;

test('has all expected cases', function (): void {
    $cases = FeedbackCategory::cases();

    expect($cases)->toHaveCount(11)
        ->and(FeedbackCategory::PUBLISHING->value)->toBe('publishing')
        ->and(FeedbackCategory::SCHEDULING->value)->toBe('scheduling')
        ->and(FeedbackCategory::ANALYTICS->value)->toBe('analytics')
        ->and(FeedbackCategory::INBOX->value)->toBe('inbox')
        ->and(FeedbackCategory::TEAM_COLLABORATION->value)->toBe('team_collaboration')
        ->and(FeedbackCategory::INTEGRATIONS->value)->toBe('integrations')
        ->and(FeedbackCategory::MOBILE_APP->value)->toBe('mobile_app')
        ->and(FeedbackCategory::API->value)->toBe('api')
        ->and(FeedbackCategory::BILLING->value)->toBe('billing')
        ->and(FeedbackCategory::ONBOARDING->value)->toBe('onboarding')
        ->and(FeedbackCategory::GENERAL->value)->toBe('general');
});

test('label returns correct labels', function (): void {
    expect(FeedbackCategory::PUBLISHING->label())->toBe('Publishing')
        ->and(FeedbackCategory::TEAM_COLLABORATION->label())->toBe('Team Collaboration')
        ->and(FeedbackCategory::MOBILE_APP->label())->toBe('Mobile App')
        ->and(FeedbackCategory::API->label())->toBe('API');
});

test('values returns all enum values', function (): void {
    $values = FeedbackCategory::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(11)
        ->and($values)->toContain('publishing')
        ->and($values)->toContain('analytics');
});

test('can create enum from string value', function (): void {
    $category = FeedbackCategory::from('publishing');

    expect($category)->toBe(FeedbackCategory::PUBLISHING);
});

test('tryFrom returns null for invalid value', function (): void {
    $category = FeedbackCategory::tryFrom('invalid');

    expect($category)->toBeNull();
});
