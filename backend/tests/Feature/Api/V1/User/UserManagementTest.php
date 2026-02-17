<?php

declare(strict_types=1);

use App\Enums\User\TenantRole;
use App\Enums\User\UserStatus;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->admin = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::ADMIN,
    ]);
    $this->member = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::MEMBER,
    ]);
});

describe('GET /api/v1/tenants/current/users', function () {
    it('returns list of users for admin', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/tenants/current/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'email',
                        'name',
                        'role_in_tenant',
                        'status',
                    ],
                ],
                'meta',
                'links',
            ]);

        expect($response->json('data'))->toHaveCount(3); // owner, admin, member
    });

    it('returns list of users for owner', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/tenants/current/users');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(3);
    });

    it('denies access to members', function () {
        Sanctum::actingAs($this->member);

        $response = $this->getJson('/api/v1/tenants/current/users');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Forbidden. Admin access required.',
            ]);
    });

    it('filters users by role', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/tenants/current/users?role=owner');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.role_in_tenant'))->toBe('owner');
    });

    it('searches users by name', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/tenants/current/users?search=' . urlencode($this->member->name));

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/tenants/current/users');

        $response->assertStatus(401);
    });
});

describe('POST /api/v1/tenants/current/users', function () {
    it('creates a new user as admin', function () {
        Sanctum::actingAs($this->admin);

        $userData = [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'password' => 'password123',
            'role_in_tenant' => TenantRole::MEMBER->value,
        ];

        $response = $this->postJson('/api/v1/tenants/current/users', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'email',
                    'name',
                    'role_in_tenant',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'tenant_id' => $this->tenant->id,
        ]);
    });

    it('creates a new user as owner', function () {
        Sanctum::actingAs($this->owner);

        $userData = [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'role_in_tenant' => TenantRole::ADMIN->value,
        ];

        $response = $this->postJson('/api/v1/tenants/current/users', $userData);

        $response->assertStatus(201);
    });

    it('denies user creation for members', function () {
        Sanctum::actingAs($this->member);

        $userData = [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'role_in_tenant' => TenantRole::MEMBER->value,
        ];

        $response = $this->postJson('/api/v1/tenants/current/users', $userData);

        $response->assertStatus(403);
    });

    it('validates required fields', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/tenants/current/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'name', 'role_in_tenant']);
    });

    it('validates email format', function () {
        Sanctum::actingAs($this->admin);

        $userData = [
            'email' => 'invalid-email',
            'name' => 'New User',
            'role_in_tenant' => TenantRole::MEMBER->value,
        ];

        $response = $this->postJson('/api/v1/tenants/current/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('prevents duplicate email in same tenant', function () {
        Sanctum::actingAs($this->admin);

        $userData = [
            'email' => $this->member->email,
            'name' => 'Duplicate User',
            'role_in_tenant' => TenantRole::MEMBER->value,
        ];

        $response = $this->postJson('/api/v1/tenants/current/users', $userData);

        $response->assertStatus(422);
    });
});

describe('GET /api/v1/tenants/current/users/{userId}', function () {
    it('returns user details for admin', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/tenants/current/users/{$this->member->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->member->id,
                    'email' => $this->member->email,
                    'name' => $this->member->name,
                ],
            ]);
    });

    it('returns 404 for non-existent user', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/tenants/current/users/non-existent-id');

        $response->assertStatus(404);
    });

    it('denies access to members', function () {
        Sanctum::actingAs($this->member);

        $response = $this->getJson("/api/v1/tenants/current/users/{$this->admin->id}");

        $response->assertStatus(403);
    });
});

describe('PUT /api/v1/tenants/current/users/{userId}/role', function () {
    it('updates user role as admin', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/tenants/current/users/{$this->member->id}/role", [
            'role_in_tenant' => TenantRole::ADMIN->value,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->member->id,
                    'role_in_tenant' => TenantRole::ADMIN->value,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->member->id,
            'role_in_tenant' => TenantRole::ADMIN->value,
        ]);
    });

    it('updates user role as owner', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->putJson("/api/v1/tenants/current/users/{$this->member->id}/role", [
            'role_in_tenant' => TenantRole::OWNER->value,
        ]);

        $response->assertStatus(200);
    });

    it('prevents non-owner from assigning owner role', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/tenants/current/users/{$this->member->id}/role", [
            'role_in_tenant' => TenantRole::OWNER->value,
        ]);

        $response->assertStatus(422);
    });

    it('prevents user from changing own role', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/tenants/current/users/{$this->admin->id}/role", [
            'role_in_tenant' => TenantRole::MEMBER->value,
        ]);

        $response->assertStatus(422);
    });

    it('denies access to members', function () {
        Sanctum::actingAs($this->member);

        $response = $this->putJson("/api/v1/tenants/current/users/{$this->member->id}/role", [
            'role_in_tenant' => TenantRole::ADMIN->value,
        ]);

        $response->assertStatus(403);
    });

    it('validates role field', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/tenants/current/users/{$this->member->id}/role", [
            'role_in_tenant' => 'invalid_role',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role_in_tenant']);
    });
});

describe('DELETE /api/v1/tenants/current/users/{userId}', function () {
    it('removes user as admin', function () {
        Sanctum::actingAs($this->admin);

        $userToRemove = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        $response = $this->deleteJson("/api/v1/tenants/current/users/{$userToRemove->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User removed successfully',
            ]);

        $this->assertSoftDeleted('users', ['id' => $userToRemove->id]);
    });

    it('removes user as owner', function () {
        Sanctum::actingAs($this->owner);

        $userToRemove = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        $response = $this->deleteJson("/api/v1/tenants/current/users/{$userToRemove->id}");

        $response->assertStatus(200);
    });

    it('prevents user from removing themselves', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/tenants/current/users/{$this->admin->id}");

        $response->assertStatus(422);
    });

    it('prevents removing the last owner', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/tenants/current/users/{$this->owner->id}");

        $response->assertStatus(422);
    });

    it('denies access to members', function () {
        Sanctum::actingAs($this->member);

        $response = $this->deleteJson("/api/v1/tenants/current/users/{$this->member->id}");

        $response->assertStatus(403);
    });

    it('returns 404 for non-existent user', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson('/api/v1/tenants/current/users/non-existent-id');

        $response->assertStatus(404);
    });
});

describe('GET /api/v1/tenants/current/permissions', function () {
    it('returns permissions for owner', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/tenants/current/permissions');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => 'owner',
                    'permissions' => [
                        'manage_users',
                        'manage_billing',
                        'delete_tenant',
                        'manage_workspaces',
                        'view_analytics',
                        'manage_content',
                    ],
                ],
            ]);
    });

    it('returns permissions for admin', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/tenants/current/permissions');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => 'admin',
                ],
            ]);

        $permissions = $response->json('data.permissions');
        expect($permissions)->toContain('manage_users')
            ->and($permissions)->not->toContain('manage_billing');
    });

    it('returns permissions for member', function () {
        Sanctum::actingAs($this->member);

        $response = $this->getJson('/api/v1/tenants/current/permissions');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => 'member',
                ],
            ]);

        $permissions = $response->json('data.permissions');
        expect($permissions)->toContain('view_analytics')
            ->and($permissions)->not->toContain('manage_users');
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/tenants/current/permissions');

        $response->assertStatus(401);
    });
});

describe('Authorization Rules', function () {
    it('enforces admin middleware on user management endpoints', function () {
        Sanctum::actingAs($this->member);

        // Test all admin-only endpoints
        $endpoints = [
            ['GET', '/api/v1/tenants/current/users'],
            ['POST', '/api/v1/tenants/current/users'],
            ['GET', "/api/v1/tenants/current/users/{$this->admin->id}"],
            ['PUT', "/api/v1/tenants/current/users/{$this->admin->id}/role"],
            ['DELETE', "/api/v1/tenants/current/users/{$this->admin->id}"],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = match ($method) {
                'GET' => $this->getJson($url),
                'POST' => $this->postJson($url, []),
                'PUT' => $this->putJson($url, []),
                'DELETE' => $this->deleteJson($url),
            };

            $response->assertStatus(403);
        }
    });

    it('allows all authenticated users to view permissions', function () {
        Sanctum::actingAs($this->member);

        $response = $this->getJson('/api/v1/tenants/current/permissions');

        $response->assertStatus(200);
    });

    it('prevents cross-tenant access', function () {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'role_in_tenant' => TenantRole::ADMIN,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/tenants/current/users/{$otherUser->id}");

        $response->assertStatus(404);
    });
});
