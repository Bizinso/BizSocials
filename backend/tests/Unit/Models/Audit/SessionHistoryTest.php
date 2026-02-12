<?php

declare(strict_types=1);

/**
 * SessionHistory Model Unit Tests
 *
 * Tests for the SessionHistory model.
 *
 * @see \App\Models\Audit\SessionHistory
 */

use App\Enums\Audit\SessionStatus;
use App\Models\Audit\SessionHistory;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create session history with factory', function (): void {
    $session = SessionHistory::factory()->create();

    expect($session)->toBeInstanceOf(SessionHistory::class)
        ->and($session->id)->not->toBeNull()
        ->and($session->session_token)->toBeString();
});

test('has correct table name', function (): void {
    $session = new SessionHistory();

    expect($session->getTable())->toBe('session_history');
});

test('casts attributes correctly', function (): void {
    $session = SessionHistory::factory()->create();

    expect($session->status)->toBeInstanceOf(SessionStatus::class)
        ->and($session->is_current)->toBeBool()
        ->and($session->last_activity_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('user relationship works', function (): void {
    $user = User::factory()->create();
    $session = SessionHistory::factory()->forUser($user)->create();

    expect($session->user)->toBeInstanceOf(User::class)
        ->and($session->user->id)->toBe($user->id);
});

test('tenant relationship works', function (): void {
    $tenant = Tenant::factory()->create();
    $session = SessionHistory::factory()->forTenant($tenant)->create();

    expect($session->tenant)->toBeInstanceOf(Tenant::class)
        ->and($session->tenant->id)->toBe($tenant->id);
});

test('revoker relationship works', function (): void {
    $session = SessionHistory::factory()->revoked()->create();

    expect($session->revoker)->toBeInstanceOf(User::class);
});

test('forUser scope filters by user', function (): void {
    $user = User::factory()->create();
    SessionHistory::factory()->forUser($user)->count(2)->create();
    SessionHistory::factory()->count(1)->create();

    expect(SessionHistory::forUser($user->id)->count())->toBe(2);
});

test('active scope filters active sessions', function (): void {
    SessionHistory::factory()->active()->count(2)->create();
    SessionHistory::factory()->expired()->count(1)->create();

    expect(SessionHistory::active()->count())->toBe(2);
});

test('expired scope filters expired sessions', function (): void {
    SessionHistory::factory()->expired()->count(2)->create();
    SessionHistory::factory()->active()->count(1)->create();

    expect(SessionHistory::expired()->count())->toBe(2);
});

test('revoked scope filters revoked sessions', function (): void {
    SessionHistory::factory()->revoked()->count(2)->create();
    SessionHistory::factory()->active()->count(1)->create();

    expect(SessionHistory::revoked()->count())->toBe(2);
});

test('current scope filters current sessions', function (): void {
    SessionHistory::factory()->current()->count(1)->create();
    SessionHistory::factory()->active()->count(2)->create();

    expect(SessionHistory::current()->count())->toBe(1);
});

test('byDevice scope filters by device type', function (): void {
    SessionHistory::factory()->desktop()->count(2)->create();
    SessionHistory::factory()->mobile()->count(1)->create();

    expect(SessionHistory::byDevice('desktop')->count())->toBe(2);
});

test('isActive returns correct value', function (): void {
    $active = SessionHistory::factory()->active()->create();
    $expired = SessionHistory::factory()->expired()->create();

    expect($active->isActive())->toBeTrue()
        ->and($expired->isActive())->toBeFalse();
});

test('isExpired returns correct value', function (): void {
    $expired = SessionHistory::factory()->expired()->create();
    $active = SessionHistory::factory()->active()->create();

    expect($expired->isExpired())->toBeTrue()
        ->and($active->isExpired())->toBeFalse();
});

test('isCurrent returns correct value', function (): void {
    $current = SessionHistory::factory()->current()->create();
    $notCurrent = SessionHistory::factory()->active()->create();

    expect($current->isCurrent())->toBeTrue()
        ->and($notCurrent->isCurrent())->toBeFalse();
});

test('revoke marks session as revoked', function (): void {
    $session = SessionHistory::factory()->active()->create();
    $revoker = User::factory()->create();

    $session->revoke($revoker, 'Security concern');

    $fresh = $session->fresh();
    expect($fresh->status)->toBe(SessionStatus::REVOKED)
        ->and($fresh->revoked_by)->toBe($revoker->id)
        ->and($fresh->revocation_reason)->toBe('Security concern')
        ->and($fresh->is_current)->toBeFalse();
});

test('updateLastActivity updates last_activity_at', function (): void {
    $session = SessionHistory::factory()->create([
        'last_activity_at' => now()->subHour(),
    ]);
    $oldActivity = $session->last_activity_at;

    $session->updateLastActivity();

    expect($session->fresh()->last_activity_at)->toBeGreaterThan($oldActivity);
});

test('markAsCurrent sets session as current and unsets others', function (): void {
    $user = User::factory()->create();
    $oldCurrent = SessionHistory::factory()->forUser($user)->current()->create();
    $newSession = SessionHistory::factory()->forUser($user)->active()->create();

    $newSession->markAsCurrent();

    expect($newSession->fresh()->is_current)->toBeTrue()
        ->and($oldCurrent->fresh()->is_current)->toBeFalse();
});

test('session_token is unique', function (): void {
    SessionHistory::factory()->create(['session_token' => 'unique_token_123']);

    expect(fn () => SessionHistory::factory()->create(['session_token' => 'unique_token_123']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
