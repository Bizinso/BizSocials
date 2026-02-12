<?php

declare(strict_types=1);

use App\Models\Platform\SuperAdminUser;
use Laravel\Sanctum\Sanctum;

describe('POST /api/v1/admin/auth/login', function () {
    it('authenticates valid super admin credentials', function () {
        $admin = SuperAdminUser::factory()->active()->create([
            'password' => bcrypt('admin-password'),
        ]);

        $response = $this->postJson('/api/v1/admin/auth/login', [
            'email' => $admin->email,
            'password' => 'admin-password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'admin' => ['id', 'name', 'email', 'role'],
                    'token',
                    'expires_in',
                ],
            ])
            ->assertJsonPath('data.admin.email', $admin->email);
    });

    it('rejects invalid credentials', function () {
        $admin = SuperAdminUser::factory()->create([
            'password' => bcrypt('admin-password'),
        ]);

        $response = $this->postJson('/api/v1/admin/auth/login', [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
    });

    it('rejects login for inactive admin', function () {
        $admin = SuperAdminUser::factory()->suspended()->create([
            'password' => bcrypt('admin-password'),
        ]);

        $response = $this->postJson('/api/v1/admin/auth/login', [
            'email' => $admin->email,
            'password' => 'admin-password',
        ]);

        $response->assertUnprocessable();
    });
});

describe('POST /api/v1/admin/auth/logout', function () {
    it('revokes the admin token', function () {
        $admin = SuperAdminUser::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/auth/logout');

        $response->assertOk();
    });
});

describe('GET /api/v1/admin/auth/me', function () {
    it('returns the authenticated admin profile', function () {
        $admin = SuperAdminUser::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/auth/me');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'email', 'role'],
            ])
            ->assertJsonPath('data.email', $admin->email);
    });
});
