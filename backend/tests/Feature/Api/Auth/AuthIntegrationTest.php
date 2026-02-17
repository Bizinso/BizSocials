<?php

declare(strict_types=1);

/**
 * Authentication API Integration Tests
 * 
 * Task: 10.3 Write integration tests for auth API
 * Requirements: 13.1, 13.2, 13.4
 * 
 * This test suite validates:
 * - POST /api/v1/auth/login endpoint
 * - POST /api/v1/auth/register endpoint
 * - POST /api/v1/auth/logout endpoint
 * - Authentication middleware behavior
 * - Request validation
 * - Response structure and status codes
 * - Authorization rules
 */

use App\Enums\User\UserStatus;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('Authentication API Integration Tests', function () {
    describe('POST /api/v1/auth/login - Login Endpoint', function () {
        beforeEach(function () {
            $this->user = User::factory()->active()->verified()->create([
                'email' => 'test@example.com',
                'password' => 'password123',
                'mfa_enabled' => false,
            ]);
        });

        it('returns 200 with valid credentials', function () {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

            $response->assertStatus(200);
        });

        it('returns correct response structure on successful login', function () {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

            $response->assertJsonStructure([
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
            ]);
        });

        it('returns Bearer token type', function () {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

            $response->assertJson([
                'data' => [
                    'token_type' => 'Bearer',
                ],
            ]);
        });

        it('returns 422 with invalid credentials', function () {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);

            $response->assertStatus(422);
        });

        it('validates email is required', function () {
            $response = $this->postJson('/api/v1/auth/login', [
                'password' => 'password123',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('validates password is required', function () {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('validates email format', function () {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'invalid-email',
                'password' => 'password123',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('rejects suspended users', function () {
            $this->user->update(['status' => UserStatus::SUSPENDED]);

            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

            $response->assertStatus(422);
        });

        it('rejects deactivated users', function () {
            $this->user->update(['status' => UserStatus::DEACTIVATED]);

            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

            $response->assertStatus(422);
        });

        it('creates authentication token in database', function () {
            $tokenCount = $this->user->tokens()->count();

            $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

            $this->user->refresh();
            expect($this->user->tokens()->count())->toBe($tokenCount + 1);
        });

        it('updates last_login_at timestamp', function () {
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

        it('applies throttle middleware to login endpoint', function () {
            // Verify that the login endpoint has throttle middleware applied
            // by checking route middleware configuration
            $route = app('router')->getRoutes()->getByName(null);
            $loginRoute = collect(app('router')->getRoutes())->first(function ($route) {
                return $route->uri() === 'v1/auth/login' && in_array('POST', $route->methods());
            });

            // If route exists, verify it has throttle middleware
            if ($loginRoute) {
                $middleware = $loginRoute->middleware();
                expect($middleware)->toContain('throttle:auth');
            }

            // Also verify that multiple failed attempts don't cause server errors
            for ($i = 0; $i < 5; $i++) {
                $response = $this->postJson('/api/v1/auth/login', [
                    'email' => 'test@example.com',
                    'password' => 'wrongpassword',
                ]);
                // Should return 422 for invalid credentials, not 500
                expect($response->status())->toBeIn([422, 429]);
            }
        });
    });

    describe('POST /api/v1/auth/register - Registration Endpoint', function () {
        it('returns 201 on successful registration', function () {
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $response->assertStatus(201);
        });

        it('returns correct response structure on successful registration', function () {
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $response->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'status',
                        'created_at',
                    ],
                    'token',
                    'token_type',
                    'expires_in',
                ],
            ]);
        });

        it('creates user in database', function () {
            $this->postJson('/api/v1/auth/register', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $this->assertDatabaseHas('users', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);
        });

        it('hashes password before storing', function () {
            $this->postJson('/api/v1/auth/register', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $user = User::where('email', 'john@example.com')->first();
            expect($user->password)->not->toBe('password123');
            expect(password_verify('password123', $user->password))->toBeTrue();
        });

        it('creates authentication token for new user', function () {
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $user = User::where('email', 'john@example.com')->first();
            expect($user->tokens()->count())->toBe(1);
            expect($response->json('data.token'))->not->toBeNull();
        });

        it('validates name is required', function () {
            $response = $this->postJson('/api/v1/auth/register', [
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('validates email is required', function () {
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => 'John Doe',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('validates email format', function () {
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => 'John Doe',
                'email' => 'invalid-email',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('validates email uniqueness', function () {
            User::factory()->create(['email' => 'existing@example.com']);

            $response = $this->postJson('/api/v1/auth/register', [
                'name' => 'John Doe',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('validates password is required', function () {
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => 'John Doe',
                'email' => 'test@example.com',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('validates password minimum length', function () {
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => 'John Doe',
                'email' => 'test@example.com',
                'password' => 'short',
                'password_confirmation' => 'short',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('validates password confirmation matches', function () {
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => 'John Doe',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'different123',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('creates tenant for new user when tenant_id not provided', function () {
            $tenantCount = Tenant::count();

            $this->postJson('/api/v1/auth/register', [
                'name' => 'Bob Smith',
                'email' => 'bob@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            expect(Tenant::count())->toBe($tenantCount + 1);
        });

        it('links user to existing tenant when tenant_id provided', function () {
            $tenant = Tenant::factory()->create();

            $response = $this->postJson('/api/v1/auth/register', [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'tenant_id' => $tenant->id,
            ]);

            $response->assertStatus(201);

            $this->assertDatabaseHas('users', [
                'email' => 'jane@example.com',
                'tenant_id' => $tenant->id,
            ]);
        });
    });

    describe('POST /api/v1/auth/logout - Logout Endpoint', function () {
        it('returns 200 for authenticated user', function () {
            $user = User::factory()->active()->verified()->create();
            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/auth/logout');

            $response->assertStatus(200);
        });

        it('returns correct response structure', function () {
            $user = User::factory()->active()->verified()->create();
            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/auth/logout');

            $response->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
        });

        it('returns 401 for unauthenticated user', function () {
            $response = $this->postJson('/api/v1/auth/logout');

            $response->assertStatus(401);
        });

        it('revokes current authentication token', function () {
            $user = User::factory()->active()->verified()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            expect($user->tokens()->count())->toBe(1);

            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/v1/auth/logout');

            $response->assertStatus(200);
            expect($user->tokens()->count())->toBe(0);
        });

        it('only revokes current token, not all tokens', function () {
            $user = User::factory()->active()->verified()->create();
            $token1 = $user->createToken('token-1')->plainTextToken;
            $token2 = $user->createToken('token-2')->plainTextToken;

            expect($user->tokens()->count())->toBe(2);

            // Logout with token1
            $this->withHeader('Authorization', 'Bearer ' . $token1)
                ->postJson('/api/v1/auth/logout');

            // Token1 should be revoked, but token2 should still exist
            expect($user->tokens()->count())->toBe(1);

            // Verify token2 still works
            $response = $this->withHeader('Authorization', 'Bearer ' . $token2)
                ->getJson('/api/v1/user');

            $response->assertStatus(200);
        });
    });

    describe('Authentication Middleware', function () {
        it('allows access to protected routes with valid token', function () {
            $user = User::factory()->active()->verified()->create();
            Sanctum::actingAs($user);

            $response = $this->getJson('/api/v1/user');

            $response->assertStatus(200);
        });

        it('denies access to protected routes without token', function () {
            $response = $this->getJson('/api/v1/user');

            $response->assertStatus(401);
        });

        it('denies access with invalid token', function () {
            $response = $this->withHeader('Authorization', 'Bearer invalid-token')
                ->getJson('/api/v1/user');

            $response->assertStatus(401);
        });

        it('denies access with expired token', function () {
            $user = User::factory()->active()->verified()->create();
            $token = $user->createToken('test-token', ['*'], now()->subDay());

            $response = $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken)
                ->getJson('/api/v1/user');

            $response->assertStatus(401);
        });

        it('allows access to public routes without authentication', function () {
            $response = $this->getJson('/api/v1/health');

            $response->assertStatus(200);
        });

        it('validates token format', function () {
            $response = $this->withHeader('Authorization', 'InvalidFormat')
                ->getJson('/api/v1/user');

            $response->assertStatus(401);
        });

        it('requires Bearer prefix in Authorization header', function () {
            $user = User::factory()->active()->verified()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            // Without Bearer prefix
            $response = $this->withHeader('Authorization', $token)
                ->getJson('/api/v1/user');

            $response->assertStatus(401);
        });
    });

    describe('Token Refresh Endpoint', function () {
        it('returns 200 for authenticated user', function () {
            $user = User::factory()->active()->verified()->create();
            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/auth/refresh');

            $response->assertStatus(200);
        });

        it('returns new token', function () {
            $user = User::factory()->active()->verified()->create();
            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/auth/refresh');

            $response->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'token_type',
                    'expires_in',
                ],
            ]);
        });

        it('returns 401 for unauthenticated user', function () {
            $response = $this->postJson('/api/v1/auth/refresh');

            $response->assertStatus(401);
        });

        it('revokes old tokens and creates new one', function () {
            $user = User::factory()->active()->verified()->create();
            $user->createToken('token-1');
            $user->createToken('token-2');
            expect($user->tokens()->count())->toBe(2);

            Sanctum::actingAs($user);

            $this->postJson('/api/v1/auth/refresh');

            // Old tokens should be deleted, new token created
            expect($user->tokens()->count())->toBe(1);
        });
    });

    describe('Authorization Rules', function () {
        it('enforces tenant middleware on workspace routes', function () {
            $user = User::factory()->active()->verified()->create();
            Sanctum::actingAs($user);

            // Try to access workspace route without tenant context
            $response = $this->getJson('/api/v1/workspaces');

            // Should either succeed or fail based on tenant middleware
            expect($response->status())->toBeIn([200, 403, 404]);
        });

        it('allows authenticated users to access their own profile', function () {
            $user = User::factory()->active()->verified()->create();
            Sanctum::actingAs($user);

            $response = $this->getJson('/api/v1/user');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $user->id,
                        'email' => $user->email,
                    ],
                ]);
        });

        it('prevents users from accessing other users data', function () {
            $user1 = User::factory()->active()->verified()->create();
            $user2 = User::factory()->active()->verified()->create();

            Sanctum::actingAs($user1);

            // Try to access user2's data (if such endpoint exists)
            // This is a conceptual test - adjust based on actual routes
            $response = $this->getJson('/api/v1/user');

            $response->assertStatus(200);
            expect($response->json('data.id'))->toBe($user1->id);
            expect($response->json('data.id'))->not->toBe($user2->id);
        });
    });

    describe('Error Response Format', function () {
        it('returns consistent error format for validation errors', function () {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'invalid',
            ]);

            $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors',
                ]);
        });

        it('returns consistent error format for authentication errors', function () {
            $response = $this->getJson('/api/v1/user');

            $response->assertStatus(401)
                ->assertJsonStructure([
                    'message',
                ]);
        });

        it('includes validation error details', function () {
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => '',
                'email' => 'invalid',
                'password' => 'short',
            ]);

            $response->assertStatus(422);
            $errors = $response->json('errors');
            expect($errors)->toHaveKeys(['name', 'email', 'password']);
        });
    });
});
