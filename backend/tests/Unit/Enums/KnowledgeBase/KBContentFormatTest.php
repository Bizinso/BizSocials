<?php

declare(strict_types=1);

/**
 * KBContentFormat Enum Unit Tests
 *
 * Tests for the KBContentFormat enum which defines the content
 * format of knowledge base articles.
 *
 * @see \App\Enums\KnowledgeBase\KBContentFormat
 */

use App\Enums\KnowledgeBase\KBContentFormat;

test('has all expected cases', function (): void {
    $cases = KBContentFormat::cases();

    expect($cases)->toHaveCount(3)
        ->and(KBContentFormat::MARKDOWN->value)->toBe('markdown')
        ->and(KBContentFormat::HTML->value)->toBe('html')
        ->and(KBContentFormat::RICH_TEXT->value)->toBe('rich_text');
});

test('label returns correct labels', function (): void {
    expect(KBContentFormat::MARKDOWN->label())->toBe('Markdown')
        ->and(KBContentFormat::HTML->label())->toBe('HTML')
        ->and(KBContentFormat::RICH_TEXT->label())->toBe('Rich Text');
});

test('values returns all enum values', function (): void {
    $values = KBContentFormat::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(3)
        ->and($values)->toContain('markdown')
        ->and($values)->toContain('html')
        ->and($values)->toContain('rich_text');
});

test('can create enum from string value', function (): void {
    $format = KBContentFormat::from('markdown');

    expect($format)->toBe(KBContentFormat::MARKDOWN);
});

test('tryFrom returns null for invalid value', function (): void {
    $format = KBContentFormat::tryFrom('invalid');

    expect($format)->toBeNull();
});
