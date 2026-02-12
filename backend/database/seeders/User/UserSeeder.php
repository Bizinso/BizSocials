<?php

declare(strict_types=1);

namespace Database\Seeders\User;

use App\Enums\User\TenantRole;
use App\Enums\User\UserStatus;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder for User model.
 *
 * Creates sample users for each seeded tenant.
 */
final class UserSeeder extends Seeder
{
    /**
     * The password to use for all seeded users.
     */
    private static ?string $password = null;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        self::$password ??= Hash::make('password');

        // Get all tenants
        $tenants = Tenant::all()->keyBy('slug');

        // 1. Acme Corporation users
        $acmeTenant = $tenants->get('acme-corporation');
        if ($acmeTenant) {
            $acmeOwner = $this->createUser([
                'tenant_id' => $acmeTenant->id,
                'name' => 'John Owner',
                'email' => 'john.owner@acme.example.com',
                'role_in_tenant' => TenantRole::OWNER,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now()->subDays(60),
            ]);

            $this->createUser([
                'tenant_id' => $acmeTenant->id,
                'name' => 'Jane Admin',
                'email' => 'jane.admin@acme.example.com',
                'role_in_tenant' => TenantRole::ADMIN,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now()->subDays(45),
            ]);

            $this->createUser([
                'tenant_id' => $acmeTenant->id,
                'name' => 'Bob Member',
                'email' => 'bob.member@acme.example.com',
                'role_in_tenant' => TenantRole::MEMBER,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now()->subDays(30),
            ]);

            $this->createUser([
                'tenant_id' => $acmeTenant->id,
                'name' => 'Eve Viewer',
                'email' => 'eve.viewer@acme.example.com',
                'role_in_tenant' => TenantRole::MEMBER,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now()->subDays(15),
            ]);

            // Update tenant owner
            $acmeTenant->owner_user_id = $acmeOwner->id;
            $acmeTenant->save();
        }

        // 2. StartupXYZ users
        $startupTenant = $tenants->get('startupxyz');
        if ($startupTenant) {
            $startupOwner = $this->createUser([
                'tenant_id' => $startupTenant->id,
                'name' => 'Sarah Startup',
                'email' => 'sarah@startupxyz.example.com',
                'role_in_tenant' => TenantRole::OWNER,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now()->subDays(4),
            ]);

            $this->createUser([
                'tenant_id' => $startupTenant->id,
                'name' => 'Mike Developer',
                'email' => 'mike@startupxyz.example.com',
                'role_in_tenant' => TenantRole::MEMBER,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now()->subDays(3),
            ]);

            $startupTenant->owner_user_id = $startupOwner->id;
            $startupTenant->save();
        }

        // 3. Fashion Brand Co users (B2C Brand)
        $fashionTenant = $tenants->get('fashion-brand-co');
        if ($fashionTenant) {
            $fashionOwner = $this->createUser([
                'tenant_id' => $fashionTenant->id,
                'name' => 'Fashion Admin',
                'email' => 'admin@fashionbrand.example.com',
                'role_in_tenant' => TenantRole::OWNER,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now()->subDays(45),
            ]);

            $fashionTenant->owner_user_id = $fashionOwner->id;
            $fashionTenant->save();
        }

        // 4. John Freelancer (Individual)
        $freelancerTenant = $tenants->get('john-freelancer');
        if ($freelancerTenant) {
            $freelancerOwner = $this->createUser([
                'tenant_id' => $freelancerTenant->id,
                'name' => 'John Freelancer',
                'email' => 'john@freelancer.example.com',
                'role_in_tenant' => TenantRole::OWNER,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now()->subDays(20),
            ]);

            $freelancerTenant->owner_user_id = $freelancerOwner->id;
            $freelancerTenant->save();
        }

        // 5. Sarah Lifestyle (Influencer)
        $influencerTenant = $tenants->get('sarah-lifestyle');
        if ($influencerTenant) {
            $influencerOwner = $this->createUser([
                'tenant_id' => $influencerTenant->id,
                'name' => 'Sarah Lifestyle',
                'email' => 'sarah@lifestyle.example.com',
                'role_in_tenant' => TenantRole::OWNER,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now()->subDays(30),
                'mfa_enabled' => true,
            ]);

            $influencerTenant->owner_user_id = $influencerOwner->id;
            $influencerTenant->save();
        }

        // 6. Green Earth Foundation (Non-Profit)
        $ngoTenant = $tenants->get('green-earth-foundation');
        if ($ngoTenant) {
            $ngoOwner = $this->createUser([
                'tenant_id' => $ngoTenant->id,
                'name' => 'Earth Admin',
                'email' => 'admin@greenearth.example.org',
                'role_in_tenant' => TenantRole::OWNER,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now()->subDays(90),
            ]);

            $this->createUser([
                'tenant_id' => $ngoTenant->id,
                'name' => 'Volunteer One',
                'email' => 'volunteer@greenearth.example.org',
                'role_in_tenant' => TenantRole::MEMBER,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now()->subDays(60),
            ]);

            $ngoTenant->owner_user_id = $ngoOwner->id;
            $ngoTenant->save();
        }

        // 7. Pending Corp
        $pendingTenant = $tenants->get('pending-corp');
        if ($pendingTenant) {
            $pendingOwner = $this->createUser([
                'tenant_id' => $pendingTenant->id,
                'name' => 'Pending User',
                'email' => 'user@pendingcorp.example.com',
                'role_in_tenant' => TenantRole::OWNER,
                'status' => UserStatus::PENDING,
                'email_verified_at' => null,
            ]);

            $pendingTenant->owner_user_id = $pendingOwner->id;
            $pendingTenant->save();
        }

        // 8. Suspended Inc
        $suspendedTenant = $tenants->get('suspended-inc');
        if ($suspendedTenant) {
            $suspendedOwner = $this->createUser([
                'tenant_id' => $suspendedTenant->id,
                'name' => 'Suspended User',
                'email' => 'user@suspendedinc.example.com',
                'role_in_tenant' => TenantRole::OWNER,
                'status' => UserStatus::SUSPENDED,
                'email_verified_at' => now()->subDays(120),
            ]);

            $suspendedTenant->owner_user_id = $suspendedOwner->id;
            $suspendedTenant->save();
        }

        $this->command->info('Users seeded successfully.');
    }

    /**
     * Create a user with the given attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createUser(array $attributes): User
    {
        $defaults = [
            'password' => self::$password,
            'language' => 'en',
            'mfa_enabled' => false,
            'settings' => [
                'notifications' => [
                    'email_on_mention' => true,
                    'email_on_comment' => true,
                    'email_digest' => 'daily',
                    'push_enabled' => false,
                ],
                'ui' => [
                    'theme' => 'system',
                    'compact_mode' => false,
                    'sidebar_collapsed' => false,
                ],
            ],
        ];

        return User::firstOrCreate(
            ['tenant_id' => $attributes['tenant_id'], 'email' => $attributes['email']],
            array_merge($defaults, $attributes)
        );
    }
}
