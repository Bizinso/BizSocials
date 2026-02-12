<?php

declare(strict_types=1);

/**
 * SecurityEvent Model Unit Tests
 *
 * Tests for the SecurityEvent model.
 *
 * @see \App\Models\Audit\SecurityEvent
 */

use App\Enums\Audit\SecurityEventType;
use App\Enums\Audit\SecuritySeverity;
use App\Models\Audit\SecurityEvent;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create security event with factory', function (): void {
    $event = SecurityEvent::factory()->create();

    expect($event)->toBeInstanceOf(SecurityEvent::class)
        ->and($event->id)->not->toBeNull()
        ->and($event->event_type)->toBeInstanceOf(SecurityEventType::class);
});

test('has correct table name', function (): void {
    $event = new SecurityEvent();

    expect($event->getTable())->toBe('security_events');
});

test('casts attributes correctly', function (): void {
    $event = SecurityEvent::factory()->create();

    expect($event->event_type)->toBeInstanceOf(SecurityEventType::class)
        ->and($event->severity)->toBeInstanceOf(SecuritySeverity::class)
        ->and($event->is_resolved)->toBeBool();
});

test('tenant relationship works', function (): void {
    $tenant = Tenant::factory()->create();
    $event = SecurityEvent::factory()->forTenant($tenant)->create();

    expect($event->tenant)->toBeInstanceOf(Tenant::class)
        ->and($event->tenant->id)->toBe($tenant->id);
});

test('user relationship works', function (): void {
    $user = User::factory()->create();
    $event = SecurityEvent::factory()->forUser($user)->create();

    expect($event->user)->toBeInstanceOf(User::class)
        ->and($event->user->id)->toBe($user->id);
});

test('resolver relationship works', function (): void {
    $event = SecurityEvent::factory()->resolved()->create();

    expect($event->resolver)->toBeInstanceOf(SuperAdminUser::class);
});

test('byType scope filters by event type', function (): void {
    SecurityEvent::factory()->loginSuccess()->count(2)->create();
    SecurityEvent::factory()->loginFailure()->count(1)->create();

    expect(SecurityEvent::byType(SecurityEventType::LOGIN_SUCCESS)->count())->toBe(2);
});

test('bySeverity scope filters by severity', function (): void {
    SecurityEvent::factory()->withSeverity(SecuritySeverity::CRITICAL)->count(2)->create();
    SecurityEvent::factory()->withSeverity(SecuritySeverity::INFO)->count(1)->create();

    expect(SecurityEvent::bySeverity(SecuritySeverity::CRITICAL)->count())->toBe(2);
});

test('unresolved scope filters unresolved events', function (): void {
    SecurityEvent::factory()->unresolved()->count(2)->create();
    SecurityEvent::factory()->resolved()->create();

    expect(SecurityEvent::unresolved()->count())->toBe(2);
});

test('critical scope filters critical events', function (): void {
    SecurityEvent::factory()->accountLocked()->count(2)->create();
    SecurityEvent::factory()->loginSuccess()->create();

    expect(SecurityEvent::critical()->count())->toBe(2);
});

test('isResolved returns correct value', function (): void {
    $resolved = SecurityEvent::factory()->resolved()->create();
    $unresolved = SecurityEvent::factory()->unresolved()->create();

    expect($resolved->isResolved())->toBeTrue()
        ->and($unresolved->isResolved())->toBeFalse();
});

test('isCritical returns correct value', function (): void {
    $critical = SecurityEvent::factory()->accountLocked()->create();
    $info = SecurityEvent::factory()->loginSuccess()->create();

    expect($critical->isCritical())->toBeTrue()
        ->and($info->isCritical())->toBeFalse();
});

test('resolve marks event as resolved', function (): void {
    $event = SecurityEvent::factory()->unresolved()->create();
    $admin = SuperAdminUser::factory()->create();

    $event->resolve($admin, 'Issue resolved');

    expect($event->fresh()->is_resolved)->toBeTrue()
        ->and($event->fresh()->resolved_by)->toBe($admin->id)
        ->and($event->fresh()->resolution_notes)->toBe('Issue resolved');
});

test('requiresAlert returns correct value', function (): void {
    $suspicious = SecurityEvent::factory()->suspiciousActivity()->create();
    $loginSuccess = SecurityEvent::factory()->loginSuccess()->create();

    expect($suspicious->requiresAlert())->toBeTrue()
        ->and($loginSuccess->requiresAlert())->toBeFalse();
});
