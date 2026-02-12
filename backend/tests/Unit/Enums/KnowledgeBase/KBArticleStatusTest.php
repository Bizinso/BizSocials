<?php

declare(strict_types=1);

/**
 * KBArticleStatus Enum Unit Tests
 *
 * Tests for the KBArticleStatus enum which defines the publication
 * status of knowledge base articles.
 *
 * @see \App\Enums\KnowledgeBase\KBArticleStatus
 */

use App\Enums\KnowledgeBase\KBArticleStatus;

test('has all expected cases', function (): void {
    $cases = KBArticleStatus::cases();

    expect($cases)->toHaveCount(3)
        ->and(KBArticleStatus::DRAFT->value)->toBe('draft')
        ->and(KBArticleStatus::PUBLISHED->value)->toBe('published')
        ->and(KBArticleStatus::ARCHIVED->value)->toBe('archived');
});

test('label returns correct labels', function (): void {
    expect(KBArticleStatus::DRAFT->label())->toBe('Draft')
        ->and(KBArticleStatus::PUBLISHED->label())->toBe('Published')
        ->and(KBArticleStatus::ARCHIVED->label())->toBe('Archived');
});

test('isVisible returns true only for PUBLISHED', function (): void {
    expect(KBArticleStatus::PUBLISHED->isVisible())->toBeTrue()
        ->and(KBArticleStatus::DRAFT->isVisible())->toBeFalse()
        ->and(KBArticleStatus::ARCHIVED->isVisible())->toBeFalse();
});

test('canTransitionTo from DRAFT allows PUBLISHED and ARCHIVED', function (): void {
    expect(KBArticleStatus::DRAFT->canTransitionTo(KBArticleStatus::PUBLISHED))->toBeTrue()
        ->and(KBArticleStatus::DRAFT->canTransitionTo(KBArticleStatus::ARCHIVED))->toBeTrue()
        ->and(KBArticleStatus::DRAFT->canTransitionTo(KBArticleStatus::DRAFT))->toBeFalse();
});

test('canTransitionTo from PUBLISHED allows DRAFT and ARCHIVED', function (): void {
    expect(KBArticleStatus::PUBLISHED->canTransitionTo(KBArticleStatus::DRAFT))->toBeTrue()
        ->and(KBArticleStatus::PUBLISHED->canTransitionTo(KBArticleStatus::ARCHIVED))->toBeTrue()
        ->and(KBArticleStatus::PUBLISHED->canTransitionTo(KBArticleStatus::PUBLISHED))->toBeFalse();
});

test('canTransitionTo from ARCHIVED allows DRAFT and PUBLISHED', function (): void {
    expect(KBArticleStatus::ARCHIVED->canTransitionTo(KBArticleStatus::DRAFT))->toBeTrue()
        ->and(KBArticleStatus::ARCHIVED->canTransitionTo(KBArticleStatus::PUBLISHED))->toBeTrue()
        ->and(KBArticleStatus::ARCHIVED->canTransitionTo(KBArticleStatus::ARCHIVED))->toBeFalse();
});

test('canTransitionTo returns false for same status', function (): void {
    foreach (KBArticleStatus::cases() as $status) {
        expect($status->canTransitionTo($status))->toBeFalse();
    }
});

test('values returns all enum values', function (): void {
    $values = KBArticleStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(3)
        ->and($values)->toContain('draft')
        ->and($values)->toContain('published')
        ->and($values)->toContain('archived');
});

test('can create enum from string value', function (): void {
    $status = KBArticleStatus::from('draft');

    expect($status)->toBe(KBArticleStatus::DRAFT);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = KBArticleStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});
