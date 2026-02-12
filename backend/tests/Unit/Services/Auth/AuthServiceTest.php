<?php

declare(strict_types=1);

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
use App\Services\Auth\AuthService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->authService = app(AuthService::class);
});

describe('AuthService::login', function () {
    it('logs in user with valid credentials', function () {
        $user = User::factory()->active()->verified()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $data = new LoginData(
            email: 'test@example.com',
            password: 'password123',
        );

        $result = $this->authService->login($data);

        expect($result)->toHaveKeys(['user', 'token', 'expires_in']);
        expect($result['user']->id)->toBe($user->id);
        expect($result['token'])->toBeString();
        expect($result['expires_in'])->toBeInt();
    });

    it('throws exception for invalid email', function () {
        User::factory()->active()->verified()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $data = new LoginData(
            email: 'wrong@example.com',
            password: 'password123',
        );

        $this->authService->login($data);
    })->throws(ValidationException::class);

    it('throws exception for invalid password', function () {
        User::factory()->active()->verified()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $data = new LoginData(
            email: 'test@example.com',
            password: 'wrongpassword',
        );

        $this->authService->login($data);
    })->throws(ValidationException::class);

    it('throws exception for suspended user', function () {
        User::factory()->suspended()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $data = new LoginData(
            email: 'test@example.com',
            password: 'password123',
        );

        $this->authService->login($data);
    })->throws(ValidationException::class);

    it('records login time', function () {
        $user = User::factory()->active()->verified()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
            'last_login_at' => null,
        ]);

        $data = new LoginData(
            email: 'test@example.com',
            password: 'password123',
        );

        $this->authService->login($data);

        $user->refresh();
        expect($user->last_login_at)->not->toBeNull();
    });

    it('returns longer expiry with remember flag', function () {
        User::factory()->active()->verified()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
            'mfa_enabled' => false,
        ]);

        $dataWithRemember = new LoginData(
            email: 'test@example.com',
            password: 'password123',
            remember: true,
        );

        $dataWithoutRemember = new LoginData(
            email: 'test@example.com',
            password: 'password123',
            remember: false,
        );

        $resultWithRemember = $this->authService->login($dataWithRemember);

        // Reset tokens for next test
        User::where('email', 'test@example.com')->first()->tokens()->delete();

        $resultWithoutRemember = $this->authService->login($dataWithoutRemember);

        expect($resultWithRemember['expires_in'])->toBeGreaterThan($resultWithoutRemember['expires_in']);
    });
});

describe('AuthService::register', function () {
    it('creates a new user', function () {
        Event::fake([Registered::class, TenantCreated::class]);

        $data = new RegisterData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
            password_confirmation: 'password123',
        );

        $user = $this->authService->register($data);

        expect($user)->toBeInstanceOf(User::class);
        expect($user->name)->toBe('John Doe');
        expect($user->email)->toBe('john@example.com');
        expect($user->status)->toBe(UserStatus::ACTIVE);

        Event::assertDispatched(Registered::class);
    });

    it('creates a new tenant with deterministic fields when no tenant_id provided', function () {
        Event::fake([Registered::class, TenantCreated::class]);
        $tenantCount = Tenant::count();

        $data = new RegisterData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
            password_confirmation: 'password123',
        );

        $user = $this->authService->register($data);

        expect(Tenant::count())->toBe($tenantCount + 1);
        expect($user->tenant_id)->not->toBeNull();
        expect($user->role_in_tenant)->toBe(TenantRole::OWNER);

        $tenant = Tenant::find($user->tenant_id);
        expect($tenant->name)->toBe("John Doe's Organization");
        expect($tenant->type)->toBe(TenantType::INDIVIDUAL);
        expect($tenant->status)->toBe(TenantStatus::PENDING);
        expect($tenant->slug)->toStartWith('john-does-organization-');
    });

    it('sets owner_user_id on newly created tenant', function () {
        Event::fake([Registered::class, TenantCreated::class]);

        $data = new RegisterData(
            name: 'Jane Smith',
            email: 'jane@example.com',
            password: 'password123',
            password_confirmation: 'password123',
        );

        $user = $this->authService->register($data);

        $tenant = Tenant::find($user->tenant_id);
        expect($tenant->owner_user_id)->toBe($user->id);
    });

    it('dispatches TenantCreated event when new tenant is created', function () {
        Event::fake([Registered::class, TenantCreated::class]);

        $data = new RegisterData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
            password_confirmation: 'password123',
        );

        $this->authService->register($data);

        Event::assertDispatched(TenantCreated::class, function (TenantCreated $event) {
            return $event->tenant->name === "John Doe's Organization";
        });
    });

    it('creates TenantOnboarding record when new tenant is created', function () {
        Event::fake([Registered::class, TenantCreated::class]);

        $data = new RegisterData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
            password_confirmation: 'password123',
        );

        $user = $this->authService->register($data);

        $onboarding = TenantOnboarding::where('tenant_id', $user->tenant_id)->first();
        expect($onboarding)->not->toBeNull();
        expect($onboarding->current_step)->toBe('account_created');
        expect($onboarding->steps_completed)->toBe(['account_created']);
        expect($onboarding->started_at)->not->toBeNull();
        expect($onboarding->completed_at)->toBeNull();
    });

    it('does not create TenantOnboarding when joining existing tenant', function () {
        Event::fake([Registered::class, TenantCreated::class]);
        $tenant = Tenant::factory()->create();
        $onboardingCountBefore = TenantOnboarding::count();

        $data = new RegisterData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
            password_confirmation: 'password123',
            tenant_id: $tenant->id,
        );

        $this->authService->register($data);

        expect(TenantOnboarding::count())->toBe($onboardingCountBefore);
    });

    it('does not dispatch TenantCreated when joining existing tenant', function () {
        Event::fake([Registered::class, TenantCreated::class]);
        $tenant = Tenant::factory()->create();

        $data = new RegisterData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
            password_confirmation: 'password123',
            tenant_id: $tenant->id,
        );

        $this->authService->register($data);

        Event::assertNotDispatched(TenantCreated::class);
    });

    it('uses existing tenant when tenant_id provided', function () {
        Event::fake([Registered::class, TenantCreated::class]);
        $tenant = Tenant::factory()->create();

        $data = new RegisterData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
            password_confirmation: 'password123',
            tenant_id: $tenant->id,
        );

        $user = $this->authService->register($data);

        expect($user->tenant_id)->toBe($tenant->id);
        expect($user->role_in_tenant)->toBe(TenantRole::MEMBER);
    });

    it('hashes the password', function () {
        Event::fake([Registered::class, TenantCreated::class]);

        $data = new RegisterData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
            password_confirmation: 'password123',
        );

        $user = $this->authService->register($data);

        expect($user->password)->not->toBe('password123');
        expect(Hash::check('password123', $user->password))->toBeTrue();
    });
});

describe('AuthService::logout', function () {
    it('revokes current token', function () {
        $user = User::factory()->active()->verified()->create();
        $user->createToken('test-token');

        expect($user->tokens()->count())->toBe(1);

        // Simulate authenticated request
        $token = $user->tokens()->first();
        $user->withAccessToken($token);

        $this->authService->logout($user);

        expect($user->tokens()->count())->toBe(0);
    });
});

describe('AuthService::refreshToken', function () {
    it('revokes all old tokens and creates new one', function () {
        $user = User::factory()->active()->verified()->create();
        $user->createToken('token-1');
        $user->createToken('token-2');

        expect($user->tokens()->count())->toBe(2);

        $newToken = $this->authService->refreshToken($user);

        expect($newToken)->toBeString();
        expect($user->tokens()->count())->toBe(1);
    });
});

describe('AuthService::verifyEmail', function () {
    it('verifies email with valid hash', function () {
        $user = User::factory()->unverified()->create([
            'email' => 'test@example.com',
        ]);

        $hash = sha1('test@example.com');

        $result = $this->authService->verifyEmail($user, $hash);

        expect($result)->toBeTrue();
        $user->refresh();
        expect($user->hasVerifiedEmail())->toBeTrue();
    });

    it('returns false for invalid hash', function () {
        $user = User::factory()->unverified()->create([
            'email' => 'test@example.com',
        ]);

        $result = $this->authService->verifyEmail($user, 'invalid-hash');

        expect($result)->toBeFalse();
        $user->refresh();
        expect($user->hasVerifiedEmail())->toBeFalse();
    });

    it('does not re-verify already verified email', function () {
        $verifiedAt = now()->subDay();
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => $verifiedAt,
        ]);

        $hash = sha1('test@example.com');

        $result = $this->authService->verifyEmail($user, $hash);

        expect($result)->toBeTrue();
        $user->refresh();
        // Timestamp should not have changed
        expect($user->email_verified_at->format('Y-m-d H:i:s'))->toBe($verifiedAt->format('Y-m-d H:i:s'));
    });

    it('activates tenant when verifying email for PENDING tenant', function () {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::PENDING]);
        TenantOnboarding::create([
            'tenant_id' => $tenant->id,
            'current_step' => 'email_verified',
            'steps_completed' => ['account_created'],
            'started_at' => now(),
        ]);
        $user = User::factory()->unverified()->create([
            'email' => 'test@example.com',
            'tenant_id' => $tenant->id,
        ]);

        $hash = sha1('test@example.com');
        $result = $this->authService->verifyEmail($user, $hash);

        expect($result)->toBeTrue();
        $tenant->refresh();
        expect($tenant->status)->toBe(TenantStatus::ACTIVE);
    });

    it('advances onboarding to email_verified on verification', function () {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::PENDING]);
        $onboarding = TenantOnboarding::create([
            'tenant_id' => $tenant->id,
            'current_step' => 'email_verified',
            'steps_completed' => ['account_created'],
            'started_at' => now(),
        ]);
        $user = User::factory()->unverified()->create([
            'email' => 'test@example.com',
            'tenant_id' => $tenant->id,
        ]);

        $hash = sha1('test@example.com');
        $this->authService->verifyEmail($user, $hash);

        $onboarding->refresh();
        expect($onboarding->steps_completed)->toContain('email_verified');
        expect($onboarding->current_step)->toBe('organization_completed');
    });

    it('does not re-activate already active tenant on verification', function () {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->unverified()->create([
            'email' => 'test@example.com',
            'tenant_id' => $tenant->id,
        ]);

        $hash = sha1('test@example.com');
        $this->authService->verifyEmail($user, $hash);

        $tenant->refresh();
        expect($tenant->status)->toBe(TenantStatus::ACTIVE);
    });
});
