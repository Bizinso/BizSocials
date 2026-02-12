<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('POST /api/v1/auth/logout', function () {
    it('logs out authenticated user', function () {
        $user = User::factory()->active()->verified()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
    });

    it('fails when user is not authenticated', function () {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertUnauthorized();
    });

    it('revokes the current token', function () {
        $user = User::factory()->active()->verified()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk();

        // Token should be revoked
        expect($user->tokens()->count())->toBe(0);
    });
});

describe('POST /api/v1/auth/refresh', function () {
    it('refreshes token for authenticated user', function () {
        $user = User::factory()->active()->verified()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/refresh');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'token_type',
                    'expires_in',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token_type' => 'Bearer',
                ],
            ]);
    });

    it('fails when user is not authenticated', function () {
        $response = $this->postJson('/api/v1/auth/refresh');

        $response->assertUnauthorized();
    });

    it('revokes all old tokens', function () {
        $user = User::factory()->active()->verified()->create();
        // Create multiple tokens
        $user->createToken('token-1');
        $user->createToken('token-2');
        expect($user->tokens()->count())->toBe(2);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/refresh');

        // Old tokens should be deleted, new token created
        expect($user->tokens()->count())->toBe(1);
    });
});
