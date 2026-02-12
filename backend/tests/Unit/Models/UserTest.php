<?php

declare(strict_types=1);

/**
 * User Model Unit Tests
 *
 * Tests for the User model which represents users
 * within a tenant organization.
 *
 * @see \App\Models\User
 */

use App\Enums\User\TenantRole;
use App\Enums\User\UserStatus;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\User\UserInvitation;
use App\Models\User\UserSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;

test('has correct table name', function (): void {
    $user = new User();

    expect($user->getTable())->toBe('users');
});

test('uses uuid primary key', function (): void {
    $user = User::factory()->create();

    expect($user->id)->not->toBeNull()
        ->and(strlen($user->id))->toBe(36);
});

test('uses soft deletes', function (): void {
    $user = new User();

    expect(in_array(SoftDeletes::class, class_uses_recursive($user), true))->toBeTrue();
});

test('has correct fillable attributes', function (): void {
    $user = new User();
    $fillable = $user->getFillable();

    expect($fillable)->toContain('tenant_id')
        ->and($fillable)->toContain('email')
        ->and($fillable)->toContain('password')
        ->and($fillable)->toContain('name')
        ->and($fillable)->toContain('avatar_url')
        ->and($fillable)->toContain('phone')
        ->and($fillable)->toContain('timezone')
        ->and($fillable)->toContain('language')
        ->and($fillable)->toContain('status')
        ->and($fillable)->toContain('role_in_tenant')
        ->and($fillable)->toContain('email_verified_at')
        ->and($fillable)->toContain('last_login_at')
        ->and($fillable)->toContain('last_active_at')
        ->and($fillable)->toContain('mfa_enabled')
        ->and($fillable)->toContain('mfa_secret')
        ->and($fillable)->toContain('settings');
});

test('has correct hidden attributes', function (): void {
    $user = new User();
    $hidden = $user->getHidden();

    expect($hidden)->toContain('password')
        ->and($hidden)->toContain('mfa_secret')
        ->and($hidden)->toContain('remember_token');
});

test('status casts to enum', function (): void {
    $user = User::factory()->active()->create();

    expect($user->status)->toBeInstanceOf(UserStatus::class)
        ->and($user->status)->toBe(UserStatus::ACTIVE);
});

test('role_in_tenant casts to enum', function (): void {
    $user = User::factory()->owner()->create();

    expect($user->role_in_tenant)->toBeInstanceOf(TenantRole::class)
        ->and($user->role_in_tenant)->toBe(TenantRole::OWNER);
});

test('email_verified_at casts to datetime', function (): void {
    $user = User::factory()->verified()->create();

    expect($user->email_verified_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('last_login_at casts to datetime', function (): void {
    $user = User::factory()->create([
        'last_login_at' => now(),
    ]);

    expect($user->last_login_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('last_active_at casts to datetime', function (): void {
    $user = User::factory()->create([
        'last_active_at' => now(),
    ]);

    expect($user->last_active_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('mfa_enabled casts to boolean', function (): void {
    $user = User::factory()->withMfa()->create();

    expect($user->mfa_enabled)->toBeBool()
        ->and($user->mfa_enabled)->toBeTrue();
});

test('settings casts to array', function (): void {
    $settings = [
        'notifications' => ['email_on_mention' => true],
        'ui' => ['theme' => 'dark'],
    ];

    $user = User::factory()->withSettings($settings)->create();

    expect($user->settings)->toBeArray()
        ->and($user->settings['ui']['theme'])->toBe('dark');
});

test('tenant relationship returns belongs to', function (): void {
    $user = new User();

    expect($user->tenant())->toBeInstanceOf(BelongsTo::class);
});

test('tenant relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();

    expect($user->tenant)->toBeInstanceOf(Tenant::class)
        ->and($user->tenant->id)->toBe($tenant->id);
});

test('sessions relationship returns has many', function (): void {
    $user = new User();

    expect($user->sessions())->toBeInstanceOf(HasMany::class);
});

test('sessions relationship works correctly', function (): void {
    $user = User::factory()->create();
    UserSession::factory()->count(3)->forUser($user)->create();

    expect($user->sessions)->toHaveCount(3)
        ->and($user->sessions->first())->toBeInstanceOf(UserSession::class);
});

test('sentInvitations relationship returns has many', function (): void {
    $user = new User();

    expect($user->sentInvitations())->toBeInstanceOf(HasMany::class);
});

test('sentInvitations relationship works correctly', function (): void {
    $user = User::factory()->create();
    UserInvitation::factory()->count(2)->byUser($user)->create();

    expect($user->sentInvitations)->toHaveCount(2)
        ->and($user->sentInvitations->first())->toBeInstanceOf(UserInvitation::class);
});

test('scope active filters correctly', function (): void {
    $tenant = Tenant::factory()->create();
    User::factory()->count(3)->forTenant($tenant)->active()->create();
    User::factory()->count(2)->forTenant($tenant)->suspended()->create();

    $activeUsers = User::active()->get();

    expect($activeUsers)->toHaveCount(3)
        ->and($activeUsers->every(fn ($u) => $u->status === UserStatus::ACTIVE))->toBeTrue();
});

test('scope forTenant filters by tenant_id', function (): void {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    User::factory()->count(2)->forTenant($tenant1)->create();
    User::factory()->count(3)->forTenant($tenant2)->create();

    $tenant1Users = User::forTenant($tenant1->id)->get();

    expect($tenant1Users)->toHaveCount(2)
        ->and($tenant1Users->every(fn ($u) => $u->tenant_id === $tenant1->id))->toBeTrue();
});

test('scope withRole filters by role', function (): void {
    $tenant = Tenant::factory()->create();
    User::factory()->forTenant($tenant)->owner()->create();
    User::factory()->count(2)->forTenant($tenant)->admin()->create();
    User::factory()->count(3)->forTenant($tenant)->member()->create();

    $admins = User::withRole(TenantRole::ADMIN)->get();

    expect($admins)->toHaveCount(2)
        ->and($admins->every(fn ($u) => $u->role_in_tenant === TenantRole::ADMIN))->toBeTrue();
});

test('isActive returns true only for active status', function (): void {
    $active = User::factory()->active()->create();
    $pending = User::factory()->pending()->create();
    $suspended = User::factory()->suspended()->create();

    expect($active->isActive())->toBeTrue()
        ->and($pending->isActive())->toBeFalse()
        ->and($suspended->isActive())->toBeFalse();
});

test('canLogin returns true only for active status', function (): void {
    $active = User::factory()->active()->create();
    $pending = User::factory()->pending()->create();
    $suspended = User::factory()->suspended()->create();
    $deactivated = User::factory()->deactivated()->create();

    expect($active->canLogin())->toBeTrue()
        ->and($pending->canLogin())->toBeFalse()
        ->and($suspended->canLogin())->toBeFalse()
        ->and($deactivated->canLogin())->toBeFalse();
});

test('isOwner checks role', function (): void {
    $owner = User::factory()->owner()->create();
    $admin = User::factory()->admin()->create();
    $member = User::factory()->member()->create();

    expect($owner->isOwner())->toBeTrue()
        ->and($admin->isOwner())->toBeFalse()
        ->and($member->isOwner())->toBeFalse();
});

test('isAdmin checks role', function (): void {
    $owner = User::factory()->owner()->create();
    $admin = User::factory()->admin()->create();
    $member = User::factory()->member()->create();

    expect($owner->isAdmin())->toBeTrue()
        ->and($admin->isAdmin())->toBeTrue()
        ->and($member->isAdmin())->toBeFalse();
});

test('hasVerifiedEmail checks email_verified_at', function (): void {
    $verified = User::factory()->verified()->create();
    $unverified = User::factory()->unverified()->create();

    expect($verified->hasVerifiedEmail())->toBeTrue()
        ->and($unverified->hasVerifiedEmail())->toBeFalse();
});

test('markEmailAsVerified sets timestamp', function (): void {
    $user = User::factory()->unverified()->create();

    expect($user->email_verified_at)->toBeNull();

    $user->markEmailAsVerified();

    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->email_verified_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('recordLogin updates last_login_at', function (): void {
    $user = User::factory()->create([
        'last_login_at' => null,
        'last_active_at' => null,
    ]);

    $user->recordLogin();

    expect($user->last_login_at)->not->toBeNull()
        ->and($user->last_active_at)->not->toBeNull();
});

test('updateLastActive updates last_active_at', function (): void {
    $user = User::factory()->create([
        'last_active_at' => now()->subHours(2),
    ]);

    $oldTime = $user->last_active_at;
    $user->updateLastActive();

    expect($user->last_active_at)->not->toBe($oldTime)
        ->and($user->last_active_at->isAfter($oldTime))->toBeTrue();
});

test('getTimezone returns user timezone when set', function (): void {
    $user = User::factory()->create([
        'timezone' => 'America/New_York',
    ]);

    expect($user->getTimezone())->toBe('America/New_York');
});

test('getTimezone returns tenant timezone when user timezone is null', function (): void {
    $tenant = Tenant::factory()->create([
        'settings' => ['timezone' => 'Asia/Kolkata'],
    ]);
    $user = User::factory()->forTenant($tenant)->create([
        'timezone' => null,
    ]);

    expect($user->getTimezone())->toBe('Asia/Kolkata');
});

test('getTimezone returns UTC as default', function (): void {
    $tenant = Tenant::factory()->create([
        'settings' => [],
    ]);
    $user = User::factory()->forTenant($tenant)->create([
        'timezone' => null,
    ]);

    expect($user->getTimezone())->toBe('UTC');
});

test('getSetting retrieves from settings JSON', function (): void {
    $user = User::factory()->create([
        'settings' => [
            'notifications' => [
                'email_on_mention' => true,
                'email_digest' => 'daily',
            ],
        ],
    ]);

    expect($user->getSetting('notifications.email_on_mention'))->toBeTrue()
        ->and($user->getSetting('notifications.email_digest'))->toBe('daily')
        ->and($user->getSetting('nonexistent', 'default'))->toBe('default');
});

test('setSetting updates settings JSON', function (): void {
    $user = User::factory()->create([
        'settings' => ['ui' => ['theme' => 'light']],
    ]);

    $user->setSetting('ui.theme', 'dark');
    $user->setSetting('notifications.push', true);

    $user->refresh();

    expect($user->settings['ui']['theme'])->toBe('dark')
        ->and($user->settings['notifications']['push'])->toBeTrue();
});

test('activate changes status', function (): void {
    $user = User::factory()->pending()->create();

    $user->activate();

    expect($user->status)->toBe(UserStatus::ACTIVE);
});

test('suspend changes status', function (): void {
    $user = User::factory()->active()->create();

    $user->suspend();

    expect($user->status)->toBe(UserStatus::SUSPENDED);
});

test('deactivate changes status', function (): void {
    $user = User::factory()->active()->create();

    $user->deactivate();

    expect($user->status)->toBe(UserStatus::DEACTIVATED);
});

test('factory creates valid model', function (): void {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->id)->not->toBeNull()
        ->and($user->email)->toBeString()
        ->and($user->name)->toBeString()
        ->and($user->status)->toBeInstanceOf(UserStatus::class)
        ->and($user->role_in_tenant)->toBeInstanceOf(TenantRole::class)
        ->and($user->settings)->toBeArray();
});

test('email unique within tenant constraint', function (): void {
    $tenant = Tenant::factory()->create();
    User::factory()->forTenant($tenant)->create(['email' => 'test@example.com']);

    expect(fn () => User::factory()->forTenant($tenant)->create(['email' => 'test@example.com']))
        ->toThrow(QueryException::class);
});

test('same email allowed in different tenants', function (): void {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $user1 = User::factory()->forTenant($tenant1)->create(['email' => 'test@example.com']);
    $user2 = User::factory()->forTenant($tenant2)->create(['email' => 'test@example.com']);

    expect($user1->id)->not->toBe($user2->id)
        ->and($user1->email)->toBe($user2->email);
});

test('password is hashed', function (): void {
    $user = User::factory()->create([
        'password' => 'plaintext',
    ]);

    expect($user->password)->not->toBe('plaintext')
        ->and(Hash::check('plaintext', $user->password))->toBeTrue();
});

test('soft delete works correctly', function (): void {
    $user = User::factory()->create();
    $userId = $user->id;

    $user->delete();

    expect(User::find($userId))->toBeNull()
        ->and(User::withTrashed()->find($userId))->not->toBeNull()
        ->and(User::withTrashed()->find($userId)->deleted_at)->not->toBeNull();
});
