<?php

declare(strict_types=1);

/**
 * SupportTicketType Enum Unit Tests
 *
 * Tests for the SupportTicketType enum which defines ticket types.
 *
 * @see \App\Enums\Support\SupportTicketType
 */

use App\Enums\Support\SupportTicketType;

test('has all expected cases', function (): void {
    $cases = SupportTicketType::cases();

    expect($cases)->toHaveCount(7)
        ->and(SupportTicketType::QUESTION->value)->toBe('question')
        ->and(SupportTicketType::PROBLEM->value)->toBe('problem')
        ->and(SupportTicketType::FEATURE_REQUEST->value)->toBe('feature_request')
        ->and(SupportTicketType::BUG_REPORT->value)->toBe('bug_report')
        ->and(SupportTicketType::BILLING->value)->toBe('billing')
        ->and(SupportTicketType::ACCOUNT->value)->toBe('account')
        ->and(SupportTicketType::OTHER->value)->toBe('other');
});

test('label returns correct labels', function (): void {
    expect(SupportTicketType::QUESTION->label())->toBe('Question')
        ->and(SupportTicketType::PROBLEM->label())->toBe('Problem')
        ->and(SupportTicketType::FEATURE_REQUEST->label())->toBe('Feature Request')
        ->and(SupportTicketType::BUG_REPORT->label())->toBe('Bug Report')
        ->and(SupportTicketType::BILLING->label())->toBe('Billing')
        ->and(SupportTicketType::ACCOUNT->label())->toBe('Account')
        ->and(SupportTicketType::OTHER->label())->toBe('Other');
});

test('icon returns non-empty string for all types', function (): void {
    foreach (SupportTicketType::cases() as $type) {
        expect($type->icon())->toBeString()->not->toBeEmpty();
    }
});

test('icon returns expected values', function (): void {
    expect(SupportTicketType::QUESTION->icon())->toBe('question-mark-circle')
        ->and(SupportTicketType::BUG_REPORT->icon())->toBe('bug-ant')
        ->and(SupportTicketType::BILLING->icon())->toBe('credit-card');
});

test('values returns all enum values', function (): void {
    $values = SupportTicketType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(7)
        ->and($values)->toContain('question')
        ->and($values)->toContain('bug_report');
});
