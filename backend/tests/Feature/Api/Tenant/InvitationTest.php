<?php

declare(strict_types=1);

use App\Enums\User\InvitationStatus;
use App\Enums\User\TenantRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\User\UserInvitation;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->admin = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::ADMIN,
    ]);
    $this->member = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::MEMBER,
    ]);
});

describe('GET /api/v1/tenants/current/invitations', function () {
    it('returns list of pending invitations', function () {
        // Create some invitations
        UserInvitation::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'invited_by' => $this->owner->id,
            'status' => InvitationStatus::PENDING,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/tenants/current/invitations');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('denies member from viewing invitations', function () {
        Sanctum::actingAs($this->member);

        $response = $this->getJson('/api/v1/tenants/current/invitations');

        $response->assertForbidden();
    });
});

describe('POST /api/v1/tenants/current/invitations', function () {
    it('allows admin to invite user', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/tenants/current/invitations', [
            'email' => 'newuser@example.com',
            'role' => 'member',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'email',
                    'role',
                    'status',
                    'expires_at',
                ],
            ])
            ->assertJsonPath('data.email', 'newuser@example.com');
    });

    it('denies member from sending invitations', function () {
        Sanctum::actingAs($this->member);

        $response = $this->postJson('/api/v1/tenants/current/invitations', [
            'email' => 'newuser@example.com',
        ]);

        $response->assertForbidden();
    });

    it('prevents inviting existing member', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/tenants/current/invitations', [
            'email' => $this->member->email,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('prevents duplicate pending invitations', function () {
        $email = 'duplicate@example.com';

        UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => $email,
            'invited_by' => $this->owner->id,
            'status' => InvitationStatus::PENDING,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/tenants/current/invitations', [
            'email' => $email,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('validates email format', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/tenants/current/invitations', [
            'email' => 'not-an-email',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });
});

describe('POST /api/v1/tenants/current/invitations/{id}/resend', function () {
    it('resends pending invitation', function () {
        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'invited_by' => $this->owner->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $oldToken = $invitation->token;

        Sanctum::actingAs($this->owner);

        $response = $this->postJson("/api/v1/tenants/current/invitations/{$invitation->id}/resend");

        $response->assertOk();

        $invitation->refresh();
        expect($invitation->token)->not->toBe($oldToken);
    });

    it('fails for non-pending invitation', function () {
        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'invited_by' => $this->owner->id,
            'status' => InvitationStatus::ACCEPTED,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson("/api/v1/tenants/current/invitations/{$invitation->id}/resend");

        $response->assertStatus(422);
    });
});

describe('DELETE /api/v1/tenants/current/invitations/{id}', function () {
    it('cancels pending invitation', function () {
        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'invited_by' => $this->owner->id,
            'status' => InvitationStatus::PENDING,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/tenants/current/invitations/{$invitation->id}");

        $response->assertOk();

        $invitation->refresh();
        expect($invitation->status)->toBe(InvitationStatus::REVOKED);
    });
});

describe('POST /api/v1/invitations/{token}/accept', function () {
    it('accepts invitation for user in same tenant', function () {
        // Create a user already in this tenant
        $existingUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => $existingUser->email,
            'invited_by' => $this->owner->id,
            'status' => InvitationStatus::PENDING,
            'role_in_tenant' => TenantRole::ADMIN, // Upgrading role
        ]);

        Sanctum::actingAs($existingUser);

        $response = $this->postJson("/api/v1/invitations/{$invitation->token}/accept");

        $response->assertOk();

        $existingUser->refresh();
        expect($existingUser->tenant_id)->toBe($this->tenant->id);
        expect($existingUser->role_in_tenant)->toBe(TenantRole::ADMIN);

        $invitation->refresh();
        expect($invitation->status)->toBe(InvitationStatus::ACCEPTED);
    });

    it('fails with invalid token', function () {
        Sanctum::actingAs($this->member);

        $response = $this->postJson('/api/v1/invitations/invalid-token/accept');

        $response->assertStatus(422);
    });

    it('fails for expired invitation', function () {
        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => $this->member->email,
            'invited_by' => $this->owner->id,
            'status' => InvitationStatus::PENDING,
            'expires_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($this->member);

        $response = $this->postJson("/api/v1/invitations/{$invitation->token}/accept");

        $response->assertStatus(422);
    });

    it('fails for user from different tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => $otherUser->email,
            'invited_by' => $this->owner->id,
            'status' => InvitationStatus::PENDING,
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->postJson("/api/v1/invitations/{$invitation->token}/accept");

        $response->assertStatus(422);
    });
});

describe('POST /api/v1/invitations/{token}/decline', function () {
    it('declines invitation', function () {
        $invitation = UserInvitation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'invited_by' => $this->owner->id,
            'status' => InvitationStatus::PENDING,
        ]);

        $response = $this->postJson("/api/v1/invitations/{$invitation->token}/decline");

        $response->assertOk();

        $invitation->refresh();
        expect($invitation->status)->toBe(InvitationStatus::REVOKED);
    });
});
