<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Data\Auth\AuthResponseData;
use App\Data\Auth\LoginData;
use App\Data\Auth\RegisterData;
use App\Data\User\UserData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Auth\MfaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly MfaService $mfaService,
    ) {}

    /**
     * Handle user login.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = LoginData::from($request->validated());

        $result = $this->authService->login($data);

        // MFA required — return intermediate response
        if (!empty($result['mfa_required'])) {
            return $this->success([
                'mfa_required' => true,
                'mfa_token' => $result['token'],
                'expires_in' => $result['expires_in'],
            ], 'MFA verification required');
        }

        $response = new AuthResponseData(
            user: UserData::fromModel($result['user']),
            token: $result['token'],
            token_type: 'Bearer',
            expires_in: $result['expires_in'],
        );

        return $this->success($response->toArray(), 'Login successful');
    }

    /**
     * Handle user registration.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = RegisterData::from($request->validated());

        $user = $this->authService->register($data);

        // Create token for new user
        $token = $user->createToken(
            'auth-token',
            ['*'],
            now()->addMinutes(60 * 24)
        )->plainTextToken;

        $response = new AuthResponseData(
            user: UserData::fromModel($user),
            token: $token,
            token_type: 'Bearer',
            expires_in: 60 * 24 * 60, // 24 hours in seconds
        );

        return $this->created($response->toArray(), 'Registration successful');
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authService->logout($user);

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Handle token refresh.
     */
    public function refresh(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $token = $this->authService->refreshToken($user);

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 60 * 24 * 60, // 24 hours in seconds
        ], 'Token refreshed successfully');
    }

    /**
     * Handle email verification.
     */
    public function verifyEmail(Request $request, string $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (! $this->authService->verifyEmail($user, $hash)) {
            return $this->error('Invalid verification link', 400);
        }

        return $this->success(null, 'Email verified successfully');
    }

    /**
     * Handle resend verification email.
     */
    public function resendVerification(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success(null, 'Email already verified');
        }

        $this->authService->resendVerification($user);

        return $this->success(null, 'Verification email sent');
    }

    /**
     * Verify MFA code during login.
     */
    public function mfaVerifyLogin(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:6']);

        /** @var User $user */
        $user = $request->user();

        if (!$this->mfaService->verifyLogin($user, $request->input('code'))) {
            return $this->error('Invalid MFA code', 422);
        }

        // Revoke the MFA-pending token
        $user->currentAccessToken()?->delete();

        // Issue full token
        $result = $this->authService->issueFullToken($user);

        $response = new AuthResponseData(
            user: UserData::fromModel($result['user']),
            token: $result['token'],
            token_type: 'Bearer',
            expires_in: $result['expires_in'],
        );

        return $this->success($response->toArray(), 'Login successful');
    }

    /**
     * Initiate MFA setup (generate secret + QR URI).
     */
    public function mfaSetup(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $result = $this->mfaService->setup($user);

        return $this->success($result, 'MFA setup initiated');
    }

    /**
     * Verify TOTP code during setup and enable MFA.
     */
    public function mfaVerifySetup(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:6']);

        /** @var User $user */
        $user = $request->user();

        $result = $this->mfaService->verifySetup($user, $request->input('code'));

        return $this->success($result, 'MFA enabled successfully');
    }

    /**
     * Disable MFA.
     */
    public function mfaDisable(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);

        /** @var User $user */
        $user = $request->user();

        $this->mfaService->disable($user, $request->input('password'));

        return $this->success(null, 'MFA disabled successfully');
    }

    /**
     * Get MFA status.
     */
    public function mfaStatus(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success($this->mfaService->status($user));
    }
}
