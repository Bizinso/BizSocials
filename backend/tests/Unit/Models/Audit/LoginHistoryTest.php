<?php

declare(strict_types=1);

/**
 * LoginHistory Model Unit Tests
 *
 * Tests for the LoginHistory model.
 *
 * @see \App\Models\Audit\LoginHistory
 */

use App\Models\Audit\LoginHistory;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create login history with factory', function (): void {
    $history = LoginHistory::factory()->create();

    expect($history)->toBeInstanceOf(LoginHistory::class)
        ->and($history->id)->not->toBeNull()
        ->and($history->successful)->toBeBool();
});

test('has correct table name', function (): void {
    $history = new LoginHistory();

    expect($history->getTable())->toBe('login_history');
});

test('casts attributes correctly', function (): void {
    $history = LoginHistory::factory()->loggedOut()->create();

    expect($history->successful)->toBeBool()
        ->and($history->logged_out_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('user relationship works', function (): void {
    $user = User::factory()->create();
    $history = LoginHistory::factory()->forUser($user)->create();

    expect($history->user)->toBeInstanceOf(User::class)
        ->and($history->user->id)->toBe($user->id);
});

test('tenant relationship works', function (): void {
    $tenant = Tenant::factory()->create();
    $history = LoginHistory::factory()->forTenant($tenant)->create();

    expect($history->tenant)->toBeInstanceOf(Tenant::class)
        ->and($history->tenant->id)->toBe($tenant->id);
});

test('forUser scope filters by user', function (): void {
    $user = User::factory()->create();
    LoginHistory::factory()->forUser($user)->count(2)->create();
    LoginHistory::factory()->count(1)->create();

    expect(LoginHistory::forUser($user->id)->count())->toBe(2);
});

test('successful scope filters successful logins', function (): void {
    LoginHistory::factory()->successful()->count(2)->create();
    LoginHistory::factory()->failed()->count(1)->create();

    expect(LoginHistory::successful()->count())->toBe(2);
});

test('failed scope filters failed logins', function (): void {
    LoginHistory::factory()->failed()->count(2)->create();
    LoginHistory::factory()->successful()->count(1)->create();

    expect(LoginHistory::failed()->count())->toBe(2);
});

test('fromIp scope filters by IP address', function (): void {
    LoginHistory::factory()->fromIp('192.168.1.1')->count(2)->create();
    LoginHistory::factory()->create();

    expect(LoginHistory::fromIp('192.168.1.1')->count())->toBe(2);
});

test('byDevice scope filters by device type', function (): void {
    LoginHistory::factory()->desktop()->count(2)->create();
    LoginHistory::factory()->mobile()->count(1)->create();

    expect(LoginHistory::byDevice('desktop')->count())->toBe(2);
});

test('isSuccessful returns correct value', function (): void {
    $successful = LoginHistory::factory()->successful()->create();
    $failed = LoginHistory::factory()->failed()->create();

    expect($successful->isSuccessful())->toBeTrue()
        ->and($failed->isSuccessful())->toBeFalse();
});

test('isFailed returns correct value', function (): void {
    $failed = LoginHistory::factory()->failed()->create();
    $successful = LoginHistory::factory()->successful()->create();

    expect($failed->isFailed())->toBeTrue()
        ->and($successful->isFailed())->toBeFalse();
});

test('getDeviceInfo returns device information', function (): void {
    $history = LoginHistory::factory()->desktop()->create();

    $deviceInfo = $history->getDeviceInfo();

    expect($deviceInfo)->toBeArray()
        ->and($deviceInfo)->toHaveKeys(['device_type', 'browser', 'os'])
        ->and($deviceInfo['device_type'])->toBe('desktop');
});

test('getLocationInfo returns location information', function (): void {
    $history = LoginHistory::factory()->create([
        'country_code' => 'US',
        'city' => 'New York',
        'ip_address' => '192.168.1.1',
    ]);

    $locationInfo = $history->getLocationInfo();

    expect($locationInfo)->toBeArray()
        ->and($locationInfo)->toHaveKeys(['country_code', 'city', 'ip_address'])
        ->and($locationInfo['country_code'])->toBe('US');
});

test('logout marks session as logged out', function (): void {
    $history = LoginHistory::factory()->create(['logged_out_at' => null]);

    $history->logout();

    expect($history->fresh()->logged_out_at)->not->toBeNull();
});
