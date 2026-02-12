<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Data\Auth\LoginData;
use App\Models\Platform\SuperAdminUser;
use App\Services\BaseService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class SuperAdminAuthService extends BaseService
{
    /**
     * Authenticate a super admin user and create a token.
     *
     * @return array{admin: SuperAdminUser, token: string, expires_in: int}
     *
     * @throws ValidationException
     */
    public function login(LoginData $data): array
    {
        $admin = SuperAdminUser::where('email', $data->email)->first();

        if (! $admin || ! Hash::check($data->password, $admin->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $admin->canLogin()) {
            throw ValidationException::withMessages([
                'email' => ['Your admin account is not active. Please contact a platform administrator.'],
            ]);
        }

        // Revoke existing tokens
        $admin->tokens()->delete();

        // Create a new token (24 hours)
        $expiresIn = 60 * 24;
        $token = $admin->createToken(
            'admin-auth-token',
            ['*'],
            now()->addMinutes($expiresIn)
        )->plainTextToken;

        // Record login
        $admin->recordLogin();

        $this->log('Super admin logged in', ['admin_id' => $admin->id]);

        return [
            'admin' => $admin,
            'token' => $token,
            'expires_in' => $expiresIn * 60, // Convert to seconds
        ];
    }

    /**
     * Logout super admin by revoking current token.
     */
    public function logout(SuperAdminUser $admin): void
    {
        /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
        $token = $admin->currentAccessToken();

        if ($token !== null) {
            $token->delete();
        }

        $this->log('Super admin logged out', ['admin_id' => $admin->id]);
    }
}
