<?php

declare(strict_types=1);

/**
 * FeedbackType Enum Unit Tests
 *
 * Tests for the FeedbackType enum which defines the type of feedback.
 *
 * @see \App\Enums\Feedback\FeedbackType
 */

use App\Enums\Feedback\FeedbackType;

test('has all expected cases', function (): void {
    $cases = FeedbackType::cases();

    expect($cases)->toHaveCount(8)
        ->and(FeedbackType::FEATURE_REQUEST->value)->toBe('feature_request')
        ->and(FeedbackType::IMPROVEMENT->value)->toBe('improvement')
        ->and(FeedbackType::BUG_REPORT->value)->toBe('bug_report')
        ->and(FeedbackType::INTEGRATION_REQUEST->value)->toBe('integration_request')
        ->and(FeedbackType::UX_FEEDBACK->value)->toBe('ux_feedback')
        ->and(FeedbackType::DOCUMENTATION->value)->toBe('documentation')
        ->and(FeedbackType::PRICING_FEEDBACK->value)->toBe('pricing_feedback')
        ->and(FeedbackType::OTHER->value)->toBe('other');
});

test('label returns correct labels', function (): void {
    expect(FeedbackType::FEATURE_REQUEST->label())->toBe('Feature Request')
        ->and(FeedbackType::IMPROVEMENT->label())->toBe('Improvement')
        ->and(FeedbackType::BUG_REPORT->label())->toBe('Bug Report')
        ->and(FeedbackType::INTEGRATION_REQUEST->label())->toBe('Integration Request')
        ->and(FeedbackType::UX_FEEDBACK->label())->toBe('UX Feedback')
        ->and(FeedbackType::DOCUMENTATION->label())->toBe('Documentation')
        ->and(FeedbackType::PRICING_FEEDBACK->label())->toBe('Pricing Feedback')
        ->and(FeedbackType::OTHER->label())->toBe('Other');
});

test('icon returns correct icons', function (): void {
    expect(FeedbackType::FEATURE_REQUEST->icon())->toBe('lightbulb')
        ->and(FeedbackType::BUG_REPORT->icon())->toBe('bug')
        ->and(FeedbackType::INTEGRATION_REQUEST->icon())->toBe('plug');
});

test('color returns hex color codes', function (): void {
    foreach (FeedbackType::cases() as $type) {
        expect($type->color())->toMatch('/^#[0-9A-F]{6}$/i');
    }
});

test('values returns all enum values', function (): void {
    $values = FeedbackType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(8)
        ->and($values)->toContain('feature_request')
        ->and($values)->toContain('bug_report');
});

test('can create enum from string value', function (): void {
    $type = FeedbackType::from('feature_request');

    expect($type)->toBe(FeedbackType::FEATURE_REQUEST);
});

test('tryFrom returns null for invalid value', function (): void {
    $type = FeedbackType::tryFrom('invalid');

    expect($type)->toBeNull();
});
