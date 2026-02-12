<?php

declare(strict_types=1);

/**
 * UserSession Model Unit Tests
 *
 * Tests for the UserSession model which represents
 * active sessions for users.
 *
 * @see \App\Models\User\UserSession
 */

use App\Enums\User\DeviceType;
use App\Models\User;
use App\Models\User\UserSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('has correct table name', function (): void {
    $session = new UserSession();

    expect($session->getTable())->toBe('user_sessions');
});

test('uses uuid primary key', function (): void {
    $session = UserSession::factory()->create();

    expect($session->id)->not->toBeNull()
        ->and(strlen($session->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $session = new UserSession();
    $fillable = $session->getFillable();

    expect($fillable)->toContain('user_id')
        ->and($fillable)->toContain('token_hash')
        ->and($fillable)->toContain('ip_address')
        ->and($fillable)->toContain('user_agent')
        ->and($fillable)->toContain('device_type')
        ->and($fillable)->toContain('location')
        ->and($fillable)->toContain('last_active_at')
        ->and($fillable)->toContain('expires_at')
        ->and($fillable)->toContain('created_at');
});

test('device_type casts to enum', function (): void {
    $session = UserSession::factory()->desktop()->create();

    expect($session->device_type)->toBeInstanceOf(DeviceType::class)
        ->and($session->device_type)->toBe(DeviceType::DESKTOP);
});

test('location casts to array', function (): void {
    $session = UserSession::factory()->create([
        'location' => [
            'country' => 'US',
            'city' => 'New York',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ],
    ]);

    expect($session->location)->toBeArray()
        ->and($session->location['country'])->toBe('US')
        ->and($session->location['city'])->toBe('New York');
});

test('last_active_at casts to datetime', function (): void {
    $session = UserSession::factory()->create();

    expect($session->last_active_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('expires_at casts to datetime', function (): void {
    $session = UserSession::factory()->create();

    expect($session->expires_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('created_at casts to datetime', function (): void {
    $session = UserSession::factory()->create();

    expect($session->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('user relationship returns belongs to', function (): void {
    $session = new UserSession();

    expect($session->user())->toBeInstanceOf(BelongsTo::class);
});

test('user relationship works correctly', function (): void {
    $user = User::factory()->create();
    $session = UserSession::factory()->forUser($user)->create();

    expect($session->user)->toBeInstanceOf(User::class)
        ->and($session->user->id)->toBe($user->id);
});

test('scope forUser filters correctly', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    UserSession::factory()->count(2)->forUser($user1)->create();
    UserSession::factory()->count(3)->forUser($user2)->create();

    $user1Sessions = UserSession::forUser($user1->id)->get();

    expect($user1Sessions)->toHaveCount(2)
        ->and($user1Sessions->every(fn ($s) => $s->user_id === $user1->id))->toBeTrue();
});

test('scope active filters non-expired', function (): void {
    $user = User::factory()->create();
    UserSession::factory()->count(2)->forUser($user)->active()->create();
    UserSession::factory()->count(3)->forUser($user)->expired()->create();

    $activeSessions = UserSession::active()->get();

    expect($activeSessions)->toHaveCount(2)
        ->and($activeSessions->every(fn ($s) => $s->expires_at->isFuture()))->toBeTrue();
});

test('scope expired filters expired', function (): void {
    $user = User::factory()->create();
    UserSession::factory()->count(2)->forUser($user)->active()->create();
    UserSession::factory()->count(3)->forUser($user)->expired()->create();

    $expiredSessions = UserSession::expired()->get();

    expect($expiredSessions)->toHaveCount(3)
        ->and($expiredSessions->every(fn ($s) => $s->expires_at->isPast()))->toBeTrue();
});

test('createForUser creates with hashed token', function (): void {
    $user = User::factory()->create();
    $token = UserSession::generateToken();

    $session = UserSession::createForUser(
        $user->id,
        $token,
        '192.168.1.1',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0'
    );

    expect($session)->toBeInstanceOf(UserSession::class)
        ->and($session->user_id)->toBe($user->id)
        ->and($session->token_hash)->toBe(UserSession::hashToken($token))
        ->and($session->ip_address)->toBe('192.168.1.1')
        ->and($session->device_type)->toBe(DeviceType::DESKTOP);
});

test('createForUser sets expiration correctly', function (): void {
    $user = User::factory()->create();
    $token = UserSession::generateToken();

    $session = UserSession::createForUser($user->id, $token, null, null, 14);

    // Check that expires_at is approximately 14 days in the future
    $daysUntilExpiration = (int) abs($session->expires_at->diffInDays(now()));

    expect($session->expires_at->isFuture())->toBeTrue()
        ->and($daysUntilExpiration)->toBeGreaterThanOrEqual(13)
        ->and($daysUntilExpiration)->toBeLessThanOrEqual(14);
});

test('isExpired checks expires_at', function (): void {
    $activeSession = UserSession::factory()->active()->create();
    $expiredSession = UserSession::factory()->expired()->create();

    expect($activeSession->isExpired())->toBeFalse()
        ->and($expiredSession->isExpired())->toBeTrue();
});

test('isActive is inverse of isExpired', function (): void {
    $activeSession = UserSession::factory()->active()->create();
    $expiredSession = UserSession::factory()->expired()->create();

    expect($activeSession->isActive())->toBeTrue()
        ->and($expiredSession->isActive())->toBeFalse();
});

test('refreshActivity updates last_active_at', function (): void {
    $session = UserSession::factory()->create([
        'last_active_at' => now()->subHours(2),
    ]);

    $oldTime = $session->last_active_at;
    $session->refreshActivity();

    expect($session->last_active_at->isAfter($oldTime))->toBeTrue();
});

test('invalidate deletes session', function (): void {
    $session = UserSession::factory()->create();
    $sessionId = $session->id;

    $session->invalidate();

    expect(UserSession::find($sessionId))->toBeNull();
});

test('hashToken returns consistent hash', function (): void {
    $token = 'test-token-12345';

    $hash1 = UserSession::hashToken($token);
    $hash2 = UserSession::hashToken($token);

    expect($hash1)->toBe($hash2)
        ->and($hash1)->toBe(hash('sha256', $token));
});

test('generateToken returns unique tokens', function (): void {
    $tokens = [];
    for ($i = 0; $i < 100; $i++) {
        $tokens[] = UserSession::generateToken();
    }

    expect(count(array_unique($tokens)))->toBe(100);
});

test('generateToken returns 64 character string', function (): void {
    $token = UserSession::generateToken();

    expect(strlen($token))->toBe(64);
});

test('cleanExpired deletes expired sessions', function (): void {
    $user = User::factory()->create();
    UserSession::factory()->count(2)->forUser($user)->active()->create();
    UserSession::factory()->count(3)->forUser($user)->expired()->create();

    $deleted = UserSession::cleanExpired();

    expect($deleted)->toBe(3)
        ->and(UserSession::count())->toBe(2);
});

test('factory creates valid model', function (): void {
    $session = UserSession::factory()->create();

    expect($session)->toBeInstanceOf(UserSession::class)
        ->and($session->id)->not->toBeNull()
        ->and($session->user_id)->not->toBeNull()
        ->and($session->token_hash)->toBeString()
        ->and($session->device_type)->toBeInstanceOf(DeviceType::class)
        ->and($session->expires_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('factory desktop state works', function (): void {
    $session = UserSession::factory()->desktop()->create();

    expect($session->device_type)->toBe(DeviceType::DESKTOP);
});

test('factory mobile state works', function (): void {
    $session = UserSession::factory()->mobile()->create();

    expect($session->device_type)->toBe(DeviceType::MOBILE);
});

test('factory tablet state works', function (): void {
    $session = UserSession::factory()->tablet()->create();

    expect($session->device_type)->toBe(DeviceType::TABLET);
});

test('factory api state works', function (): void {
    $session = UserSession::factory()->api()->create();

    expect($session->device_type)->toBe(DeviceType::API);
});
