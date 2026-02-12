<?php

declare(strict_types=1);

/**
 * CannedResponseCategory Enum Unit Tests
 *
 * Tests for the CannedResponseCategory enum which defines canned response categories.
 *
 * @see \App\Enums\Support\CannedResponseCategory
 */

use App\Enums\Support\CannedResponseCategory;

test('has all expected cases', function (): void {
    $cases = CannedResponseCategory::cases();

    expect($cases)->toHaveCount(8)
        ->and(CannedResponseCategory::GREETING->value)->toBe('greeting')
        ->and(CannedResponseCategory::BILLING->value)->toBe('billing')
        ->and(CannedResponseCategory::TECHNICAL->value)->toBe('technical')
        ->and(CannedResponseCategory::ACCOUNT->value)->toBe('account')
        ->and(CannedResponseCategory::FEATURE_REQUEST->value)->toBe('feature_request')
        ->and(CannedResponseCategory::BUG_REPORT->value)->toBe('bug_report')
        ->and(CannedResponseCategory::CLOSING->value)->toBe('closing')
        ->and(CannedResponseCategory::GENERAL->value)->toBe('general');
});

test('label returns correct labels', function (): void {
    expect(CannedResponseCategory::GREETING->label())->toBe('Greeting')
        ->and(CannedResponseCategory::BILLING->label())->toBe('Billing')
        ->and(CannedResponseCategory::TECHNICAL->label())->toBe('Technical')
        ->and(CannedResponseCategory::ACCOUNT->label())->toBe('Account')
        ->and(CannedResponseCategory::FEATURE_REQUEST->label())->toBe('Feature Request')
        ->and(CannedResponseCategory::BUG_REPORT->label())->toBe('Bug Report')
        ->and(CannedResponseCategory::CLOSING->label())->toBe('Closing')
        ->and(CannedResponseCategory::GENERAL->label())->toBe('General');
});

test('values returns all enum values', function (): void {
    $values = CannedResponseCategory::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(8)
        ->and($values)->toContain('greeting')
        ->and($values)->toContain('closing')
        ->and($values)->toContain('general');
});
