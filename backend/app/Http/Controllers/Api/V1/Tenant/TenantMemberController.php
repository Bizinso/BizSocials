<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Data\Tenant\TenantMemberData;
use App\Enums\User\TenantRole;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Tenant\UpdateMemberRoleRequest;
use App\Models\User;
use App\Services\Tenant\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TenantMemberController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService,
    ) {}

    /**
     * List members of the current tenant.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tenant = $this->tenantService->getCurrent($user);

        $filters = [
            'role' => $request->query('role'),
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 15),
        ];

        $members = $this->tenantService->getMembers($tenant, $filters);

        // Transform paginated data
        $transformedItems = collect($members->items())->map(
            fn (User $member) => TenantMemberData::fromModel($member)->toArray()
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
     * Update a member's role.
     */
    public function update(UpdateMemberRoleRequest $request, string $userId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tenant = $this->tenantService->getCurrent($user);

        // Find target user
        $targetUser = User::where('id', $userId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($targetUser === null) {
            return $this->notFound('Member not found');
        }

        // Cannot change own role
        if ($targetUser->id === $user->id) {
            return $this->error('You cannot change your own role', 422);
        }

        $role = TenantRole::from($request->validated()['role']);

        $updatedUser = $this->tenantService->updateMemberRole($tenant, $targetUser, $role);

        return $this->success(
            TenantMemberData::fromModel($updatedUser)->toArray(),
            'Member role updated successfully'
        );
    }

    /**
     * Remove a member from the tenant.
     */
    public function destroy(Request $request, string $userId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Check if user is admin
        if (!$user->isAdmin()) {
            return $this->forbidden('Only admins can remove members');
        }

        $tenant = $this->tenantService->getCurrent($user);

        // Find target user
        $targetUser = User::where('id', $userId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($targetUser === null) {
            return $this->notFound('Member not found');
        }

        // Cannot remove self
        if ($targetUser->id === $user->id) {
            return $this->error('You cannot remove yourself', 422);
        }

        $this->tenantService->removeMember($tenant, $targetUser);

        return $this->success(null, 'Member removed successfully');
    }
}
