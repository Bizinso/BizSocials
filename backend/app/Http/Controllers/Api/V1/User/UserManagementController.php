<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Data\User\UserData;
use App\Enums\User\TenantRole;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\User;
use App\Services\Tenant\TenantService;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class UserManagementController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly TenantService $tenantService,
    ) {}

    /**
     * List all users in the tenant.
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

        $users = $this->userService->getUsersForTenant($tenant, $filters);

        // Transform paginated data
        $transformedItems = collect($users->items())->map(
            fn (User $member) => UserData::fromModel($member)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
            'links' => [
                'first' => $users->url(1),
                'last' => $users->url($users->lastPage()),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Create a new user.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'role_in_tenant' => ['required', Rule::in(TenantRole::values())],
            'phone' => ['nullable', 'string', 'max:50'],
            'timezone' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $tenant = $this->tenantService->getCurrent($currentUser);
            $newUser = $this->userService->createUser($tenant, $validated, $currentUser);

            return $this->success(
                UserData::fromModel($newUser)->toArray(),
                'User created successfully',
                201
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Get a specific user.
     */
    public function show(Request $request, string $userId): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $user = User::where('id', $userId)
            ->where('tenant_id', $currentUser->tenant_id)
            ->first();

        if ($user === null) {
            return $this->notFound('User not found');
        }

        return $this->success(
            UserData::fromModel($user)->toArray(),
            'User retrieved successfully'
        );
    }

    /**
     * Update a user's role.
     */
    public function updateRole(Request $request, string $userId): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $validated = $request->validate([
            'role_in_tenant' => ['required', Rule::in(TenantRole::values())],
        ]);

        $user = User::where('id', $userId)
            ->where('tenant_id', $currentUser->tenant_id)
            ->first();

        if ($user === null) {
            return $this->notFound('User not found');
        }

        try {
            $role = TenantRole::from($validated['role_in_tenant']);
            $updatedUser = $this->userService->updateUserRole($user, $role, $currentUser);

            return $this->success(
                UserData::fromModel($updatedUser)->toArray(),
                'User role updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Remove a user from the tenant.
     */
    public function destroy(Request $request, string $userId): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $user = User::where('id', $userId)
            ->where('tenant_id', $currentUser->tenant_id)
            ->first();

        if ($user === null) {
            return $this->notFound('User not found');
        }

        try {
            $this->userService->removeUser($user, $currentUser);

            return $this->success(null, 'User removed successfully');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Get permissions for the current user.
     */
    public function permissions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $permissions = $this->userService->getPermissionsForRole($user->role_in_tenant);

        return $this->success([
            'permissions' => $permissions,
            'role' => $user->role_in_tenant->value,
        ], 'Permissions retrieved successfully');
    }
}
