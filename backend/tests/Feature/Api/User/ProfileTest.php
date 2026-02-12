<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('GET /api/v1/user', function () {
    it('returns current user profile', function () {
        $user = User::factory()->active()->verified()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/user');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'avatar_url',
                    'timezone',
                    'status',
                    'email_verified_at',
                    'created_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'User profile retrieved',
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ]);
    });

    it('fails when not authenticated', function () {
        $response = $this->getJson('/api/v1/user');

        $response->assertUnauthorized();
    });
});

describe('PUT /api/v1/user', function () {
    it('updates user profile', function () {
        $user = User::factory()->active()->verified()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/user', [
            'name' => 'Updated Name',
            'timezone' => 'America/New_York',
            'phone' => '+1234567890',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'name' => 'Updated Name',
                    'timezone' => 'America/New_York',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'timezone' => 'America/New_York',
            'phone' => '+1234567890',
        ]);
    });

    it('updates only provided fields', function () {
        $user = User::factory()->active()->verified()->create([
            'name' => 'Original Name',
            'timezone' => 'UTC',
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/user', [
            'name' => 'Updated Name',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'timezone' => 'UTC', // Should remain unchanged
        ]);
    });

    it('fails when not authenticated', function () {
        $response = $this->putJson('/api/v1/user', [
            'name' => 'Updated Name',
        ]);

        $response->assertUnauthorized();
    });

    it('fails with invalid timezone', function () {
        $user = User::factory()->active()->verified()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/user', [
            'timezone' => 'Invalid/Timezone',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['timezone']);
    });

    it('updates job_title in settings', function () {
        $user = User::factory()->active()->verified()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/user', [
            'job_title' => 'Software Engineer',
        ]);

        $user->refresh();
        expect($user->getSetting('job_title'))->toBe('Software Engineer');
    });
});

describe('PUT /api/v1/user/settings', function () {
    it('updates user settings', function () {
        $user = User::factory()->active()->verified()->create([
            'settings' => ['theme' => 'light'],
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/user/settings', [
            'settings' => [
                'theme' => 'dark',
                'language' => 'en',
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Settings updated successfully',
            ]);

        $user->refresh();
        expect($user->settings['theme'])->toBe('dark');
        expect($user->settings['language'])->toBe('en');
    });

    it('merges with existing settings', function () {
        $user = User::factory()->active()->verified()->create([
            'settings' => [
                'theme' => 'light',
                'notifications' => ['email' => true],
            ],
        ]);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/user/settings', [
            'settings' => [
                'language' => 'fr',
            ],
        ]);

        $user->refresh();
        expect($user->settings['theme'])->toBe('light'); // Preserved
        expect($user->settings['language'])->toBe('fr'); // Added
        expect($user->settings['notifications'])->toBe(['email' => true]); // Preserved
    });

    it('fails when not authenticated', function () {
        $response = $this->putJson('/api/v1/user/settings', [
            'settings' => ['theme' => 'dark'],
        ]);

        $response->assertUnauthorized();
    });

    it('fails when settings is not an array', function () {
        $user = User::factory()->active()->verified()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/user/settings', [
            'settings' => 'invalid',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['settings']);
    });

    it('fails when settings is missing', function () {
        $user = User::factory()->active()->verified()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/user/settings', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['settings']);
    });
});

describe('DELETE /api/v1/user', function () {
    it('deletes user account', function () {
        $user = User::factory()->active()->verified()->create([
            'password' => 'password123',
        ]);
        $userId = $user->id;
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/user', [
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Account deleted successfully',
            ]);

        // User should be soft deleted
        $this->assertSoftDeleted('users', ['id' => $userId]);
    });

    it('fails when not authenticated', function () {
        $response = $this->deleteJson('/api/v1/user', [
            'password' => 'password123',
        ]);

        $response->assertUnauthorized();
    });

    it('fails with incorrect password', function () {
        $user = User::factory()->active()->verified()->create([
            'password' => 'password123',
        ]);
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/user', [
            'password' => 'wrongpassword',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('fails when password is missing', function () {
        $user = User::factory()->active()->verified()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/user', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('revokes all tokens on deletion', function () {
        $user = User::factory()->active()->verified()->create([
            'password' => 'password123',
        ]);

        // Create some tokens
        $user->createToken('token-1');
        $user->createToken('token-2');
        expect($user->tokens()->count())->toBe(2);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/user', [
            'password' => 'password123',
        ]);

        // All tokens should be revoked
        expect($user->tokens()->count())->toBe(0);
    });
});
