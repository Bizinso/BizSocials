<?php

declare(strict_types=1);

/**
 * IpWhitelist Model Unit Tests
 *
 * Tests for the IpWhitelist model.
 *
 * @see \App\Models\Audit\IpWhitelist
 */

use App\Models\Audit\IpWhitelist;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create ip whitelist with factory', function (): void {
    $whitelist = IpWhitelist::factory()->create();

    expect($whitelist)->toBeInstanceOf(IpWhitelist::class)
        ->and($whitelist->id)->not->toBeNull()
        ->and($whitelist->ip_address)->toBeString();
});

test('has correct table name', function (): void {
    $whitelist = new IpWhitelist();

    expect($whitelist->getTable())->toBe('ip_whitelist');
});

test('casts attributes correctly', function (): void {
    $whitelist = IpWhitelist::factory()->expiresAt(now()->addDays(7))->create();

    expect($whitelist->is_active)->toBeBool()
        ->and($whitelist->expires_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('tenant relationship works', function (): void {
    $tenant = Tenant::factory()->create();
    $whitelist = IpWhitelist::factory()->forTenant($tenant)->create();

    expect($whitelist->tenant)->toBeInstanceOf(Tenant::class)
        ->and($whitelist->tenant->id)->toBe($tenant->id);
});

test('creator relationship works', function (): void {
    $user = User::factory()->create();
    $whitelist = IpWhitelist::factory()->createdBy($user)->create();

    expect($whitelist->creator)->toBeInstanceOf(User::class)
        ->and($whitelist->creator->id)->toBe($user->id);
});

test('forTenant scope filters by tenant', function (): void {
    $tenant = Tenant::factory()->create();
    IpWhitelist::factory()->forTenant($tenant)->count(2)->create();
    IpWhitelist::factory()->count(1)->create();

    expect(IpWhitelist::forTenant($tenant->id)->count())->toBe(2);
});

test('active scope filters active entries', function (): void {
    IpWhitelist::factory()->active()->count(2)->create();
    IpWhitelist::factory()->inactive()->count(1)->create();

    expect(IpWhitelist::active()->count())->toBe(2);
});

test('expired scope filters expired entries', function (): void {
    IpWhitelist::factory()->expired()->count(2)->create();
    IpWhitelist::factory()->active()->count(1)->create();

    expect(IpWhitelist::expired()->count())->toBe(2);
});

test('byIp scope filters by IP address', function (): void {
    IpWhitelist::factory()->withIp('192.168.1.1')->count(1)->create();
    IpWhitelist::factory()->count(1)->create();

    expect(IpWhitelist::byIp('192.168.1.1')->count())->toBe(1);
});

test('isActive returns correct value', function (): void {
    $active = IpWhitelist::factory()->active()->create();
    $inactive = IpWhitelist::factory()->inactive()->create();
    $expired = IpWhitelist::factory()->expired()->create();

    expect($active->isActive())->toBeTrue()
        ->and($inactive->isActive())->toBeFalse()
        ->and($expired->isActive())->toBeFalse();
});

test('isExpired returns correct value', function (): void {
    $expired = IpWhitelist::factory()->expired()->create();
    $active = IpWhitelist::factory()->active()->create();

    expect($expired->isExpired())->toBeTrue()
        ->and($active->isExpired())->toBeFalse();
});

test('containsIp returns true for matching IP', function (): void {
    $whitelist = IpWhitelist::factory()->withIp('192.168.1.1')->create();

    expect($whitelist->containsIp('192.168.1.1'))->toBeTrue()
        ->and($whitelist->containsIp('192.168.1.2'))->toBeFalse();
});

test('containsIp returns true for IP in CIDR range', function (): void {
    $whitelist = IpWhitelist::factory()->create([
        'ip_address' => '192.168.1.0',
        'cidr_range' => '192.168.1.0/24',
    ]);

    expect($whitelist->containsIp('192.168.1.100'))->toBeTrue()
        ->and($whitelist->containsIp('192.168.2.1'))->toBeFalse();
});

test('deactivate sets is_active to false', function (): void {
    $whitelist = IpWhitelist::factory()->active()->create();

    $whitelist->deactivate();

    expect($whitelist->fresh()->is_active)->toBeFalse();
});

test('activate sets is_active to true', function (): void {
    $whitelist = IpWhitelist::factory()->inactive()->create();

    $whitelist->activate();

    expect($whitelist->fresh()->is_active)->toBeTrue();
});

test('unique constraint on tenant_id and ip_address', function (): void {
    $tenant = Tenant::factory()->create();

    IpWhitelist::factory()->forTenant($tenant)->withIp('192.168.1.1')->create();

    expect(fn () => IpWhitelist::factory()->forTenant($tenant)->withIp('192.168.1.1')->create())
        ->toThrow(\Illuminate\Database\QueryException::class);
});
