<?php

declare(strict_types=1);

/**
 * KBDifficultyLevel Enum Unit Tests
 *
 * Tests for the KBDifficultyLevel enum which defines the difficulty
 * level of knowledge base articles.
 *
 * @see \App\Enums\KnowledgeBase\KBDifficultyLevel
 */

use App\Enums\KnowledgeBase\KBDifficultyLevel;

test('has all expected cases', function (): void {
    $cases = KBDifficultyLevel::cases();

    expect($cases)->toHaveCount(3)
        ->and(KBDifficultyLevel::BEGINNER->value)->toBe('beginner')
        ->and(KBDifficultyLevel::INTERMEDIATE->value)->toBe('intermediate')
        ->and(KBDifficultyLevel::ADVANCED->value)->toBe('advanced');
});

test('label returns correct labels', function (): void {
    expect(KBDifficultyLevel::BEGINNER->label())->toBe('Beginner')
        ->and(KBDifficultyLevel::INTERMEDIATE->label())->toBe('Intermediate')
        ->and(KBDifficultyLevel::ADVANCED->label())->toBe('Advanced');
});

test('sortOrder returns correct order values', function (): void {
    expect(KBDifficultyLevel::BEGINNER->sortOrder())->toBe(1)
        ->and(KBDifficultyLevel::INTERMEDIATE->sortOrder())->toBe(2)
        ->and(KBDifficultyLevel::ADVANCED->sortOrder())->toBe(3);
});

test('sortOrder values are sequential', function (): void {
    $levels = [
        KBDifficultyLevel::BEGINNER,
        KBDifficultyLevel::INTERMEDIATE,
        KBDifficultyLevel::ADVANCED,
    ];

    $sortOrders = array_map(fn ($level) => $level->sortOrder(), $levels);

    expect($sortOrders)->toBe([1, 2, 3]);
});

test('values returns all enum values', function (): void {
    $values = KBDifficultyLevel::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(3)
        ->and($values)->toContain('beginner')
        ->and($values)->toContain('intermediate')
        ->and($values)->toContain('advanced');
});

test('can create enum from string value', function (): void {
    $level = KBDifficultyLevel::from('beginner');

    expect($level)->toBe(KBDifficultyLevel::BEGINNER);
});

test('tryFrom returns null for invalid value', function (): void {
    $level = KBDifficultyLevel::tryFrom('invalid');

    expect($level)->toBeNull();
});
