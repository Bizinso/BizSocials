<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;

describe('POST /api/v1/auth/forgot-password', function () {
    it('sends password reset link to valid email', function () {
        Notification::fake();

        $user = User::factory()->active()->verified()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Password reset link sent to your email',
            ]);

        Notification::assertSentTo($user, ResetPassword::class);
    });

    it('fails when email does not exist', function () {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when email is missing', function () {
        $response = $this->postJson('/api/v1/auth/forgot-password', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when email is invalid format', function () {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'invalid-email',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });
});

describe('POST /api/v1/auth/reset-password', function () {
    it('resets password with valid token', function () {
        $user = User::factory()->active()->verified()->create([
            'email' => 'test@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Password reset successfully',
            ]);

        // Verify password was changed
        $user->refresh();
        expect(Hash::check('newpassword123', $user->password))->toBeTrue();
    });

    it('fails with invalid token', function () {
        User::factory()->active()->verified()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertUnprocessable();
    });

    it('fails when email is missing', function () {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'some-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when token is missing', function () {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    });

    it('fails when password is too short', function () {
        $user = User::factory()->active()->verified()->create([
            'email' => 'test@example.com',
        ]);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => $token,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('fails when password confirmation does not match', function () {
        $user = User::factory()->active()->verified()->create([
            'email' => 'test@example.com',
        ]);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('revokes all tokens after password reset', function () {
        $user = User::factory()->active()->verified()->create([
            'email' => 'test@example.com',
        ]);

        // Create some tokens
        $user->createToken('token-1');
        $user->createToken('token-2');
        expect($user->tokens()->count())->toBe(2);

        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        // All tokens should be revoked
        expect($user->tokens()->count())->toBe(0);
    });
});

describe('POST /api/v1/auth/change-password', function () {
    it('changes password for authenticated user', function () {
        $user = User::factory()->active()->verified()->create([
            'password' => 'oldpassword123',
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Password changed successfully',
            ]);

        // Verify password was changed
        $user->refresh();
        expect(Hash::check('newpassword123', $user->password))->toBeTrue();
    });

    it('fails when not authenticated', function () {
        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertUnauthorized();
    });

    it('fails when current password is incorrect', function () {
        $user = User::factory()->active()->verified()->create([
            'password' => 'oldpassword123',
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    });

    it('fails when new password is too short', function () {
        $user = User::factory()->active()->verified()->create([
            'password' => 'oldpassword123',
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'oldpassword123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('fails when new password is same as current', function () {
        $user = User::factory()->active()->verified()->create([
            'password' => 'oldpassword123',
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'oldpassword123',
            'password' => 'oldpassword123',
            'password_confirmation' => 'oldpassword123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('fails when password confirmation does not match', function () {
        $user = User::factory()->active()->verified()->create([
            'password' => 'oldpassword123',
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });
});
