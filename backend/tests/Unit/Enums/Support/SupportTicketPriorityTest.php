<?php

declare(strict_types=1);

/**
 * SupportTicketPriority Enum Unit Tests
 *
 * Tests for the SupportTicketPriority enum which defines ticket priority.
 *
 * @see \App\Enums\Support\SupportTicketPriority
 */

use App\Enums\Support\SupportTicketPriority;

test('has all expected cases', function (): void {
    $cases = SupportTicketPriority::cases();

    expect($cases)->toHaveCount(4)
        ->and(SupportTicketPriority::LOW->value)->toBe('low')
        ->and(SupportTicketPriority::MEDIUM->value)->toBe('medium')
        ->and(SupportTicketPriority::HIGH->value)->toBe('high')
        ->and(SupportTicketPriority::URGENT->value)->toBe('urgent');
});

test('label returns correct labels', function (): void {
    expect(SupportTicketPriority::LOW->label())->toBe('Low')
        ->and(SupportTicketPriority::MEDIUM->label())->toBe('Medium')
        ->and(SupportTicketPriority::HIGH->label())->toBe('High')
        ->and(SupportTicketPriority::URGENT->label())->toBe('Urgent');
});

test('weight returns correct weights', function (): void {
    expect(SupportTicketPriority::LOW->weight())->toBe(1)
        ->and(SupportTicketPriority::MEDIUM->weight())->toBe(2)
        ->and(SupportTicketPriority::HIGH->weight())->toBe(3)
        ->and(SupportTicketPriority::URGENT->weight())->toBe(4);
});

test('weight is ordered correctly', function (): void {
    expect(SupportTicketPriority::LOW->weight())
        ->toBeLessThan(SupportTicketPriority::MEDIUM->weight())
        ->and(SupportTicketPriority::MEDIUM->weight())
        ->toBeLessThan(SupportTicketPriority::HIGH->weight())
        ->and(SupportTicketPriority::HIGH->weight())
        ->toBeLessThan(SupportTicketPriority::URGENT->weight());
});

test('color returns valid hex colors', function (): void {
    foreach (SupportTicketPriority::cases() as $priority) {
        $color = $priority->color();
        expect($color)->toMatch('/^#[0-9A-Fa-f]{6}$/');
    }
});

test('slaHours returns correct hours', function (): void {
    expect(SupportTicketPriority::LOW->slaHours())->toBe(72)
        ->and(SupportTicketPriority::MEDIUM->slaHours())->toBe(24)
        ->and(SupportTicketPriority::HIGH->slaHours())->toBe(8)
        ->and(SupportTicketPriority::URGENT->slaHours())->toBe(4);
});

test('slaHours decreases with higher priority', function (): void {
    expect(SupportTicketPriority::LOW->slaHours())
        ->toBeGreaterThan(SupportTicketPriority::MEDIUM->slaHours())
        ->and(SupportTicketPriority::MEDIUM->slaHours())
        ->toBeGreaterThan(SupportTicketPriority::HIGH->slaHours())
        ->and(SupportTicketPriority::HIGH->slaHours())
        ->toBeGreaterThan(SupportTicketPriority::URGENT->slaHours());
});

test('values returns all enum values', function (): void {
    $values = SupportTicketPriority::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(4)
        ->and($values)->toContain('low')
        ->and($values)->toContain('urgent');
});
