<?php

declare(strict_types=1);

use App\Enums\User\TenantRole;
use App\Enums\User\UserStatus;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->userService = app(UserService::class);
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

describe('User CRUD Operations', function () {
    it('creates a user with valid data', function () {
        $data = [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'password' => 'password123',
            'role_in_tenant' => TenantRole::MEMBER->value,
        ];

        $user = $this->userService->createUser($this->tenant, $data, $this->admin);

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->email)->toBe('newuser@example.com')
            ->and($user->name)->toBe('New User')
            ->and($user->tenant_id)->toBe($this->tenant->id)
            ->and($user->role_in_tenant)->toBe(TenantRole::MEMBER);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'tenant_id' => $this->tenant->id,
        ]);
    });

    it('prevents non-admin from creating users', function () {
        $data = [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'role_in_tenant' => TenantRole::MEMBER->value,
        ];

        expect(fn () => $this->userService->createUser($this->tenant, $data, $this->member))
            ->toThrow(ValidationException::class, 'Only admins can create users');
    });

    it('prevents duplicate email in same tenant', function () {
        $data = [
            'email' => $this->member->email,
            'name' => 'Duplicate User',
            'role_in_tenant' => TenantRole::MEMBER->value,
        ];

        expect(fn () => $this->userService->createUser($this->tenant, $data, $this->admin))
            ->toThrow(ValidationException::class);
    });

    it('gets users for tenant with filters', function () {
        // Create additional users
        User::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        $users = $this->userService->getUsersForTenant($this->tenant, ['per_page' => 10]);

        expect($users->total())->toBeGreaterThanOrEqual(8); // 3 from beforeEach + 5 new
    });

    it('filters users by role', function () {
        $users = $this->userService->getUsersForTenant($this->tenant, [
            'role' => TenantRole::OWNER->value,
            'per_page' => 10,
        ]);

        expect($users->total())->toBe(1)
            ->and($users->first()->role_in_tenant)->toBe(TenantRole::OWNER);
    });

    it('searches users by name or email', function () {
        $users = $this->userService->getUsersForTenant($this->tenant, [
            'search' => $this->member->name,
            'per_page' => 10,
        ]);

        expect($users->total())->toBeGreaterThanOrEqual(1);
    });

    it('removes a user successfully', function () {
        $userToRemove = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        $this->userService->removeUser($userToRemove, $this->admin);

        $this->assertSoftDeleted('users', ['id' => $userToRemove->id]);
    });

    it('prevents non-admin from removing users', function () {
        $userToRemove = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        expect(fn () => $this->userService->removeUser($userToRemove, $this->member))
            ->toThrow(ValidationException::class, 'Only admins can remove users');
    });

    it('prevents user from removing themselves', function () {
        expect(fn () => $this->userService->removeUser($this->admin, $this->admin))
            ->toThrow(ValidationException::class, 'You cannot remove yourself');
    });

    it('prevents removing the last owner', function () {
        expect(fn () => $this->userService->removeUser($this->owner, $this->admin))
            ->toThrow(ValidationException::class, 'Cannot remove the last owner');
    });
});

describe('Role Assignment', function () {
    it('updates user role successfully', function () {
        $user = $this->userService->updateUserRole($this->member, TenantRole::ADMIN, $this->owner);

        expect($user->role_in_tenant)->toBe(TenantRole::ADMIN);

        $this->assertDatabaseHas('users', [
            'id' => $this->member->id,
            'role_in_tenant' => TenantRole::ADMIN->value,
        ]);
    });

    it('prevents non-admin from updating roles', function () {
        expect(fn () => $this->userService->updateUserRole($this->member, TenantRole::ADMIN, $this->member))
            ->toThrow(ValidationException::class, 'Only admins can update user roles');
    });

    it('prevents user from changing own role', function () {
        expect(fn () => $this->userService->updateUserRole($this->admin, TenantRole::MEMBER, $this->admin))
            ->toThrow(ValidationException::class, 'You cannot change your own role');
    });

    it('prevents non-owner from assigning owner role', function () {
        expect(fn () => $this->userService->updateUserRole($this->member, TenantRole::OWNER, $this->admin))
            ->toThrow(ValidationException::class, 'Only the owner can assign the owner role');
    });

    it('prevents demoting the last owner', function () {
        // Admin tries to demote the owner
        expect(fn () => $this->userService->updateUserRole($this->owner, TenantRole::ADMIN, $this->admin))
            ->toThrow(ValidationException::class, 'Cannot demote the last owner');
    });

    it('allows demoting owner when multiple owners exist', function () {
        $secondOwner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::OWNER,
        ]);

        $user = $this->userService->updateUserRole($this->owner, TenantRole::ADMIN, $secondOwner);

        expect($user->role_in_tenant)->toBe(TenantRole::ADMIN);
    });
});

describe('Permission Checking', function () {
    it('grants manage_users permission to admins', function () {
        expect($this->userService->hasPermission($this->admin, 'manage_users'))->toBeTrue()
            ->and($this->userService->hasPermission($this->owner, 'manage_users'))->toBeTrue()
            ->and($this->userService->hasPermission($this->member, 'manage_users'))->toBeFalse();
    });

    it('grants manage_billing permission only to owner', function () {
        expect($this->userService->hasPermission($this->owner, 'manage_billing'))->toBeTrue()
            ->and($this->userService->hasPermission($this->admin, 'manage_billing'))->toBeFalse()
            ->and($this->userService->hasPermission($this->member, 'manage_billing'))->toBeFalse();
    });

    it('grants delete_tenant permission only to owner', function () {
        expect($this->userService->hasPermission($this->owner, 'delete_tenant'))->toBeTrue()
            ->and($this->userService->hasPermission($this->admin, 'delete_tenant'))->toBeFalse()
            ->and($this->userService->hasPermission($this->member, 'delete_tenant'))->toBeFalse();
    });

    it('grants view_analytics permission to all users', function () {
        expect($this->userService->hasPermission($this->owner, 'view_analytics'))->toBeTrue()
            ->and($this->userService->hasPermission($this->admin, 'view_analytics'))->toBeTrue()
            ->and($this->userService->hasPermission($this->member, 'view_analytics'))->toBeTrue();
    });

    it('denies unknown permissions', function () {
        expect($this->userService->hasPermission($this->owner, 'unknown_permission'))->toBeFalse();
    });

    it('returns correct permissions for owner role', function () {
        $permissions = $this->userService->getPermissionsForRole(TenantRole::OWNER);

        expect($permissions)->toContain('manage_users')
            ->and($permissions)->toContain('manage_billing')
            ->and($permissions)->toContain('delete_tenant')
            ->and($permissions)->toContain('manage_workspaces')
            ->and($permissions)->toContain('view_analytics')
            ->and($permissions)->toContain('manage_content');
    });

    it('returns correct permissions for admin role', function () {
        $permissions = $this->userService->getPermissionsForRole(TenantRole::ADMIN);

        expect($permissions)->toContain('manage_users')
            ->and($permissions)->toContain('manage_workspaces')
            ->and($permissions)->toContain('view_analytics')
            ->and($permissions)->toContain('manage_content')
            ->and($permissions)->not->toContain('manage_billing')
            ->and($permissions)->not->toContain('delete_tenant');
    });

    it('returns correct permissions for member role', function () {
        $permissions = $this->userService->getPermissionsForRole(TenantRole::MEMBER);

        expect($permissions)->toContain('view_analytics')
            ->and($permissions)->toContain('manage_content')
            ->and($permissions)->not->toContain('manage_users')
            ->and($permissions)->not->toContain('manage_billing');
    });
});

describe('Profile Management', function () {
    it('deletes account with correct password', function () {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'password' => Hash::make('password123'),
        ]);

        $this->userService->deleteAccount($user, 'password123');

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    });

    it('prevents account deletion with incorrect password', function () {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'password' => Hash::make('password123'),
        ]);

        expect(fn () => $this->userService->deleteAccount($user, 'wrongpassword'))
            ->toThrow(ValidationException::class, 'The password is incorrect');
    });
});
