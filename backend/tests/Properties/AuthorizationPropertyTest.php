<?php

declare(strict_types=1);

// Feature: platform-audit-and-testing, Property 24: Input Validation Universality
// Validates: Requirements 18.1

use App\Enums\User\TenantRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->userService = app(UserService::class);
});

describe('Authorization Property Tests', function () {
    it('property: unauthorized requests are consistently rejected across all protected endpoints', function () {
        $iterations = 50;

        for ($i = 0; $i < $iterations; $i++) {
            // Create a fresh tenant and users for each iteration
            $tenant = Tenant::factory()->create();
            $member = User::factory()->create([
                'tenant_id' => $tenant->id,
                'role_in_tenant' => TenantRole::MEMBER,
            ]);

            // Test various admin-only endpoints with member credentials
            Sanctum::actingAs($member);

            $adminEndpoints = [
                ['GET', '/api/v1/tenants/current/users'],
                ['POST', '/api/v1/tenants/current/users', [
                    'email' => "test{$i}@example.com",
                    'name' => "Test User {$i}",
                    'role_in_tenant' => TenantRole::MEMBER->value,
                ]],
            ];

            foreach ($adminEndpoints as $endpoint) {
                [$method, $url, $data] = array_pad($endpoint, 3, []);

                $response = match ($method) {
                    'GET' => $this->getJson($url),
                    'POST' => $this->postJson($url, $data),
                    'PUT' => $this->putJson($url, $data),
                    'DELETE' => $this->deleteJson($url),
                };

                // Property: All unauthorized requests must return 403
                expect($response->status())->toBe(403, "Iteration {$i}: {$method} {$url} should return 403 for member");
            }
        }
    })->group('property');

    it('property: permissions are enforced consistently for all roles', function () {
        $iterations = 50;

        for ($i = 0; $i < $iterations; $i++) {
            $tenant = Tenant::factory()->create();

            // Create users with different roles
            $owner = User::factory()->create([
                'tenant_id' => $tenant->id,
                'role_in_tenant' => TenantRole::OWNER,
            ]);
            $admin = User::factory()->create([
                'tenant_id' => $tenant->id,
                'role_in_tenant' => TenantRole::ADMIN,
            ]);
            $member = User::factory()->create([
                'tenant_id' => $tenant->id,
                'role_in_tenant' => TenantRole::MEMBER,
            ]);

            // Property: Owner always has all permissions
            $ownerPermissions = $this->userService->getPermissionsForRole(TenantRole::OWNER);
            expect($ownerPermissions)->toContain('manage_users')
                ->and($ownerPermissions)->toContain('manage_billing')
                ->and($ownerPermissions)->toContain('delete_tenant')
                ->and($ownerPermissions)->toContain('manage_workspaces')
                ->and($ownerPermissions)->toContain('view_analytics')
                ->and($ownerPermissions)->toContain('manage_content');

            // Property: Admin has subset of owner permissions
            $adminPermissions = $this->userService->getPermissionsForRole(TenantRole::ADMIN);
            expect($adminPermissions)->toContain('manage_users')
                ->and($adminPermissions)->toContain('manage_workspaces')
                ->and($adminPermissions)->not->toContain('manage_billing')
                ->and($adminPermissions)->not->toContain('delete_tenant');

            // Property: Member has minimal permissions
            $memberPermissions = $this->userService->getPermissionsForRole(TenantRole::MEMBER);
            expect($memberPermissions)->toContain('view_analytics')
                ->and($memberPermissions)->toContain('manage_content')
                ->and($memberPermissions)->not->toContain('manage_users')
                ->and($memberPermissions)->not->toContain('manage_billing');

            // Property: Permission hierarchy is consistent
            expect(count($ownerPermissions))->toBeGreaterThan(count($adminPermissions))
                ->and(count($adminPermissions))->toBeGreaterThan(count($memberPermissions));
        }
    })->group('property');

    it('property: role-based access control is transitive and consistent', function () {
        $iterations = 50;

        for ($i = 0; $i < $iterations; $i++) {
            $tenant = Tenant::factory()->create();

            $owner = User::factory()->create([
                'tenant_id' => $tenant->id,
                'role_in_tenant' => TenantRole::OWNER,
            ]);
            $admin = User::factory()->create([
                'tenant_id' => $tenant->id,
                'role_in_tenant' => TenantRole::ADMIN,
            ]);
            $member = User::factory()->create([
                'tenant_id' => $tenant->id,
                'role_in_tenant' => TenantRole::MEMBER,
            ]);

            // Property: If owner has permission, admin might have it
            // If admin has permission, member might have it
            // But if member doesn't have permission, admin/owner must have it
            $permissions = ['manage_users', 'manage_billing', 'view_analytics', 'manage_content'];

            foreach ($permissions as $permission) {
                $ownerHas = $this->userService->hasPermission($owner, $permission);
                $adminHas = $this->userService->hasPermission($admin, $permission);
                $memberHas = $this->userService->hasPermission($member, $permission);

                // Property: If member has permission, admin and owner must have it
                if ($memberHas) {
                    expect($adminHas)->toBeTrue("Admin should have {$permission} if member has it")
                        ->and($ownerHas)->toBeTrue("Owner should have {$permission} if member has it");
                }

                // Property: If admin has permission, owner must have it
                if ($adminHas) {
                    expect($ownerHas)->toBeTrue("Owner should have {$permission} if admin has it");
                }
            }
        }
    })->group('property');

    it('property: cross-tenant access is always denied', function () {
        $iterations = 50;

        for ($i = 0; $i < $iterations; $i++) {
            // Create two separate tenants
            $tenant1 = Tenant::factory()->create();
            $tenant2 = Tenant::factory()->create();

            $user1 = User::factory()->create([
                'tenant_id' => $tenant1->id,
                'role_in_tenant' => TenantRole::ADMIN,
            ]);
            $user2 = User::factory()->create([
                'tenant_id' => $tenant2->id,
                'role_in_tenant' => TenantRole::MEMBER,
            ]);

            // Property: User from tenant1 cannot access user from tenant2
            Sanctum::actingAs($user1);

            $response = $this->getJson("/api/v1/tenants/current/users/{$user2->id}");

            // Must return 404 (not 403) to not reveal existence of users in other tenants
            expect($response->status())->toBe(404, "Iteration {$i}: Cross-tenant access should return 404");
        }
    })->group('property');

    it('property: permission checks are idempotent', function () {
        $iterations = 100;

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role_in_tenant' => TenantRole::ADMIN,
        ]);

        $permissions = ['manage_users', 'manage_billing', 'view_analytics', 'unknown_permission'];

        for ($i = 0; $i < $iterations; $i++) {
            foreach ($permissions as $permission) {
                $result1 = $this->userService->hasPermission($user, $permission);
                $result2 = $this->userService->hasPermission($user, $permission);

                // Property: Multiple calls to hasPermission with same inputs return same result
                expect($result1)->toBe($result2, "Permission check for {$permission} should be idempotent");
            }
        }
    })->group('property');

    it('property: role updates immediately affect permissions', function () {
        $iterations = 50;

        for ($i = 0; $i < $iterations; $i++) {
            $tenant = Tenant::factory()->create();
            $owner = User::factory()->create([
                'tenant_id' => $tenant->id,
                'role_in_tenant' => TenantRole::OWNER,
            ]);
            $user = User::factory()->create([
                'tenant_id' => $tenant->id,
                'role_in_tenant' => TenantRole::MEMBER,
            ]);

            // Check permissions before role change
            $hadManageUsers = $this->userService->hasPermission($user, 'manage_users');
            expect($hadManageUsers)->toBeFalse();

            // Update role
            $this->userService->updateUserRole($user, TenantRole::ADMIN, $owner);

            // Refresh user from database
            $user->refresh();

            // Property: After role update, permissions must reflect new role
            $hasManageUsers = $this->userService->hasPermission($user, 'manage_users');
            expect($hasManageUsers)->toBeTrue("User should have manage_users after promotion to admin");
        }
    })->group('property');

    it('property: self-modification is always prevented', function () {
        $iterations = 50;

        for ($i = 0; $i < $iterations; $i++) {
            $tenant = Tenant::factory()->create();
            $admin = User::factory()->create([
                'tenant_id' => $tenant->id,
                'role_in_tenant' => TenantRole::ADMIN,
            ]);

            // Property: User cannot change their own role
            try {
                $this->userService->updateUserRole($admin, TenantRole::MEMBER, $admin);
                $this->fail('Should have thrown ValidationException');
            } catch (\Illuminate\Validation\ValidationException $e) {
                expect($e->getMessage())->toContain('cannot change your own role');
            }

            // Property: User cannot remove themselves
            try {
                $this->userService->removeUser($admin, $admin);
                $this->fail('Should have thrown ValidationException');
            } catch (\Illuminate\Validation\ValidationException $e) {
                expect($e->getMessage())->toContain('cannot remove yourself');
            }
        }
    })->group('property');
});
