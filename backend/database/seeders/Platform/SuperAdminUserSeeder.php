<?php

declare(strict_types=1);

namespace Database\Seeders\Platform;

use App\Enums\Platform\SuperAdminRole;
use App\Enums\Platform\SuperAdminStatus;
use App\Models\Platform\SuperAdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder for SuperAdminUser model.
 *
 * Creates the default super admin account for initial platform setup.
 */
final class SuperAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default super admin if it doesn't exist
        SuperAdminUser::firstOrCreate(
            ['email' => 'admin@bizinso.com'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make(
                    env('SUPER_ADMIN_PASSWORD', 'BizS0c!als@2026!')
                ),
                'role' => SuperAdminRole::SUPER_ADMIN,
                'status' => SuperAdminStatus::ACTIVE,
                'mfa_enabled' => false,
            ]
        );

        // Create support account for testing
        if (app()->environment('local', 'testing')) {
            SuperAdminUser::firstOrCreate(
                ['email' => 'support@bizinso.com'],
                [
                    'name' => 'Support Admin',
                    'password' => Hash::make('support@123'),
                    'role' => SuperAdminRole::SUPPORT,
                    'status' => SuperAdminStatus::ACTIVE,
                    'mfa_enabled' => false,
                ]
            );

            SuperAdminUser::firstOrCreate(
                ['email' => 'viewer@bizinso.com'],
                [
                    'name' => 'Viewer Admin',
                    'password' => Hash::make('viewer@123'),
                    'role' => SuperAdminRole::VIEWER,
                    'status' => SuperAdminStatus::ACTIVE,
                    'mfa_enabled' => false,
                ]
            );
        }

        $this->command->info('Super admin users seeded successfully.');
    }
}
