<?php

declare(strict_types=1);

/**
 * ReleaseType Enum Unit Tests
 *
 * Tests for the ReleaseType enum which defines release types.
 *
 * @see \App\Enums\Feedback\ReleaseType
 */

use App\Enums\Feedback\ReleaseType;

test('has all expected cases', function (): void {
    $cases = ReleaseType::cases();

    expect($cases)->toHaveCount(6)
        ->and(ReleaseType::MAJOR->value)->toBe('major')
        ->and(ReleaseType::MINOR->value)->toBe('minor')
        ->and(ReleaseType::PATCH->value)->toBe('patch')
        ->and(ReleaseType::HOTFIX->value)->toBe('hotfix')
        ->and(ReleaseType::BETA->value)->toBe('beta')
        ->and(ReleaseType::ALPHA->value)->toBe('alpha');
});

test('label returns correct labels', function (): void {
    expect(ReleaseType::MAJOR->label())->toBe('Major Release')
        ->and(ReleaseType::MINOR->label())->toBe('Minor Release')
        ->and(ReleaseType::PATCH->label())->toBe('Patch Release')
        ->and(ReleaseType::HOTFIX->label())->toBe('Hotfix')
        ->and(ReleaseType::BETA->label())->toBe('Beta Release')
        ->and(ReleaseType::ALPHA->label())->toBe('Alpha Release');
});

test('badge returns correct badge text', function (): void {
    expect(ReleaseType::MAJOR->badge())->toBe('Major')
        ->and(ReleaseType::MINOR->badge())->toBe('Minor')
        ->and(ReleaseType::PATCH->badge())->toBe('Patch')
        ->and(ReleaseType::HOTFIX->badge())->toBe('Hotfix')
        ->and(ReleaseType::BETA->badge())->toBe('Beta')
        ->and(ReleaseType::ALPHA->badge())->toBe('Alpha');
});

test('values returns all enum values', function (): void {
    $values = ReleaseType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(6)
        ->and($values)->toContain('major')
        ->and($values)->toContain('alpha');
});

test('can create enum from string value', function (): void {
    $type = ReleaseType::from('major');

    expect($type)->toBe(ReleaseType::MAJOR);
});
