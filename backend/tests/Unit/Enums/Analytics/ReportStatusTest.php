<?php

declare(strict_types=1);

/**
 * ReportStatus Enum Unit Tests
 *
 * Tests for the ReportStatus enum which defines the lifecycle of analytics reports.
 *
 * @see \App\Enums\Analytics\ReportStatus
 */

use App\Enums\Analytics\ReportStatus;

test('has expected values', function (): void {
    $values = array_column(ReportStatus::cases(), 'value');

    expect($values)->toContain('pending')
        ->and($values)->toContain('processing')
        ->and($values)->toContain('completed')
        ->and($values)->toContain('failed')
        ->and($values)->toContain('expired');
});

test('can be created from string', function (): void {
    $status = ReportStatus::from('pending');

    expect($status)->toBe(ReportStatus::PENDING);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = ReportStatus::tryFrom('invalid_status');

    expect($status)->toBeNull();
});

describe('label method', function (): void {
    test('returns human-readable labels', function (): void {
        expect(ReportStatus::PENDING->label())->toBe('Pending')
            ->and(ReportStatus::PROCESSING->label())->toBe('Processing')
            ->and(ReportStatus::COMPLETED->label())->toBe('Completed')
            ->and(ReportStatus::FAILED->label())->toBe('Failed')
            ->and(ReportStatus::EXPIRED->label())->toBe('Expired');
    });
});

describe('color method', function (): void {
    test('returns appropriate colors', function (): void {
        expect(ReportStatus::PENDING->color())->toBe('gray')
            ->and(ReportStatus::PROCESSING->color())->toBe('blue')
            ->and(ReportStatus::COMPLETED->color())->toBe('green')
            ->and(ReportStatus::FAILED->color())->toBe('red')
            ->and(ReportStatus::EXPIRED->color())->toBe('orange');
    });
});

describe('isTerminal method', function (): void {
    test('returns true for terminal states', function (): void {
        expect(ReportStatus::COMPLETED->isTerminal())->toBeTrue()
            ->and(ReportStatus::FAILED->isTerminal())->toBeTrue()
            ->and(ReportStatus::EXPIRED->isTerminal())->toBeTrue();
    });

    test('returns false for non-terminal states', function (): void {
        expect(ReportStatus::PENDING->isTerminal())->toBeFalse()
            ->and(ReportStatus::PROCESSING->isTerminal())->toBeFalse();
    });
});

describe('isDownloadable method', function (): void {
    test('returns true only for completed status', function (): void {
        expect(ReportStatus::COMPLETED->isDownloadable())->toBeTrue();
    });

    test('returns false for other statuses', function (): void {
        expect(ReportStatus::PENDING->isDownloadable())->toBeFalse()
            ->and(ReportStatus::PROCESSING->isDownloadable())->toBeFalse()
            ->and(ReportStatus::FAILED->isDownloadable())->toBeFalse()
            ->and(ReportStatus::EXPIRED->isDownloadable())->toBeFalse();
    });
});

describe('canRetry method', function (): void {
    test('returns true only for failed status', function (): void {
        expect(ReportStatus::FAILED->canRetry())->toBeTrue();
    });

    test('returns false for other statuses', function (): void {
        expect(ReportStatus::PENDING->canRetry())->toBeFalse()
            ->and(ReportStatus::PROCESSING->canRetry())->toBeFalse()
            ->and(ReportStatus::COMPLETED->canRetry())->toBeFalse()
            ->and(ReportStatus::EXPIRED->canRetry())->toBeFalse();
    });
});

test('has exactly 5 statuses', function (): void {
    expect(ReportStatus::cases())->toHaveCount(5);
});

test('all cases have unique values', function (): void {
    $values = array_column(ReportStatus::cases(), 'value');

    expect(count($values))->toBe(count(array_unique($values)));
});
