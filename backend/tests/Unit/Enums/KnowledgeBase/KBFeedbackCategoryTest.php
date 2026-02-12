<?php

declare(strict_types=1);

/**
 * KBFeedbackCategory Enum Unit Tests
 *
 * Tests for the KBFeedbackCategory enum which defines the category
 * of feedback given on knowledge base articles.
 *
 * @see \App\Enums\KnowledgeBase\KBFeedbackCategory
 */

use App\Enums\KnowledgeBase\KBFeedbackCategory;

test('has all expected cases', function (): void {
    $cases = KBFeedbackCategory::cases();

    expect($cases)->toHaveCount(6)
        ->and(KBFeedbackCategory::OUTDATED->value)->toBe('outdated')
        ->and(KBFeedbackCategory::INCOMPLETE->value)->toBe('incomplete')
        ->and(KBFeedbackCategory::UNCLEAR->value)->toBe('unclear')
        ->and(KBFeedbackCategory::INCORRECT->value)->toBe('incorrect')
        ->and(KBFeedbackCategory::HELPFUL->value)->toBe('helpful')
        ->and(KBFeedbackCategory::OTHER->value)->toBe('other');
});

test('label returns correct labels', function (): void {
    expect(KBFeedbackCategory::OUTDATED->label())->toBe('Outdated Content')
        ->and(KBFeedbackCategory::INCOMPLETE->label())->toBe('Incomplete Information')
        ->and(KBFeedbackCategory::UNCLEAR->label())->toBe('Unclear/Confusing')
        ->and(KBFeedbackCategory::INCORRECT->label())->toBe('Incorrect Information')
        ->and(KBFeedbackCategory::HELPFUL->label())->toBe('Helpful')
        ->and(KBFeedbackCategory::OTHER->label())->toBe('Other');
});

test('isPositive returns true only for HELPFUL', function (): void {
    expect(KBFeedbackCategory::HELPFUL->isPositive())->toBeTrue()
        ->and(KBFeedbackCategory::OUTDATED->isPositive())->toBeFalse()
        ->and(KBFeedbackCategory::INCOMPLETE->isPositive())->toBeFalse()
        ->and(KBFeedbackCategory::UNCLEAR->isPositive())->toBeFalse()
        ->and(KBFeedbackCategory::INCORRECT->isPositive())->toBeFalse()
        ->and(KBFeedbackCategory::OTHER->isPositive())->toBeFalse();
});

test('isNegative returns true for negative categories', function (): void {
    expect(KBFeedbackCategory::OUTDATED->isNegative())->toBeTrue()
        ->and(KBFeedbackCategory::INCOMPLETE->isNegative())->toBeTrue()
        ->and(KBFeedbackCategory::UNCLEAR->isNegative())->toBeTrue()
        ->and(KBFeedbackCategory::INCORRECT->isNegative())->toBeTrue()
        ->and(KBFeedbackCategory::HELPFUL->isNegative())->toBeFalse()
        ->and(KBFeedbackCategory::OTHER->isNegative())->toBeFalse();
});

test('isPositive and isNegative are mutually exclusive for most categories', function (): void {
    // HELPFUL should be positive only
    expect(KBFeedbackCategory::HELPFUL->isPositive())->toBeTrue()
        ->and(KBFeedbackCategory::HELPFUL->isNegative())->toBeFalse();

    // Negative categories should be negative only
    $negatives = [
        KBFeedbackCategory::OUTDATED,
        KBFeedbackCategory::INCOMPLETE,
        KBFeedbackCategory::UNCLEAR,
        KBFeedbackCategory::INCORRECT,
    ];

    foreach ($negatives as $category) {
        expect($category->isPositive())->toBeFalse()
            ->and($category->isNegative())->toBeTrue();
    }

    // OTHER is neither positive nor negative
    expect(KBFeedbackCategory::OTHER->isPositive())->toBeFalse()
        ->and(KBFeedbackCategory::OTHER->isNegative())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = KBFeedbackCategory::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(6)
        ->and($values)->toContain('outdated')
        ->and($values)->toContain('incomplete')
        ->and($values)->toContain('unclear')
        ->and($values)->toContain('incorrect')
        ->and($values)->toContain('helpful')
        ->and($values)->toContain('other');
});

test('can create enum from string value', function (): void {
    $category = KBFeedbackCategory::from('helpful');

    expect($category)->toBe(KBFeedbackCategory::HELPFUL);
});

test('tryFrom returns null for invalid value', function (): void {
    $category = KBFeedbackCategory::tryFrom('invalid');

    expect($category)->toBeNull();
});
