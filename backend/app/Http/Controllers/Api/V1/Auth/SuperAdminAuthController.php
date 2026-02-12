<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Data\Auth\LoginData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Platform\SuperAdminUser;
use App\Services\Auth\SuperAdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SuperAdminAuthController extends Controller
{
    public function __construct(
        private readonly SuperAdminAuthService $authService,
    ) {}

    /**
     * Authenticate a super admin.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $data = LoginData::from([
            'email' => $validated['email'],
            'password' => $validated['password'],
            'remember' => false,
        ]);

        $result = $this->authService->login($data);

        return $this->success([
            'admin' => [
                'id' => $result['admin']->id,
                'name' => $result['admin']->name,
                'email' => $result['admin']->email,
                'role' => $result['admin']->role->value,
            ],
            'token' => $result['token'],
            'expires_in' => $result['expires_in'],
        ]);
    }

    /**
     * Logout the authenticated super admin.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var SuperAdminUser $admin */
        $admin = $request->user();

        $this->authService->logout($admin);

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Get the authenticated super admin profile.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var SuperAdminUser $admin */
        $admin = $request->user();

        return $this->success([
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role->value,
            'last_login_at' => $admin->last_login_at?->toIso8601String(),
        ]);
    }
}
