<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Platform;

use App\Data\Admin\AdminUserData;
use App\Data\Admin\SuspendData;
use App\Data\Admin\UpdateUserAdminData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\User;
use App\Services\Admin\AdminUserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Exceptions\CannotCreateData;

final class AdminUserController extends Controller
{
    public function __construct(
        private readonly AdminUserService $userService,
    ) {}

    /**
     * List all users.
     * GET /admin/users
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->query('status'),
            'tenant_id' => $request->query('tenant_id'),
            'role' => $request->query('role'),
            'email_verified' => $request->query('email_verified'),
            'mfa_enabled' => $request->query('mfa_enabled'),
            'search' => $request->query('search'),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
            'per_page' => $request->query('per_page', 15),
        ];

        $users = $this->userService->list($filters);

        $transformedItems = collect($users->items())->map(
            fn (User $user) => AdminUserData::fromModel($user)->toArray()
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
     * Get a specific user.
     * GET /admin/users/{user}
     */
    public function show(string $user): JsonResponse
    {
        try {
            $userModel = $this->userService->get($user);

            return $this->success(
                AdminUserData::fromModel($userModel)->toArray(),
                'User retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('User not found');
        }
    }

    /**
     * Update a user.
     * PUT /admin/users/{user}
     */
    public function update(Request $request, string $user): JsonResponse
    {
        try {
            $userModel = $this->userService->get($user);

            $data = UpdateUserAdminData::from($request->all());
            $updatedUser = $this->userService->update($userModel, $data);

            return $this->success(
                AdminUserData::fromModel($updatedUser)->toArray(),
                'User updated successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('User not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        }
    }

    /**
     * Suspend a user.
     * POST /admin/users/{user}/suspend
     */
    public function suspend(Request $request, string $user): JsonResponse
    {
        try {
            $userModel = $this->userService->get($user);

            $data = SuspendData::from($request->all());
            $suspendedUser = $this->userService->suspend($userModel, $data->reason);

            return $this->success(
                AdminUserData::fromModel($suspendedUser)->toArray(),
                'User suspended successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('User not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        } catch (CannotCreateData $e) {
            return $this->validationError(['reason' => ['The reason field is required.']], 'Validation failed');
        }
    }

    /**
     * Activate a user.
     * POST /admin/users/{user}/activate
     */
    public function activate(string $user): JsonResponse
    {
        try {
            $userModel = $this->userService->get($user);
            $activatedUser = $this->userService->activate($userModel);

            return $this->success(
                AdminUserData::fromModel($activatedUser)->toArray(),
                'User activated successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('User not found');
        }
    }

    /**
     * Reset user password.
     * POST /admin/users/{user}/reset-password
     */
    public function resetPassword(string $user): JsonResponse
    {
        try {
            $userModel = $this->userService->get($user);
            $this->userService->resetPassword($userModel);

            return $this->success(null, 'Password reset email sent successfully');
        } catch (ModelNotFoundException) {
            return $this->notFound('User not found');
        }
    }

    /**
     * Get user statistics.
     * GET /admin/users/stats
     */
    public function stats(): JsonResponse
    {
        $stats = $this->userService->getStats();

        return $this->success($stats, 'User statistics retrieved successfully');
    }
}
