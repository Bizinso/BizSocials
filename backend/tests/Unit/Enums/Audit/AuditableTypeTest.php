<?php

declare(strict_types=1);

/**
 * AuditableType Enum Unit Tests
 *
 * Tests for the AuditableType enum which defines auditable resource types.
 *
 * @see \App\Enums\Audit\AuditableType
 */

use App\Enums\Audit\AuditableType;

test('has all expected cases', function (): void {
    $cases = AuditableType::cases();

    expect($cases)->toHaveCount(13)
        ->and(AuditableType::USER->value)->toBe('user')
        ->and(AuditableType::TENANT->value)->toBe('tenant')
        ->and(AuditableType::WORKSPACE->value)->toBe('workspace')
        ->and(AuditableType::SOCIAL_ACCOUNT->value)->toBe('social_account')
        ->and(AuditableType::POST->value)->toBe('post')
        ->and(AuditableType::SUBSCRIPTION->value)->toBe('subscription')
        ->and(AuditableType::INVOICE->value)->toBe('invoice')
        ->and(AuditableType::SUPPORT_TICKET->value)->toBe('support_ticket')
        ->and(AuditableType::API_KEY->value)->toBe('api_key')
        ->and(AuditableType::SETTINGS->value)->toBe('settings')
        ->and(AuditableType::TEAM->value)->toBe('team')
        ->and(AuditableType::PLATFORM_INTEGRATION->value)->toBe('platform_integration')
        ->and(AuditableType::OTHER->value)->toBe('other');
});

test('label returns correct labels', function (): void {
    expect(AuditableType::USER->label())->toBe('User')
        ->and(AuditableType::TENANT->label())->toBe('Tenant')
        ->and(AuditableType::SOCIAL_ACCOUNT->label())->toBe('Social Account')
        ->and(AuditableType::SUPPORT_TICKET->label())->toBe('Support Ticket')
        ->and(AuditableType::API_KEY->label())->toBe('API Key');
});

test('modelClass returns correct model classes', function (): void {
    expect(AuditableType::USER->modelClass())->toBe(\App\Models\User::class)
        ->and(AuditableType::TENANT->modelClass())->toBe(\App\Models\Tenant\Tenant::class)
        ->and(AuditableType::WORKSPACE->modelClass())->toBe(\App\Models\Workspace\Workspace::class)
        ->and(AuditableType::POST->modelClass())->toBe(\App\Models\Content\Post::class);
});

test('modelClass returns null for types without model', function (): void {
    expect(AuditableType::API_KEY->modelClass())->toBeNull()
        ->and(AuditableType::SETTINGS->modelClass())->toBeNull()
        ->and(AuditableType::OTHER->modelClass())->toBeNull();
});

test('values returns all enum values', function (): void {
    $values = AuditableType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(13)
        ->and($values)->toContain('user')
        ->and($values)->toContain('tenant')
        ->and($values)->toContain('platform_integration')
        ->and($values)->toContain('other');
});
