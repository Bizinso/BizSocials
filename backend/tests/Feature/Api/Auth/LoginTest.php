<?php

declare(strict_types=1);

use App\Enums\User\UserStatus;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->active()->verified()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
        'mfa_enabled' => false,
    ]);
});

describe('POST /api/v1/auth/login', function () {
    it('logs in a user with valid credentials', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'avatar_url',
                        'timezone',
                        'status',
                        'email_verified_at',
                        'created_at',
                    ],
                    'token',
                    'token_type',
                    'expires_in',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'email' => 'test@example.com',
                    ],
                    'token_type' => 'Bearer',
                ],
            ]);
    });

    it('fails with invalid email', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('fails with invalid password', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when email is missing', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when password is missing', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('fails with invalid email format', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when user is suspended', function () {
        $this->user->update(['status' => UserStatus::SUSPENDED]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when user is deactivated', function () {
        $this->user->update(['status' => UserStatus::DEACTIVATED]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('updates last_login_at on successful login', function () {
        // Create a fresh user with no login history
        $user = User::factory()->active()->verified()->create([
            'email' => 'fresh@example.com',
            'password' => 'password123',
            'last_login_at' => null,
            'mfa_enabled' => false,
        ]);

        expect($user->last_login_at)->toBeNull();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'fresh@example.com',
            'password' => 'password123',
        ]);

        $user->refresh();
        expect($user->last_login_at)->not->toBeNull();
    });

    it('handles remember me option', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertOk();
        // With remember=true, expires_in should be longer (30 days = 2592000 seconds)
        $expiresIn = $response->json('data.expires_in');
        expect($expiresIn)->toBeGreaterThan(86400); // More than 24 hours
    });
});
