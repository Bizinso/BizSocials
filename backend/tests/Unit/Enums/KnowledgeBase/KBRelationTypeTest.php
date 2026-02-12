<?php

declare(strict_types=1);

/**
 * KBRelationType Enum Unit Tests
 *
 * Tests for the KBRelationType enum which defines the type of
 * relationship between knowledge base articles.
 *
 * @see \App\Enums\KnowledgeBase\KBRelationType
 */

use App\Enums\KnowledgeBase\KBRelationType;

test('has all expected cases', function (): void {
    $cases = KBRelationType::cases();

    expect($cases)->toHaveCount(3)
        ->and(KBRelationType::RELATED->value)->toBe('related')
        ->and(KBRelationType::PREREQUISITE->value)->toBe('prerequisite')
        ->and(KBRelationType::NEXT_STEP->value)->toBe('next_step');
});

test('label returns correct labels', function (): void {
    expect(KBRelationType::RELATED->label())->toBe('Related Article')
        ->and(KBRelationType::PREREQUISITE->label())->toBe('Prerequisite')
        ->and(KBRelationType::NEXT_STEP->label())->toBe('Next Step');
});

test('inverseLabel returns correct inverse labels', function (): void {
    expect(KBRelationType::RELATED->inverseLabel())->toBe('Related To')
        ->and(KBRelationType::PREREQUISITE->inverseLabel())->toBe('Required By')
        ->and(KBRelationType::NEXT_STEP->inverseLabel())->toBe('Follows From');
});

test('values returns all enum values', function (): void {
    $values = KBRelationType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(3)
        ->and($values)->toContain('related')
        ->and($values)->toContain('prerequisite')
        ->and($values)->toContain('next_step');
});

test('can create enum from string value', function (): void {
    $type = KBRelationType::from('related');

    expect($type)->toBe(KBRelationType::RELATED);
});

test('tryFrom returns null for invalid value', function (): void {
    $type = KBRelationType::tryFrom('invalid');

    expect($type)->toBeNull();
});
