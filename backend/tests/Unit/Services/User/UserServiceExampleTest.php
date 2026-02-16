<?php

declare(strict_types=1);

use App\Enums\User\TenantRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\User\UserService;

/**
 * Example Unit Test for UserService
 * 
 * This test demonstrates:
 * 1. Pest PHP configuration is working correctly
 * 2. Testing a simple service method (getPermissionsForRole)
 * 3. Test database and factories are functioning properly
 * 
 * Requirements: 11.2
 */

describe('UserService Example Test', function () {
    beforeEach(function () {
        $this->service = app(UserService::class);
    });

    it('verifies Pest configuration is working', function () {
        // Simple assertion to verify Pest is configured correctly
        expect(true)->toBeTrue();
        expect(1 + 1)->toBe(2);
    });

    it('tests getPermissionsForRole method for owner role', function () {
        $permissions = $this->service->getPermissionsForRole(TenantRole::OWNER);

        expect($permissions)->toBeArray()
            ->and($permissions)->toContain('manage_users')
            ->and($permissions)->toContain('manage_billing')
            ->and($permissions)->toContain('delete_tenant')
            ->and($permissions)->toContain('manage_workspaces')
            ->and($permissions)->toContain('view_analytics')
            ->and($permissions)->toContain('manage_content');
    });

    it('tests getPermissionsForRole method for admin role', function () {
        $permissions = $this->service->getPermissionsForRole(TenantRole::ADMIN);

        expect($permissions)->toBeArray()
            ->and($permissions)->toContain('manage_users')
            ->and($permissions)->toContain('manage_workspaces')
            ->and($permissions)->toContain('view_analytics')
            ->and($permissions)->toContain('manage_content')
            ->and($permissions)->not->toContain('manage_billing')
            ->and($permissions)->not->toContain('delete_tenant');
    });

    it('tests getPermissionsForRole method for member role', function () {
        $permissions = $this->service->getPermissionsForRole(TenantRole::MEMBER);

        expect($permissions)->toBeArray()
            ->and($permissions)->toContain('view_analytics')
            ->and($permissions)->toContain('manage_content')
            ->and($permissions)->not->toContain('manage_users')
            ->and($permissions)->not->toContain('manage_billing');
    });

    it('verifies test database and User factory work correctly', function () {
        // Create a user using the factory
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Verify the user was created in the test database
        expect($user)->toBeInstanceOf(User::class)
            ->and($user->name)->toBe('Test User')
            ->and($user->email)->toBe('test@example.com')
            ->and($user->id)->not->toBeNull();

        // Verify we can query the user from the database
        $foundUser = User::where('email', 'test@example.com')->first();
        expect($foundUser)->not->toBeNull()
            ->and($foundUser->id)->toBe($user->id);
    });

    it('verifies test database and Tenant factory work correctly', function () {
        // Create a tenant using the factory
        $tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
        ]);

        // Verify the tenant was created in the test database
        expect($tenant)->toBeInstanceOf(Tenant::class)
            ->and($tenant->name)->toBe('Test Tenant')
            ->and($tenant->id)->not->toBeNull();

        // Create a user associated with this tenant
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        // Verify the relationship works
        expect($user->tenant_id)->toBe($tenant->id)
            ->and($user->tenant)->toBeInstanceOf(Tenant::class)
            ->and($user->tenant->id)->toBe($tenant->id);
    });

    it('verifies factory states work correctly', function () {
        // Test owner state
        $owner = User::factory()->owner()->create();
        expect($owner->role_in_tenant)->toBe(TenantRole::OWNER);

        // Test admin state
        $admin = User::factory()->admin()->create();
        expect($admin->role_in_tenant)->toBe(TenantRole::ADMIN);

        // Test member state
        $member = User::factory()->member()->create();
        expect($member->role_in_tenant)->toBe(TenantRole::MEMBER);

        // Test verified state
        $verifiedUser = User::factory()->verified()->create();
        expect($verifiedUser->email_verified_at)->not->toBeNull();

        // Test unverified state
        $unverifiedUser = User::factory()->unverified()->create();
        expect($unverifiedUser->email_verified_at)->toBeNull();
    });

    it('verifies RefreshDatabase trait is working', function () {
        // Create a user
        $user = User::factory()->create(['email' => 'refresh-test@example.com']);
        expect(User::where('email', 'refresh-test@example.com')->count())->toBe(1);

        // The database will be refreshed after this test completes
        // This is verified by the next test not finding this user
    });

    it('confirms database was refreshed from previous test', function () {
        // This user should not exist because RefreshDatabase rolled back the previous test
        $user = User::where('email', 'refresh-test@example.com')->first();
        expect($user)->toBeNull();
    });

    it('tests hasPermission method with database-backed user', function () {
        // Create a user with admin role
        $admin = User::factory()->admin()->create();

        // Test admin permissions
        expect($this->service->hasPermission($admin, 'manage_users'))->toBeTrue()
            ->and($this->service->hasPermission($admin, 'manage_billing'))->toBeFalse()
            ->and($this->service->hasPermission($admin, 'view_analytics'))->toBeTrue();

        // Create a user with member role
        $member = User::factory()->member()->create();

        // Test member permissions
        expect($this->service->hasPermission($member, 'manage_users'))->toBeFalse()
            ->and($this->service->hasPermission($member, 'view_analytics'))->toBeTrue()
            ->and($this->service->hasPermission($member, 'manage_content'))->toBeTrue();
    });
});
