<?php

declare(strict_types=1);

/**
 * KBArticleType Enum Unit Tests
 *
 * Tests for the KBArticleType enum which defines the type
 * of knowledge base articles.
 *
 * @see \App\Enums\KnowledgeBase\KBArticleType
 */

use App\Enums\KnowledgeBase\KBArticleType;

test('has all expected cases', function (): void {
    $cases = KBArticleType::cases();

    expect($cases)->toHaveCount(9)
        ->and(KBArticleType::GETTING_STARTED->value)->toBe('getting_started')
        ->and(KBArticleType::HOW_TO->value)->toBe('how_to')
        ->and(KBArticleType::TUTORIAL->value)->toBe('tutorial')
        ->and(KBArticleType::REFERENCE->value)->toBe('reference')
        ->and(KBArticleType::TROUBLESHOOTING->value)->toBe('troubleshooting')
        ->and(KBArticleType::FAQ->value)->toBe('faq')
        ->and(KBArticleType::BEST_PRACTICE->value)->toBe('best_practice')
        ->and(KBArticleType::RELEASE_NOTE->value)->toBe('release_note')
        ->and(KBArticleType::API_DOCUMENTATION->value)->toBe('api_documentation');
});

test('label returns correct labels', function (): void {
    expect(KBArticleType::GETTING_STARTED->label())->toBe('Getting Started')
        ->and(KBArticleType::HOW_TO->label())->toBe('How-To Guide')
        ->and(KBArticleType::TUTORIAL->label())->toBe('Tutorial')
        ->and(KBArticleType::REFERENCE->label())->toBe('Reference')
        ->and(KBArticleType::TROUBLESHOOTING->label())->toBe('Troubleshooting')
        ->and(KBArticleType::FAQ->label())->toBe('FAQ')
        ->and(KBArticleType::BEST_PRACTICE->label())->toBe('Best Practice')
        ->and(KBArticleType::RELEASE_NOTE->label())->toBe('Release Note')
        ->and(KBArticleType::API_DOCUMENTATION->label())->toBe('API Documentation');
});

test('icon returns correct icons', function (): void {
    expect(KBArticleType::GETTING_STARTED->icon())->toBe('rocket')
        ->and(KBArticleType::HOW_TO->icon())->toBe('clipboard-list')
        ->and(KBArticleType::TUTORIAL->icon())->toBe('academic-cap')
        ->and(KBArticleType::REFERENCE->icon())->toBe('book-open')
        ->and(KBArticleType::TROUBLESHOOTING->icon())->toBe('wrench-screwdriver')
        ->and(KBArticleType::FAQ->icon())->toBe('question-mark-circle')
        ->and(KBArticleType::BEST_PRACTICE->icon())->toBe('star')
        ->and(KBArticleType::RELEASE_NOTE->icon())->toBe('document-text')
        ->and(KBArticleType::API_DOCUMENTATION->icon())->toBe('code-bracket');
});

test('description returns correct descriptions', function (): void {
    expect(KBArticleType::GETTING_STARTED->description())->toContain('Introductory')
        ->and(KBArticleType::HOW_TO->description())->toContain('Step-by-step')
        ->and(KBArticleType::TUTORIAL->description())->toContain('In-depth')
        ->and(KBArticleType::REFERENCE->description())->toContain('Technical')
        ->and(KBArticleType::TROUBLESHOOTING->description())->toContain('Problem-solving')
        ->and(KBArticleType::FAQ->description())->toContain('frequently asked')
        ->and(KBArticleType::BEST_PRACTICE->description())->toContain('Recommended')
        ->and(KBArticleType::RELEASE_NOTE->description())->toContain('features')
        ->and(KBArticleType::API_DOCUMENTATION->description())->toContain('API');
});

test('values returns all enum values', function (): void {
    $values = KBArticleType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(9)
        ->and($values)->toContain('getting_started')
        ->and($values)->toContain('how_to')
        ->and($values)->toContain('tutorial')
        ->and($values)->toContain('reference')
        ->and($values)->toContain('troubleshooting')
        ->and($values)->toContain('faq')
        ->and($values)->toContain('best_practice')
        ->and($values)->toContain('release_note')
        ->and($values)->toContain('api_documentation');
});

test('can create enum from string value', function (): void {
    $type = KBArticleType::from('getting_started');

    expect($type)->toBe(KBArticleType::GETTING_STARTED);
});

test('tryFrom returns null for invalid value', function (): void {
    $type = KBArticleType::tryFrom('invalid');

    expect($type)->toBeNull();
});
