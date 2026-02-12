<?php

declare(strict_types=1);

/**
 * SupportChannel Enum Unit Tests
 *
 * Tests for the SupportChannel enum which defines ticket submission channels.
 *
 * @see \App\Enums\Support\SupportChannel
 */

use App\Enums\Support\SupportChannel;

test('has all expected cases', function (): void {
    $cases = SupportChannel::cases();

    expect($cases)->toHaveCount(5)
        ->and(SupportChannel::WEB_FORM->value)->toBe('web_form')
        ->and(SupportChannel::EMAIL->value)->toBe('email')
        ->and(SupportChannel::IN_APP->value)->toBe('in_app')
        ->and(SupportChannel::CHAT->value)->toBe('chat')
        ->and(SupportChannel::API->value)->toBe('api');
});

test('label returns correct labels', function (): void {
    expect(SupportChannel::WEB_FORM->label())->toBe('Web Form')
        ->and(SupportChannel::EMAIL->label())->toBe('Email')
        ->and(SupportChannel::IN_APP->label())->toBe('In-App')
        ->and(SupportChannel::CHAT->label())->toBe('Chat')
        ->and(SupportChannel::API->label())->toBe('API');
});

test('values returns all enum values', function (): void {
    $values = SupportChannel::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(5)
        ->and($values)->toContain('web_form')
        ->and($values)->toContain('email')
        ->and($values)->toContain('api');
});
