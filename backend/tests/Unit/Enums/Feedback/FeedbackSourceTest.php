<?php

declare(strict_types=1);

/**
 * FeedbackSource Enum Unit Tests
 *
 * Tests for the FeedbackSource enum which defines feedback submission sources.
 *
 * @see \App\Enums\Feedback\FeedbackSource
 */

use App\Enums\Feedback\FeedbackSource;

test('has all expected cases', function (): void {
    $cases = FeedbackSource::cases();

    expect($cases)->toHaveCount(5)
        ->and(FeedbackSource::PORTAL->value)->toBe('portal')
        ->and(FeedbackSource::WIDGET->value)->toBe('widget')
        ->and(FeedbackSource::EMAIL->value)->toBe('email')
        ->and(FeedbackSource::SUPPORT_TICKET->value)->toBe('support_ticket')
        ->and(FeedbackSource::INTERNAL->value)->toBe('internal');
});

test('label returns correct labels', function (): void {
    expect(FeedbackSource::PORTAL->label())->toBe('Feedback Portal')
        ->and(FeedbackSource::WIDGET->label())->toBe('In-App Widget')
        ->and(FeedbackSource::EMAIL->label())->toBe('Email')
        ->and(FeedbackSource::SUPPORT_TICKET->label())->toBe('Support Ticket')
        ->and(FeedbackSource::INTERNAL->label())->toBe('Internal');
});

test('values returns all enum values', function (): void {
    $values = FeedbackSource::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(5)
        ->and($values)->toContain('portal')
        ->and($values)->toContain('internal');
});

test('can create enum from string value', function (): void {
    $source = FeedbackSource::from('widget');

    expect($source)->toBe(FeedbackSource::WIDGET);
});
