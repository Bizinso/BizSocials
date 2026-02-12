<?php

declare(strict_types=1);

/**
 * UserInvitation Model Unit Tests
 *
 * Tests for the UserInvitation model which represents
 * invitations to join a tenant organization.
 *
 * @see \App\Models\User\UserInvitation
 */

use App\Enums\User\InvitationStatus;
use App\Enums\User\TenantRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\User\UserInvitation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;

test('has correct table name', function (): void {
    $invitation = new UserInvitation();

    expect($invitation->getTable())->toBe('user_invitations');
});

test('uses uuid primary key', function (): void {
    $invitation = UserInvitation::factory()->create();

    expect($invitation->id)->not->toBeNull()
        ->and(strlen($invitation->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $invitation = new UserInvitation();
    $fillable = $invitation->getFillable();

    expect($fillable)->toContain('tenant_id')
        ->and($fillable)->toContain('email')
        ->and($fillable)->toContain('role_in_tenant')
        ->and($fillable)->toContain('workspace_memberships')
        ->and($fillable)->toContain('invited_by')
        ->and($fillable)->toContain('token')
        ->and($fillable)->toContain('status')
        ->and($fillable)->toContain('expires_at')
        ->and($fillable)->toContain('accepted_at');
});

test('TOKEN_LENGTH constant is 64', function (): void {
    expect(UserInvitation::TOKEN_LENGTH)->toBe(64);
});

test('EXPIRES_IN_DAYS constant is 7', function (): void {
    expect(UserInvitation::EXPIRES_IN_DAYS)->toBe(7);
});

test('role_in_tenant casts to enum', function (): void {
    $invitation = UserInvitation::factory()->asAdmin()->create();

    expect($invitation->role_in_tenant)->toBeInstanceOf(TenantRole::class)
        ->and($invitation->role_in_tenant)->toBe(TenantRole::ADMIN);
});

test('workspace_memberships casts to array', function (): void {
    $memberships = [
        ['workspace_id' => 'ws-1', 'role' => 'ADMIN'],
        ['workspace_id' => 'ws-2', 'role' => 'EDITOR'],
    ];

    $invitation = UserInvitation::factory()->withWorkspaceMemberships($memberships)->create();

    expect($invitation->workspace_memberships)->toBeArray()
        ->and($invitation->workspace_memberships)->toHaveCount(2);
});

test('status casts to enum', function (): void {
    $invitation = UserInvitation::factory()->pending()->create();

    expect($invitation->status)->toBeInstanceOf(InvitationStatus::class)
        ->and($invitation->status)->toBe(InvitationStatus::PENDING);
});

test('expires_at casts to datetime', function (): void {
    $invitation = UserInvitation::factory()->create();

    expect($invitation->expires_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('accepted_at casts to datetime', function (): void {
    $invitation = UserInvitation::factory()->accepted()->create();

    expect($invitation->accepted_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('tenant relationship returns belongs to', function (): void {
    $invitation = new UserInvitation();

    expect($invitation->tenant())->toBeInstanceOf(BelongsTo::class);
});

test('tenant relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    $invitation = UserInvitation::factory()->forTenant($tenant)->byUser($user)->create();

    expect($invitation->tenant)->toBeInstanceOf(Tenant::class)
        ->and($invitation->tenant->id)->toBe($tenant->id);
});

test('inviter relationship returns belongs to', function (): void {
    $invitation = new UserInvitation();

    expect($invitation->inviter())->toBeInstanceOf(BelongsTo::class);
});

test('inviter relationship works correctly', function (): void {
    $user = User::factory()->create();
    $invitation = UserInvitation::factory()->byUser($user)->create();

    expect($invitation->inviter)->toBeInstanceOf(User::class)
        ->and($invitation->inviter->id)->toBe($user->id);
});

test('scope forTenant filters correctly', function (): void {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $user1 = User::factory()->forTenant($tenant1)->create();
    $user2 = User::factory()->forTenant($tenant2)->create();

    UserInvitation::factory()->count(2)->forTenant($tenant1)->byUser($user1)->create();
    UserInvitation::factory()->count(3)->forTenant($tenant2)->byUser($user2)->create();

    $tenant1Invitations = UserInvitation::forTenant($tenant1->id)->get();

    expect($tenant1Invitations)->toHaveCount(2)
        ->and($tenant1Invitations->every(fn ($i) => $i->tenant_id === $tenant1->id))->toBeTrue();
});

test('scope pending filters PENDING status', function (): void {
    $user = User::factory()->create();
    UserInvitation::factory()->count(2)->byUser($user)->pending()->create();
    UserInvitation::factory()->count(3)->byUser($user)->accepted()->create();

    $pendingInvitations = UserInvitation::pending()->get();

    expect($pendingInvitations)->toHaveCount(2)
        ->and($pendingInvitations->every(fn ($i) => $i->status === InvitationStatus::PENDING))->toBeTrue();
});

test('scope expired filters past expiration date', function (): void {
    $user = User::factory()->create();
    UserInvitation::factory()->count(2)->byUser($user)->pending()->create();
    UserInvitation::factory()->count(3)->byUser($user)->expired()->create();

    $expiredInvitations = UserInvitation::expired()->get();

    expect($expiredInvitations)->toHaveCount(3)
        ->and($expiredInvitations->every(fn ($i) => $i->expires_at->isPast()))->toBeTrue();
});

test('token is auto-generated on create', function (): void {
    $user = User::factory()->create();
    $invitation = UserInvitation::factory()->byUser($user)->create([
        'token' => null,
    ]);

    expect($invitation->token)->not->toBeNull()
        ->and(strlen($invitation->token))->toBe(UserInvitation::TOKEN_LENGTH);
});

test('findByToken retrieves invitation', function (): void {
    $user = User::factory()->create();
    $invitation = UserInvitation::factory()->byUser($user)->create();

    $found = UserInvitation::findByToken($invitation->token);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($invitation->id);
});

test('findByToken returns null for invalid token', function (): void {
    $found = UserInvitation::findByToken('invalid-token');

    expect($found)->toBeNull();
});

test('generateToken returns correct length', function (): void {
    $token = UserInvitation::generateToken();

    expect(strlen($token))->toBe(UserInvitation::TOKEN_LENGTH);
});

test('generateToken returns unique tokens', function (): void {
    $tokens = [];
    for ($i = 0; $i < 100; $i++) {
        $tokens[] = UserInvitation::generateToken();
    }

    expect(count(array_unique($tokens)))->toBe(100);
});

test('expireOldInvitations marks old as expired', function (): void {
    $user = User::factory()->create();
    // Create pending invitations that are past expiration
    UserInvitation::factory()->count(3)->byUser($user)->state([
        'status' => InvitationStatus::PENDING,
        'expires_at' => now()->subDays(1),
    ])->create();

    // Create pending invitations that are not expired
    UserInvitation::factory()->count(2)->byUser($user)->pending()->create();

    $expiredCount = UserInvitation::expireOldInvitations();

    expect($expiredCount)->toBe(3)
        ->and(UserInvitation::where('status', InvitationStatus::EXPIRED)->count())->toBe(3)
        ->and(UserInvitation::where('status', InvitationStatus::PENDING)->count())->toBe(2);
});

test('isPending checks status', function (): void {
    $user = User::factory()->create();
    $pending = UserInvitation::factory()->byUser($user)->pending()->create();
    $accepted = UserInvitation::factory()->byUser($user)->accepted()->create();

    expect($pending->isPending())->toBeTrue()
        ->and($accepted->isPending())->toBeFalse();
});

test('isExpired checks expires_at and status', function (): void {
    $user = User::factory()->create();
    $pending = UserInvitation::factory()->byUser($user)->pending()->create();
    $expired = UserInvitation::factory()->byUser($user)->expired()->create();
    $pastExpiration = UserInvitation::factory()->byUser($user)->state([
        'status' => InvitationStatus::PENDING,
        'expires_at' => now()->subDays(1),
    ])->create();

    expect($pending->isExpired())->toBeFalse()
        ->and($expired->isExpired())->toBeTrue()
        ->and($pastExpiration->isExpired())->toBeTrue();
});

test('canBeAccepted checks pending and not expired', function (): void {
    $user = User::factory()->create();
    $pending = UserInvitation::factory()->byUser($user)->pending()->create();
    $expired = UserInvitation::factory()->byUser($user)->expired()->create();
    $accepted = UserInvitation::factory()->byUser($user)->accepted()->create();
    $pastExpiration = UserInvitation::factory()->byUser($user)->state([
        'status' => InvitationStatus::PENDING,
        'expires_at' => now()->subDays(1),
    ])->create();

    expect($pending->canBeAccepted())->toBeTrue()
        ->and($expired->canBeAccepted())->toBeFalse()
        ->and($accepted->canBeAccepted())->toBeFalse()
        ->and($pastExpiration->canBeAccepted())->toBeFalse();
});

test('accept changes status and sets accepted_at', function (): void {
    $user = User::factory()->create();
    $invitation = UserInvitation::factory()->byUser($user)->pending()->create();

    expect($invitation->accepted_at)->toBeNull();

    $invitation->accept();

    expect($invitation->status)->toBe(InvitationStatus::ACCEPTED)
        ->and($invitation->accepted_at)->not->toBeNull()
        ->and($invitation->accepted_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('revoke changes status', function (): void {
    $user = User::factory()->create();
    $invitation = UserInvitation::factory()->byUser($user)->pending()->create();

    $invitation->revoke();

    expect($invitation->status)->toBe(InvitationStatus::REVOKED);
});

test('markExpired changes status', function (): void {
    $user = User::factory()->create();
    $invitation = UserInvitation::factory()->byUser($user)->pending()->create();

    $invitation->markExpired();

    expect($invitation->status)->toBe(InvitationStatus::EXPIRED);
});

test('getWorkspaceMembership retrieves from JSON', function (): void {
    $memberships = [
        ['workspace_id' => 'ws-1', 'role' => 'ADMIN'],
        ['workspace_id' => 'ws-2', 'role' => 'EDITOR'],
    ];

    $invitation = UserInvitation::factory()->withWorkspaceMemberships($memberships)->create();

    $membership1 = $invitation->getWorkspaceMembership('ws-1');
    $membership2 = $invitation->getWorkspaceMembership('ws-2');
    $membershipNone = $invitation->getWorkspaceMembership('ws-3');

    expect($membership1)->toBeArray()
        ->and($membership1['role'])->toBe('ADMIN')
        ->and($membership2['role'])->toBe('EDITOR')
        ->and($membershipNone)->toBeNull();
});

test('getWorkspaceMembership returns null when no memberships', function (): void {
    $invitation = UserInvitation::factory()->create([
        'workspace_memberships' => null,
    ]);

    expect($invitation->getWorkspaceMembership('ws-1'))->toBeNull();
});

test('factory creates valid model', function (): void {
    $invitation = UserInvitation::factory()->create();

    expect($invitation)->toBeInstanceOf(UserInvitation::class)
        ->and($invitation->id)->not->toBeNull()
        ->and($invitation->email)->toBeString()
        ->and($invitation->token)->toBeString()
        ->and(strlen($invitation->token))->toBe(UserInvitation::TOKEN_LENGTH)
        ->and($invitation->status)->toBeInstanceOf(InvitationStatus::class)
        ->and($invitation->role_in_tenant)->toBeInstanceOf(TenantRole::class)
        ->and($invitation->expires_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('token uniqueness constraint', function (): void {
    $user = User::factory()->create();
    $invitation = UserInvitation::factory()->byUser($user)->create();

    expect(fn () => UserInvitation::factory()->byUser($user)->create(['token' => $invitation->token]))
        ->toThrow(QueryException::class);
});

test('factory accepted state works', function (): void {
    $invitation = UserInvitation::factory()->accepted()->create();

    expect($invitation->status)->toBe(InvitationStatus::ACCEPTED)
        ->and($invitation->accepted_at)->not->toBeNull();
});

test('factory expired state works', function (): void {
    $invitation = UserInvitation::factory()->expired()->create();

    expect($invitation->status)->toBe(InvitationStatus::EXPIRED)
        ->and($invitation->expires_at->isPast())->toBeTrue();
});

test('factory revoked state works', function (): void {
    $invitation = UserInvitation::factory()->revoked()->create();

    expect($invitation->status)->toBe(InvitationStatus::REVOKED);
});
