<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Workspace;

use App\Data\Workspace\AddMemberData;
use App\Data\Workspace\WorkspaceMemberData;
use App\Enums\Workspace\Permission;
use App\Enums\Workspace\WorkspaceRole;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Workspace\AddMemberRequest;
use App\Http\Requests\Workspace\UpdateMemberRoleRequest;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;
use App\Services\Workspace\WorkspaceMembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkspaceMemberController extends Controller
{
    public function __construct(
        private readonly WorkspaceMembershipService $membershipService,
    ) {}

    /**
     * List members of a workspace.
     */
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Check if user has access to this workspace
        $membership = $this->membershipService->getMembership($workspace, $user);

        if ($membership === null && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $filters = [
            'role' => $request->query('role'),
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 15),
        ];

        $members = $this->membershipService->listMembers($workspace, $filters);

        // Transform paginated data
        $transformedItems = collect($members->items())->map(
            fn (WorkspaceMembership $membership) => WorkspaceMemberData::fromMembership($membership)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Members retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
                'from' => $members->firstItem(),
                'to' => $members->lastItem(),
            ],
            'links' => [
                'first' => $members->url(1),
                'last' => $members->url($members->lastPage()),
                'prev' => $members->previousPageUrl(),
                'next' => $members->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Add a member to a workspace.
     */
    public function store(AddMemberRequest $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        $validated = $request->validated();
        $role = isset($validated['role']) ? WorkspaceRole::from($validated['role']) : WorkspaceRole::VIEWER;

        // Find user to add
        $targetUser = User::find($validated['user_id']);

        if ($targetUser === null) {
            return $this->notFound('User not found');
        }

        $membership = $this->membershipService->addMember($workspace, $targetUser, $role);

        return $this->created(
            WorkspaceMemberData::fromMembership($membership)->toArray(),
            'Member added successfully'
        );
    }

    /**
     * Update a member's role.
     */
    public function update(UpdateMemberRoleRequest $request, Workspace $workspace, string $userId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Find target user
        $targetUser = User::where('id', $userId)
            ->where('tenant_id', $user->tenant_id)
            ->first();

        if ($targetUser === null) {
            return $this->notFound('Member not found');
        }

        // Cannot change own role
        if ($targetUser->id === $user->id) {
            return $this->error('You cannot change your own role', 422);
        }

        $role = WorkspaceRole::from($request->validated()['role']);

        $membership = $this->membershipService->updateRole($workspace, $targetUser, $role);

        return $this->success(
            WorkspaceMemberData::fromMembership($membership)->toArray(),
            'Member role updated successfully'
        );
    }

    /**
     * Remove a member from a workspace.
     */
    public function destroy(Request $request, Workspace $workspace, string $userId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Check if user can manage members
        $membership = $this->membershipService->getMembership($workspace, $user);

        if ($membership === null || !$membership->hasPermission(Permission::WORKSPACE_MEMBERS_MANAGE)) {
            return $this->forbidden('You do not have permission to remove members');
        }

        // Find target user
        $targetUser = User::where('id', $userId)
            ->where('tenant_id', $user->tenant_id)
            ->first();

        if ($targetUser === null) {
            return $this->notFound('Member not found');
        }

        // Cannot remove self
        if ($targetUser->id === $user->id) {
            return $this->error('You cannot remove yourself', 422);
        }

        $this->membershipService->removeMember($workspace, $targetUser);

        return $this->success(null, 'Member removed successfully');
    }
}
