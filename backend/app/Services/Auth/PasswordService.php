<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Data\Auth\ChangePasswordData;
use App\Data\Auth\ResetPasswordData;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PasswordService extends BaseService
{
    /**
     * Send password reset link to user email.
     *
     * @throws ValidationException
     */
    public function sendResetLink(string $email): void
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        $this->log('Password reset link sent', ['email' => $email]);
    }

    /**
     * Reset user password with token.
     *
     * @throws ValidationException
     */
    public function reset(ResetPasswordData $data): void
    {
        $status = Password::reset(
            [
                'email' => $data->email,
                'password' => $data->password,
                'password_confirmation' => $data->password_confirmation,
                'token' => $data->token,
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                ])->setRememberToken(Str::random(60));

                $user->save();

                // Revoke all existing tokens
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        $this->log('Password reset successfully', ['email' => $data->email]);
    }

    /**
     * Change password for authenticated user.
     *
     * @throws ValidationException
     */
    public function change(User $user, ChangePasswordData $data): void
    {
        if (! Hash::check($data->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->forceFill([
            'password' => $data->password,
        ])->save();

        // Revoke all tokens except current
        /** @var \Laravel\Sanctum\PersonalAccessToken|null $currentToken */
        $currentToken = $user->currentAccessToken();

        if ($currentToken !== null) {
            $user->tokens()->where('id', '!=', $currentToken->id)->delete();
        }

        $this->log('Password changed', ['user_id' => $user->id]);
    }
}
