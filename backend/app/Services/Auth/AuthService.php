<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Data\Auth\LoginData;
use App\Data\Auth\RegisterData;
use App\Enums\Tenant\TenantStatus;
use App\Enums\Tenant\TenantType;
use App\Enums\User\TenantRole;
use App\Enums\User\UserStatus;
use App\Events\Tenant\TenantCreated;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantOnboarding;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AuthService extends BaseService
{
    /**
     * Authenticate user and create a token.
     *
     * If MFA is enabled, returns mfa_required = true with a temporary token
     * that must be exchanged via the MFA verification endpoint.
     *
     * @return array{user: User, token: string, expires_in: int, mfa_required?: bool}
     *
     * @throws ValidationException
     */
    public function login(LoginData $data): array
    {
        $user = User::where('email', $data->email)->first();

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->canLogin()) {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Please contact support.'],
            ]);
        }

        // Check if MFA is enabled — return intermediate response
        if ($user->mfa_enabled) {
            $mfaToken = $user->createToken(
                'mfa-pending',
                ['mfa:verify'],
                now()->addMinutes(5),
            )->plainTextToken;

            $this->log('MFA verification required', ['user_id' => $user->id]);

            return [
                'user' => $user,
                'token' => $mfaToken,
                'expires_in' => 300,
                'mfa_required' => true,
            ];
        }

        return $this->issueFullToken($user, $data->remember);
    }

    /**
     * Issue a full auth token after successful authentication (or MFA verification).
     *
     * @return array{user: User, token: string, expires_in: int}
     */
    public function issueFullToken(User $user, bool $remember = false): array
    {
        // Limit active tokens: keep the 9 most recent, remove older ones
        $tokenCount = $user->tokens()->count();
        if ($tokenCount >= 10) {
            $user->tokens()
                ->orderBy('created_at')
                ->limit($tokenCount - 9)
                ->delete();
        }

        // Create a new token
        $expiresIn = $remember ? 60 * 24 * 30 : 60 * 24; // 30 days or 24 hours
        $token = $user->createToken(
            'auth-token',
            ['*'],
            now()->addMinutes($expiresIn)
        )->plainTextToken;

        // Record login
        $user->recordLogin();

        $this->log('User logged in', ['user_id' => $user->id]);

        return [
            'user' => $user,
            'token' => $token,
            'expires_in' => $expiresIn * 60, // Convert to seconds
        ];
    }

    /**
     * Register a new user.
     */
    public function register(RegisterData $data): User
    {
        return $this->transaction(function () use ($data) {
            $tenantId = $data->tenant_id;
            $isNewTenant = ! $tenantId;

            if ($isNewTenant) {
                $tenantName = $data->name."'s Organization";
                $tenant = Tenant::create([
                    'name' => $tenantName,
                    'slug' => Str::slug($tenantName).'-'.Str::lower(Str::random(6)),
                    'type' => TenantType::INDIVIDUAL,
                    'status' => TenantStatus::PENDING,
                ]);
                $tenantId = $tenant->id;
            }

            $user = User::create([
                'tenant_id' => $tenantId,
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password, // Cast will hash it
                'status' => UserStatus::ACTIVE,
                'role_in_tenant' => $isNewTenant ? TenantRole::OWNER : TenantRole::MEMBER,
                'language' => 'en',
            ]);

            if ($isNewTenant) {
                $tenant->update(['owner_user_id' => $user->id]);

                TenantOnboarding::create([
                    'tenant_id' => $tenant->id,
                    'current_step' => 'account_created',
                    'steps_completed' => ['account_created'],
                    'started_at' => now(),
                ]);

                event(new TenantCreated($tenant));
            }

            event(new Registered($user));

            $this->log('User registered', ['user_id' => $user->id]);

            return $user;
        });
    }

    /**
     * Logout user by revoking current token.
     */
    public function logout(User $user): void
    {
        /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();

        if ($token !== null) {
            $token->delete();
        }

        $this->log('User logged out', ['user_id' => $user->id]);
    }

    /**
     * Refresh user token by revoking old tokens and creating a new one.
     */
    public function refreshToken(User $user): string
    {
        // Revoke all tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken(
            'auth-token',
            ['*'],
            now()->addMinutes(60 * 24)
        )->plainTextToken;

        $this->log('Token refreshed', ['user_id' => $user->id]);

        return $token;
    }

    /**
     * Verify user email.
     *
     * On first verification, also activates the tenant (PENDING → ACTIVE)
     * and advances the onboarding state to email_verified.
     */
    public function verifyEmail(User $user, string $hash): bool
    {
        if (! hash_equals(sha1($user->email), $hash)) {
            return false;
        }

        if (! $user->hasVerifiedEmail()) {
            $this->transaction(function () use ($user) {
                $user->markEmailAsVerified();

                $tenant = $user->tenant;
                if ($tenant && $tenant->status === TenantStatus::PENDING) {
                    $tenant->update(['status' => TenantStatus::ACTIVE]);
                }

                $onboarding = $tenant?->onboarding;
                if ($onboarding && ! $onboarding->isStepCompleted('email_verified')) {
                    $onboarding->completeStep('email_verified');
                }

                $this->log('Email verified', ['user_id' => $user->id, 'tenant_id' => $tenant?->id]);
            });
        }

        return true;
    }

    /**
     * Resend verification email.
     */
    public function resendVerification(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();

        $this->log('Verification email resent', ['user_id' => $user->id]);
    }
}
