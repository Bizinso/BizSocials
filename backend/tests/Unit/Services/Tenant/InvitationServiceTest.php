<?php

declare(strict_types=1);

use App\Data\Tenant\InviteUserData;
use App\Enums\User\InvitationStatus;
use App\Enums\User\TenantRole;
use App\Enums\User\UserStatus;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\User\UserInvitation;
use App\Models\Workspace\Workspace;
use App\Services\Tenant\InvitationService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = new InvitationService();
    $this->tenant = Tenant::factory()->active()->create();
    $this->inviter = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::ADMIN,
    ]);
});

describe('invite', function () {
    it('creates an invitation', function () {
        $data = new InviteUserData(
            email: 'newuser@example.com',
            role: TenantRole::MEMBER,
        );

        $invitation = $this->service->invite($this->tenant, $data, $this->inviter);

        expect($invitation->email)->toBe('newuser@example.com');
        expect($invitation->tenant_id)->toBe($this->tenant->id);
        expect($invitation->role_in_tenant)->toBe(TenantRole::MEMBER);
        expect($invitation->status)->toBe(InvitationStatus::PENDING);
        expect($invitation->token)->not->toBeEmpty();
    });

    it('creates invitation with workspace memberships', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $data = new InviteUserData(
            email: 'newuser@example.com',
            role: TenantRole::MEMBER,
            workspace_ids: [$workspace->id],
        );

        $invitation = $this->service->invite($this->tenant, $data, $this->inviter);

        expect($invitation->workspace_memberships)->toHaveCount(1);
        expect($invitation->workspace_memberships[0]['workspace_id'])->toBe($workspace->id);
    });

    it('throws exception for existing member', function () {
        $existingUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $data = new InviteUserData(
            email: $existingUser->email,
        );

        expect(fn () => $this->service->invite($this->tenant, $data, $this->inviter))
            ->toThrow(ValidationException::class);
    });

    it('throws exception for duplicate pending invitation', function () {
        UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'duplicate@example.com',
            'invited_by' => $this->inviter->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $data = new InviteUserData(
            email: 'duplicate@example.com',
        );

        expect(fn () => $this->service->invite($this->tenant, $data, $this->inviter))
            ->toThrow(ValidationException::class);
    });

    it('throws exception for invalid workspace', function () {
        $data = new InviteUserData(
            email: 'newuser@example.com',
            workspace_ids: ['00000000-0000-0000-0000-000000000000'],
        );

        expect(fn () => $this->service->invite($this->tenant, $data, $this->inviter))
            ->toThrow(ValidationException::class);
    });
});

describe('listPending', function () {
    it('returns only pending invitations', function () {
        UserInvitation::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'invited_by' => $this->inviter->id,
            'status' => InvitationStatus::PENDING,
        ]);
        UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'invited_by' => $this->inviter->id,
            'status' => InvitationStatus::ACCEPTED,
        ]);

        $result = $this->service->listPending($this->tenant);

        expect($result)->toHaveCount(3);
    });
});

describe('resend', function () {
    it('updates token and expiration', function () {
        // Create with an expiration date in the past (to verify it gets extended)
        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'invited_by' => $this->inviter->id,
            'status' => InvitationStatus::PENDING,
            'expires_at' => now()->addDays(1), // Old expiration, about to expire
        ]);

        $oldToken = $invitation->token;
        $oldExpiration = $invitation->expires_at;

        $this->service->resend($invitation);

        $invitation->refresh();
        expect($invitation->token)->not->toBe($oldToken);
        // New expiration should be 7 days from now, which is greater than 1 day from now
        expect($invitation->expires_at->gt($oldExpiration))->toBeTrue();
    });

    it('throws exception for non-pending invitation', function () {
        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'invited_by' => $this->inviter->id,
            'status' => InvitationStatus::ACCEPTED,
        ]);

        expect(fn () => $this->service->resend($invitation))
            ->toThrow(ValidationException::class);
    });
});

describe('cancel', function () {
    it('revokes the invitation', function () {
        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'invited_by' => $this->inviter->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $this->service->cancel($invitation);

        $invitation->refresh();
        expect($invitation->status)->toBe(InvitationStatus::REVOKED);
    });

    it('throws exception for non-pending invitation', function () {
        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'invited_by' => $this->inviter->id,
            'status' => InvitationStatus::ACCEPTED,
        ]);

        expect(fn () => $this->service->cancel($invitation))
            ->toThrow(ValidationException::class);
    });
});

describe('accept', function () {
    it('accepts invitation and updates user in same tenant', function () {
        // Create a user already in this tenant (e.g., pending user being re-invited)
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'invited@example.com',
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'invited@example.com',
            'invited_by' => $this->inviter->id,
            'role_in_tenant' => TenantRole::ADMIN, // Upgrade to admin
            'status' => InvitationStatus::PENDING,
        ]);

        $this->service->accept($invitation->token, $user);

        $user->refresh();
        expect($user->tenant_id)->toBe($this->tenant->id);
        expect($user->role_in_tenant)->toBe(TenantRole::ADMIN);
        expect($user->status)->toBe(UserStatus::ACTIVE);

        $invitation->refresh();
        expect($invitation->status)->toBe(InvitationStatus::ACCEPTED);
    });

    it('throws exception for user from different tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'email' => 'invited@example.com',
        ]);

        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'invited@example.com',
            'invited_by' => $this->inviter->id,
            'status' => InvitationStatus::PENDING,
        ]);

        expect(fn () => $this->service->accept($invitation->token, $user))
            ->toThrow(ValidationException::class);
    });

    it('adds workspace memberships when accepting', function () {
        $workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // User already in this tenant
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'invited@example.com',
        ]);

        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'invited@example.com',
            'invited_by' => $this->inviter->id,
            'status' => InvitationStatus::PENDING,
            'workspace_memberships' => [
                ['workspace_id' => $workspace->id, 'role' => 'viewer'],
            ],
        ]);

        $this->service->accept($invitation->token, $user);

        expect($workspace->hasMember($user->id))->toBeTrue();
        expect($workspace->getMemberRole($user->id))->toBe(WorkspaceRole::VIEWER);
    });

    it('throws exception for invalid token', function () {
        $user = User::factory()->create();

        expect(fn () => $this->service->accept('invalid-token', $user))
            ->toThrow(ValidationException::class);
    });

    it('throws exception for expired invitation', function () {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'invited@example.com',
        ]);

        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'invited@example.com',
            'invited_by' => $this->inviter->id,
            'status' => InvitationStatus::PENDING,
            'expires_at' => now()->subDay(),
        ]);

        expect(fn () => $this->service->accept($invitation->token, $user))
            ->toThrow(ValidationException::class);
    });

    it('throws exception for email mismatch', function () {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'different@example.com',
        ]);

        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'invited@example.com',
            'invited_by' => $this->inviter->id,
            'status' => InvitationStatus::PENDING,
        ]);

        expect(fn () => $this->service->accept($invitation->token, $user))
            ->toThrow(ValidationException::class);
    });
});

describe('decline', function () {
    it('revokes the invitation', function () {
        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'invited_by' => $this->inviter->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $this->service->decline($invitation->token);

        $invitation->refresh();
        expect($invitation->status)->toBe(InvitationStatus::REVOKED);
    });

    it('throws exception for invalid token', function () {
        expect(fn () => $this->service->decline('invalid-token'))
            ->toThrow(ValidationException::class);
    });
});
