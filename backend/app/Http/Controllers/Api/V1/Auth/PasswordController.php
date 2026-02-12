<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Data\Auth\ChangePasswordData;
use App\Data\Auth\ResetPasswordData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\Auth\PasswordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PasswordController extends Controller
{
    public function __construct(
        private readonly PasswordService $passwordService,
    ) {}

    /**
     * Handle forgot password request.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwordService->sendResetLink($request->validated('email'));

        return $this->success(null, 'Password reset link sent to your email');
    }

    /**
     * Handle password reset.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = ResetPasswordData::from($request->validated());

        $this->passwordService->reset($data);

        return $this->success(null, 'Password reset successfully');
    }

    /**
     * Handle password change for authenticated user.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = ChangePasswordData::from($request->validated());

        $this->passwordService->change($user, $data);

        return $this->success(null, 'Password changed successfully');
    }
}
