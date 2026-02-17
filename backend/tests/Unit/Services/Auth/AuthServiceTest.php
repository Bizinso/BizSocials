<?php

declare(strict_types=1);

use App\Data\Auth\LoginData;
use App\Data\Auth\RegisterData;
use App\Enums\Tenant\TenantStatus;
use App\Enums\User\TenantRole;
use App\Enums\User\UserStatus;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

describe('AuthService', function () {
    beforeEach(function () {
        $this->authService = app(AuthService::class);
    });

    describe('login', function () {
        it('authenticates user with valid credentials', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => 'password123',
                'status' => UserStatus::ACTIVE,
                'mfa_enabled' => false,
            ]);

            $loginData = new LoginData(
                email: 'test@example.com',
                password: 'password123',
                remember: false
            );

            $result = $this->authService->login($loginData);

            expect($result)->toHaveKeys(['user', 'token', 'expires_in'])
                ->and($result['user']->id)->toBe($user->id)
                ->and($result['token'])->toBeString()
                ->and($result['expires_in'])->toBeInt();
        });

        it('verifies password is hashed with bcrypt', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

            // Verify password is hashed
            expect($user->password)->not->toBe('password123')
                ->and(Hash::check('password123', $user->password))->toBeTrue()
                ->and(str_starts_with($user->password, '$2y$'))->toBeTrue(); // bcrypt identifier
        });

        it('throws exception for invalid email', function () {
            $loginData = new LoginData(
                email: 'nonexistent@example.com',
                password: 'password123',
                remember: false
            );

            $this->authService->login($loginData);
        })->throws(ValidationException::class, 'The provided credentials are incorrect.');

        it('throws exception for invalid password', function () {
            User::factory()->create([
                'email' => 'test@example.com',
                'password' => 'correctpassword',
                'status' => UserStatus::ACTIVE,
            ]);

            $loginData = new LoginData(
                email: 'test@example.com',
                password: 'wrongpassword',
                remember: false
            );

            $this->authService->login($loginData);
        })->throws(ValidationException::class, 'The provided credentials are incorrect.');

        it('throws exception for inactive user', function () {
            User::factory()->create([
                'email' => 'test@example.com',
                'password' => 'password123',
                'status' => UserStatus::SUSPENDED,
            ]);

            $loginData = new LoginData(
                email: 'test@example.com',
                password: 'password123',
                remember: false
            );

            $this->authService->login($loginData);
        })->throws(ValidationException::class, 'Your account is not active');

        it('returns mfa_required for users with MFA enabled', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => 'password123',
                'status' => UserStatus::ACTIVE,
                'mfa_enabled' => true,
                'mfa_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            ]);

            $loginData = new LoginData(
                email: 'test@example.com',
                password: 'password123',
                remember: false
            );

            $result = $this->authService->login($loginData);

            expect($result)->toHaveKey('mfa_required')
                ->and($result['mfa_required'])->toBeTrue()
                ->and($result['expires_in'])->toBe(300); // 5 minutes
        });

        it('records login timestamp', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => 'password123',
                'status' => UserStatus::ACTIVE,
                'mfa_enabled' => false,
                'last_login_at' => null,
            ]);

            $loginData = new LoginData(
                email: 'test@example.com',
                password: 'password123',
                remember: false
            );

            $this->authService->login($loginData);

            $user->refresh();
            expect($user->last_login_at)->not->toBeNull()
                ->and($user->last_active_at)->not->toBeNull();
        });

        it('limits active tokens to 10', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => 'password123',
                'status' => UserStatus::ACTIVE,
                'mfa_enabled' => false,
            ]);

            // Create 10 existing tokens
            for ($i = 0; $i < 10; $i++) {
                $user->createToken('test-token-'.$i);
            }

            expect($user->tokens()->count())->toBe(10);

            $loginData = new LoginData(
                email: 'test@example.com',
                password: 'password123',
                remember: false
            );

            $this->authService->login($loginData);

            // Should still have 10 tokens (oldest one removed)
            expect($user->tokens()->count())->toBe(10);
        });

        it('respects remember me option', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => 'password123',
                'status' => UserStatus::ACTIVE,
                'mfa_enabled' => false,
            ]);

            $loginDataRemember = new LoginData(
                email: 'test@example.com',
                password: 'password123',
                remember: true
            );

            $result = $this->authService->login($loginDataRemember);

            // Remember me should give 30 days (43200 minutes = 2592000 seconds)
            expect($result['expires_in'])->toBe(60 * 24 * 30 * 60);
        });
    });

    describe('register', function () {
        it('creates new user with hashed password', function () {
            $registerData = new RegisterData(
                name: 'Test User',
                email: 'newuser@example.com',
                password: 'password123',
                password_confirmation: 'password123',
                tenant_id: null
            );

            $user = $this->authService->register($registerData);

            expect($user)->toBeInstanceOf(User::class)
                ->and($user->email)->toBe('newuser@example.com')
                ->and($user->name)->toBe('Test User')
                ->and($user->password)->not->toBe('password123')
                ->and(Hash::check('password123', $user->password))->toBeTrue()
                ->and($user->status)->toBe(UserStatus::ACTIVE);
        });

        it('creates new tenant for user without tenant_id', function () {
            $registerData = new RegisterData(
                name: 'Test User',
                email: 'newuser@example.com',
                password: 'password123',
                password_confirmation: 'password123',
                tenant_id: null
            );

            $user = $this->authService->register($registerData);

            expect($user->tenant)->not->toBeNull()
                ->and($user->tenant->owner_user_id)->toBe($user->id)
                ->and($user->role_in_tenant)->toBe(TenantRole::OWNER)
                ->and($user->tenant->status)->toBe(TenantStatus::PENDING);
        });

        it('assigns user to existing tenant', function () {
            $tenant = Tenant::factory()->create();

            $registerData = new RegisterData(
                name: 'Test User',
                email: 'newuser@example.com',
                password: 'password123',
                password_confirmation: 'password123',
                tenant_id: $tenant->id
            );

            $user = $this->authService->register($registerData);

            expect($user->tenant_id)->toBe($tenant->id)
                ->and($user->role_in_tenant)->toBe(TenantRole::MEMBER);
        });

        it('creates tenant onboarding record for new tenant', function () {
            $registerData = new RegisterData(
                name: 'Test User',
                email: 'newuser@example.com',
                password: 'password123',
                password_confirmation: 'password123',
                tenant_id: null
            );

            $user = $this->authService->register($registerData);

            expect($user->tenant->onboarding)->not->toBeNull()
                ->and($user->tenant->onboarding->current_step)->toBe('account_created')
                ->and($user->tenant->onboarding->steps_completed)->toContain('account_created');
        });
    });

    describe('logout', function () {
        it('revokes current access token', function () {
            $user = User::factory()->create();
            $tokenResult = $user->createToken('test-token');

            // Set the current access token on the user
            $user->withAccessToken($tokenResult->accessToken);

            expect($user->tokens()->count())->toBe(1);

            $this->authService->logout($user);

            expect($user->tokens()->count())->toBe(0);
        });

        it('handles logout when no token exists', function () {
            $user = User::factory()->create();

            expect($user->tokens()->count())->toBe(0);

            // Should not throw exception
            $this->authService->logout($user);

            expect($user->tokens()->count())->toBe(0);
        });
    });

    describe('refreshToken', function () {
        it('revokes old tokens and creates new one', function () {
            $user = User::factory()->create();
            $oldToken = $user->createToken('old-token')->plainTextToken;

            expect($user->tokens()->count())->toBe(1);

            $newToken = $this->authService->refreshToken($user);

            expect($newToken)->toBeString()
                ->and($newToken)->not->toBe($oldToken)
                ->and($user->tokens()->count())->toBe(1);
        });

        it('revokes all existing tokens', function () {
            $user = User::factory()->create();
            $user->createToken('token-1');
            $user->createToken('token-2');
            $user->createToken('token-3');

            expect($user->tokens()->count())->toBe(3);

            $this->authService->refreshToken($user);

            expect($user->tokens()->count())->toBe(1);
        });
    });

    describe('verifyEmail', function () {
        it('verifies email with correct hash', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'email_verified_at' => null,
            ]);

            $hash = sha1($user->email);

            $result = $this->authService->verifyEmail($user, $hash);

            expect($result)->toBeTrue();
            $user->refresh();
            expect($user->hasVerifiedEmail())->toBeTrue();
        });

        it('rejects verification with incorrect hash', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'email_verified_at' => null,
            ]);

            $result = $this->authService->verifyEmail($user, 'invalid-hash');

            expect($result)->toBeFalse();
            $user->refresh();
            expect($user->hasVerifiedEmail())->toBeFalse();
        });

        it('activates tenant on first email verification', function () {
            $tenant = Tenant::factory()->create([
                'status' => TenantStatus::PENDING,
            ]);

            $user = User::factory()->create([
                'tenant_id' => $tenant->id,
                'email' => 'test@example.com',
                'email_verified_at' => null,
            ]);

            $hash = sha1($user->email);
            $this->authService->verifyEmail($user, $hash);

            $tenant->refresh();
            expect($tenant->status)->toBe(TenantStatus::ACTIVE);
        });

        it('does not change already verified email', function () {
            $originalTime = now()->subDay();
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'email_verified_at' => $originalTime,
            ]);

            $hash = sha1($user->email);
            $this->authService->verifyEmail($user, $hash);

            $user->refresh();
            expect($user->email_verified_at->timestamp)->toBe($originalTime->timestamp);
        });
    });

    describe('resendVerification', function () {
        it('sends verification email for unverified user', function () {
            Notification::fake();

            $user = User::factory()->create([
                'email' => 'test@example.com',
                'email_verified_at' => null,
            ]);

            $this->authService->resendVerification($user);

            Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
        });

        it('does not send email for already verified user', function () {
            Notification::fake();

            $user = User::factory()->create([
                'email' => 'test@example.com',
                'email_verified_at' => now(),
            ]);

            $this->authService->resendVerification($user);

            Notification::assertNothingSent();
        });
    });

    describe('security', function () {
        it('uses bcrypt for password hashing', function () {
            $user = User::factory()->create([
                'password' => 'testpassword',
            ]);

            // Bcrypt hashes start with $2y$
            expect(str_starts_with($user->password, '$2y$'))->toBeTrue();
        });

        it('does not expose password in array conversion', function () {
            $user = User::factory()->create([
                'password' => 'testpassword',
            ]);

            $array = $user->toArray();

            expect($array)->not->toHaveKey('password');
        });

        it('does not expose mfa_secret in array conversion', function () {
            $user = User::factory()->create([
                'mfa_enabled' => true,
                'mfa_secret' => encrypt('SECRET'),
            ]);

            $array = $user->toArray();

            expect($array)->not->toHaveKey('mfa_secret');
        });
    });
});
