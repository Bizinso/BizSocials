<?php

declare(strict_types=1);

/**
 * DataRequestType Enum Unit Tests
 *
 * Tests for the DataRequestType enum which defines GDPR request types.
 *
 * @see \App\Enums\Audit\DataRequestType
 */

use App\Enums\Audit\DataRequestType;

test('has all expected cases', function (): void {
    $cases = DataRequestType::cases();

    expect($cases)->toHaveCount(4)
        ->and(DataRequestType::EXPORT->value)->toBe('export')
        ->and(DataRequestType::DELETION->value)->toBe('deletion')
        ->and(DataRequestType::RECTIFICATION->value)->toBe('rectification')
        ->and(DataRequestType::ACCESS->value)->toBe('access');
});

test('label returns correct labels', function (): void {
    expect(DataRequestType::EXPORT->label())->toBe('Data Export')
        ->and(DataRequestType::DELETION->label())->toBe('Data Deletion')
        ->and(DataRequestType::RECTIFICATION->label())->toBe('Data Rectification')
        ->and(DataRequestType::ACCESS->label())->toBe('Data Access');
});

test('gdprArticle returns correct GDPR article references', function (): void {
    expect(DataRequestType::EXPORT->gdprArticle())->toBe('Article 20')
        ->and(DataRequestType::DELETION->gdprArticle())->toBe('Article 17')
        ->and(DataRequestType::RECTIFICATION->gdprArticle())->toBe('Article 16')
        ->and(DataRequestType::ACCESS->gdprArticle())->toBe('Article 15');
});

test('values returns all enum values', function (): void {
    $values = DataRequestType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(4)
        ->and($values)->toContain('export')
        ->and($values)->toContain('deletion')
        ->and($values)->toContain('rectification')
        ->and($values)->toContain('access');
});
