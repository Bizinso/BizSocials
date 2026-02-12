<?php

declare(strict_types=1);

/**
 * KBVisibility Enum Unit Tests
 *
 * Tests for the KBVisibility enum which defines the visibility
 * level of knowledge base articles and categories.
 *
 * @see \App\Enums\KnowledgeBase\KBVisibility
 */

use App\Enums\KnowledgeBase\KBVisibility;

test('has all expected cases', function (): void {
    $cases = KBVisibility::cases();

    expect($cases)->toHaveCount(3)
        ->and(KBVisibility::ALL->value)->toBe('all')
        ->and(KBVisibility::AUTHENTICATED->value)->toBe('authenticated')
        ->and(KBVisibility::SPECIFIC_PLANS->value)->toBe('specific_plans');
});

test('label returns correct labels', function (): void {
    expect(KBVisibility::ALL->label())->toBe('Public')
        ->and(KBVisibility::AUTHENTICATED->label())->toBe('Authenticated Users Only')
        ->and(KBVisibility::SPECIFIC_PLANS->label())->toBe('Specific Plans Only');
});

test('isPublic returns true only for ALL', function (): void {
    expect(KBVisibility::ALL->isPublic())->toBeTrue()
        ->and(KBVisibility::AUTHENTICATED->isPublic())->toBeFalse()
        ->and(KBVisibility::SPECIFIC_PLANS->isPublic())->toBeFalse();
});

test('requiresAuth returns correct values', function (): void {
    expect(KBVisibility::ALL->requiresAuth())->toBeFalse()
        ->and(KBVisibility::AUTHENTICATED->requiresAuth())->toBeTrue()
        ->and(KBVisibility::SPECIFIC_PLANS->requiresAuth())->toBeTrue();
});

test('isPublic and requiresAuth are mutually exclusive', function (): void {
    foreach (KBVisibility::cases() as $visibility) {
        expect($visibility->isPublic())->not->toBe($visibility->requiresAuth());
    }
});

test('values returns all enum values', function (): void {
    $values = KBVisibility::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(3)
        ->and($values)->toContain('all')
        ->and($values)->toContain('authenticated')
        ->and($values)->toContain('specific_plans');
});

test('can create enum from string value', function (): void {
    $visibility = KBVisibility::from('all');

    expect($visibility)->toBe(KBVisibility::ALL);
});

test('tryFrom returns null for invalid value', function (): void {
    $visibility = KBVisibility::tryFrom('invalid');

    expect($visibility)->toBeNull();
});
