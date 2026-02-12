<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Data\Tenant\InvitationData;
use App\Data\Tenant\InviteUserData;
use App\Enums\User\TenantRole;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Tenant\InviteUserRequest;
use App\Models\User;
use App\Models\User\UserInvitation;
use App\Services\Tenant\InvitationService;
use App\Services\Tenant\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InvitationController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitationService,
        private readonly TenantService $tenantService,
    ) {}

    /**
     * List pending invitations.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (!$user->isAdmin()) {
            return $this->forbidden('Only admins can view invitations');
        }

        $tenant = $this->tenantService->getCurrent($user);
        $invitations = $this->invitationService->listPending($tenant);

        $data = $invitations->map(
            fn (UserInvitation $invitation) => InvitationData::fromModel($invitation)->toArray()
        );

        return $this->success($data->toArray(), 'Invitations retrieved successfully');
    }

    /**
     * Invite a user to the tenant.
     */
    public function store(InviteUserRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tenant = $this->tenantService->getCurrent($user);

        $validated = $request->validated();
        $role = isset($validated['role']) ? TenantRole::from($validated['role']) : TenantRole::MEMBER;

        $data = new InviteUserData(
            email: $validated['email'],
            role: $role,
            workspace_ids: $validated['workspace_ids'] ?? null,
        );

        $invitation = $this->invitationService->invite($tenant, $data, $user);

        return $this->created(
            InvitationData::fromModel($invitation)->toArray(),
            'Invitation sent successfully'
        );
    }

    /**
     * Resend an invitation.
     */
    public function resend(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (!$user->isAdmin()) {
            return $this->forbidden('Only admins can resend invitations');
        }

        $tenant = $this->tenantService->getCurrent($user);

        $invitation = UserInvitation::where('id', $id)
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($invitation === null) {
            return $this->notFound('Invitation not found');
        }

        $this->invitationService->resend($invitation);

        return $this->success(
            InvitationData::fromModel($invitation->fresh())->toArray(),
            'Invitation resent successfully'
        );
    }

    /**
     * Cancel an invitation.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (!$user->isAdmin()) {
            return $this->forbidden('Only admins can cancel invitations');
        }

        $tenant = $this->tenantService->getCurrent($user);

        $invitation = UserInvitation::where('id', $id)
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($invitation === null) {
            return $this->notFound('Invitation not found');
        }

        $this->invitationService->cancel($invitation);

        return $this->success(null, 'Invitation cancelled successfully');
    }

    /**
     * Accept an invitation (public route).
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return $this->unauthorized('Authentication required to accept invitation');
        }

        $this->invitationService->accept($token, $user);

        return $this->success(null, 'Invitation accepted successfully');
    }

    /**
     * Decline an invitation (public route).
     */
    public function decline(string $token): JsonResponse
    {
        $this->invitationService->decline($token);

        return $this->success(null, 'Invitation declined successfully');
    }
}
