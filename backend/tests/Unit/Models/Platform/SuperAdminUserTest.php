<?php

declare(strict_types=1);

/**
 * SuperAdminUser Model Unit Tests
 *
 * Tests for the SuperAdminUser model which represents platform
 * administrators (Bizinso team members) with access to the
 * super admin panel.
 *
 * @see \App\Models\Platform\SuperAdminUser
 */

use App\Enums\Platform\SuperAdminRole;
use App\Enums\Platform\SuperAdminStatus;
use App\Models\Platform\PlatformConfig;
use App\Models\Platform\SuperAdminUser;
use Illuminate\Support\Facades\Hash;

test('can create super admin user', function (): void {
    $user = SuperAdminUser::create([
        'email' => 'admin@example.com',
        'password' => 'password123',
        'name' => 'Test Admin',
        'role' => SuperAdminRole::ADMIN,
        'status' => SuperAdminStatus::ACTIVE,
    ]);

    expect($user)->toBeInstanceOf(SuperAdminUser::class)
        ->and($user->email)->toBe('admin@example.com')
        ->and($user->name)->toBe('Test Admin')
        ->and($user->role)->toBe(SuperAdminRole::ADMIN)
        ->and($user->status)->toBe(SuperAdminStatus::ACTIVE)
        ->and($user->id)->not->toBeNull();
});

test('email must be unique', function (): void {
    SuperAdminUser::factory()->create(['email' => 'unique@example.com']);

    expect(fn () => SuperAdminUser::factory()->create(['email' => 'unique@example.com']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('password is hashed', function (): void {
    $plainPassword = 'mysecretpassword';

    $user = SuperAdminUser::factory()->create([
        'password' => $plainPassword,
    ]);

    // Reload from database to get the actual stored value
    $user->refresh();

    // The stored password should not equal the plain text
    expect($user->password)->not->toBe($plainPassword)
        // Password should be verifiable with Hash
        ->and(Hash::check($plainPassword, $user->password))->toBeTrue();
});

test('role casts to enum', function (): void {
    $user = SuperAdminUser::factory()->create([
        'role' => SuperAdminRole::SUPER_ADMIN,
    ]);

    expect($user->role)->toBeInstanceOf(SuperAdminRole::class)
        ->and($user->role)->toBe(SuperAdminRole::SUPER_ADMIN);
});

test('status casts to enum', function (): void {
    $user = SuperAdminUser::factory()->create([
        'status' => SuperAdminStatus::SUSPENDED,
    ]);

    expect($user->status)->toBeInstanceOf(SuperAdminStatus::class)
        ->and($user->status)->toBe(SuperAdminStatus::SUSPENDED);
});

test('hidden attributes not visible', function (): void {
    $user = SuperAdminUser::factory()->withMfa()->create();

    $array = $user->toArray();

    expect($array)->not->toHaveKey('password')
        ->and($array)->not->toHaveKey('mfa_secret')
        ->and($array)->not->toHaveKey('remember_token');
});

test('has many platform configs', function (): void {
    $admin = SuperAdminUser::factory()->create();

    // Create platform configs updated by this admin
    PlatformConfig::factory()->count(3)->create([
        'updated_by' => $admin->id,
    ]);

    expect($admin->platformConfigs)->toHaveCount(3)
        ->and($admin->platformConfigs->first())->toBeInstanceOf(PlatformConfig::class);
});

test('factory creates valid model', function (): void {
    $user = SuperAdminUser::factory()->create();

    expect($user)->toBeInstanceOf(SuperAdminUser::class)
        ->and($user->id)->not->toBeNull()
        ->and($user->email)->toBeString()
        ->and($user->name)->toBeString()
        ->and($user->role)->toBeInstanceOf(SuperAdminRole::class)
        ->and($user->status)->toBeInstanceOf(SuperAdminStatus::class);
});

test('can login returns true for active', function (): void {
    $user = SuperAdminUser::factory()->active()->create();

    expect($user->canLogin())->toBeTrue();
});

test('can login returns false for suspended', function (): void {
    $user = SuperAdminUser::factory()->suspended()->create();

    expect($user->canLogin())->toBeFalse();
});

test('can login returns false for inactive', function (): void {
    $user = SuperAdminUser::factory()->inactive()->create();

    expect($user->canLogin())->toBeFalse();
});

test('has role returns true when role matches', function (): void {
    $user = SuperAdminUser::factory()->superAdmin()->create();

    expect($user->hasRole(SuperAdminRole::SUPER_ADMIN))->toBeTrue()
        ->and($user->hasRole(SuperAdminRole::ADMIN))->toBeFalse();
});

test('can manage admins returns true for super admin only', function (): void {
    $superAdmin = SuperAdminUser::factory()->superAdmin()->create();
    $admin = SuperAdminUser::factory()->admin()->create();

    expect($superAdmin->canManageAdmins())->toBeTrue()
        ->and($admin->canManageAdmins())->toBeFalse();
});

test('has write access returns correct value by role', function (): void {
    $superAdmin = SuperAdminUser::factory()->superAdmin()->create();
    $admin = SuperAdminUser::factory()->admin()->create();
    $support = SuperAdminUser::factory()->support()->create();
    $viewer = SuperAdminUser::factory()->viewer()->create();

    expect($superAdmin->hasWriteAccess())->toBeTrue()
        ->and($admin->hasWriteAccess())->toBeTrue()
        ->and($support->hasWriteAccess())->toBeTrue()
        ->and($viewer->hasWriteAccess())->toBeFalse();
});
