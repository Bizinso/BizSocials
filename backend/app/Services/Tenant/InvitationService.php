<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Data\Tenant\InviteUserData;
use App\Enums\User\InvitationStatus;
use App\Enums\User\TenantRole;
use App\Enums\User\UserStatus;
use App\Enums\Workspace\WorkspaceRole;
use App\Events\Tenant\InvitationAccepted;
use App\Events\Tenant\UserInvited;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\User\UserInvitation;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class InvitationService extends BaseService
{
    /**
     * Invite a user to a tenant.
     */
    public function invite(Tenant $tenant, InviteUserData $data, User $inviter): UserInvitation
    {
        return $this->transaction(function () use ($tenant, $data, $inviter) {
            // Check if user already exists in tenant
            $existingUser = User::where('tenant_id', $tenant->id)
                ->where('email', $data->email)
                ->first();

            if ($existingUser !== null) {
                throw ValidationException::withMessages([
                    'email' => ['This user is already a member of this tenant.'],
                ]);
            }

            // Check if there's already a pending invitation
            $existingInvitation = UserInvitation::where('tenant_id', $tenant->id)
                ->where('email', $data->email)
                ->where('status', InvitationStatus::PENDING)
                ->first();

            if ($existingInvitation !== null) {
                throw ValidationException::withMessages([
                    'email' => ['An invitation has already been sent to this email.'],
                ]);
            }

            // Build workspace memberships if workspace_ids are provided
            $workspaceMemberships = null;
            if ($data->workspace_ids !== null && !empty($data->workspace_ids)) {
                $workspaceMemberships = [];
                foreach ($data->workspace_ids as $workspaceId) {
                    // Verify workspace exists and belongs to tenant
                    $workspace = Workspace::where('id', $workspaceId)
                        ->where('tenant_id', $tenant->id)
                        ->first();

                    if ($workspace === null) {
                        throw ValidationException::withMessages([
                            'workspace_ids' => ["Workspace {$workspaceId} not found or does not belong to this tenant."],
                        ]);
                    }

                    $workspaceMemberships[] = [
                        'workspace_id' => $workspaceId,
                        'role' => WorkspaceRole::VIEWER->value,
                    ];
                }
            }

            $invitation = UserInvitation::create([
                'tenant_id' => $tenant->id,
                'email' => $data->email,
                'role_in_tenant' => $data->role,
                'workspace_memberships' => $workspaceMemberships,
                'invited_by' => $inviter->id,
                'status' => InvitationStatus::PENDING,
            ]);

            $this->log('User invited', [
                'tenant_id' => $tenant->id,
                'email' => $data->email,
                'invited_by' => $inviter->id,
            ]);

            event(new UserInvited($invitation));

            return $invitation;
        });
    }

    /**
     * List pending invitations for a tenant.
     */
    public function listPending(Tenant $tenant): Collection
    {
        return UserInvitation::where('tenant_id', $tenant->id)
            ->where('status', InvitationStatus::PENDING)
            ->with('inviter')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Resend an invitation.
     */
    public function resend(UserInvitation $invitation): void
    {
        if (!$invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => ['Only pending invitations can be resent.'],
            ]);
        }

        // Extend expiration
        $invitation->expires_at = now()->addDays(UserInvitation::EXPIRES_IN_DAYS);
        $invitation->token = UserInvitation::generateToken();
        $invitation->save();

        $this->log('Invitation resent', ['invitation_id' => $invitation->id]);

        event(new UserInvited($invitation));
    }

    /**
     * Cancel an invitation.
     */
    public function cancel(UserInvitation $invitation): void
    {
        if (!$invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => ['Only pending invitations can be cancelled.'],
            ]);
        }

        $invitation->revoke();

        $this->log('Invitation cancelled', ['invitation_id' => $invitation->id]);
    }

    /**
     * Accept an invitation.
     */
    public function accept(string $token, User $user): void
    {
        $this->transaction(function () use ($token, $user) {
            $invitation = UserInvitation::findByToken($token);

            if ($invitation === null) {
                throw ValidationException::withMessages([
                    'token' => ['Invalid invitation token.'],
                ]);
            }

            if (!$invitation->canBeAccepted()) {
                throw ValidationException::withMessages([
                    'token' => ['This invitation has expired or is no longer valid.'],
                ]);
            }

            // Verify email matches
            if ($user->email !== $invitation->email) {
                throw ValidationException::withMessages([
                    'email' => ['The invitation was sent to a different email address.'],
                ]);
            }

            // Check if user already belongs to another tenant
            if ($user->tenant_id !== null && $user->tenant_id !== $invitation->tenant_id) {
                throw ValidationException::withMessages([
                    'user' => ['You already belong to another organization.'],
                ]);
            }

            // Update user's tenant and role
            $user->tenant_id = $invitation->tenant_id;
            $user->role_in_tenant = $invitation->role_in_tenant;
            $user->status = UserStatus::ACTIVE;
            $user->save();

            // Add workspace memberships if specified
            if ($invitation->workspace_memberships !== null) {
                foreach ($invitation->workspace_memberships as $membership) {
                    $workspace = Workspace::find($membership['workspace_id']);
                    if ($workspace !== null) {
                        $role = WorkspaceRole::tryFrom($membership['role']) ?? WorkspaceRole::VIEWER;
                        $workspace->addMember($user, $role);
                    }
                }
            }

            // Mark invitation as accepted
            $invitation->accept();

            $this->log('Invitation accepted', [
                'invitation_id' => $invitation->id,
                'user_id' => $user->id,
            ]);

            event(new InvitationAccepted($invitation, $user));
        });
    }

    /**
     * Decline an invitation.
     */
    public function decline(string $token): void
    {
        $invitation = UserInvitation::findByToken($token);

        if ($invitation === null) {
            throw ValidationException::withMessages([
                'token' => ['Invalid invitation token.'],
            ]);
        }

        if (!$invitation->isPending()) {
            throw ValidationException::withMessages([
                'token' => ['This invitation is no longer pending.'],
            ]);
        }

        $invitation->status = InvitationStatus::REVOKED;
        $invitation->save();

        $this->log('Invitation declined', ['invitation_id' => $invitation->id]);
    }
}
