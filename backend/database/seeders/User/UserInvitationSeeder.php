<?php

declare(strict_types=1);

namespace Database\Seeders\User;

use App\Enums\User\InvitationStatus;
use App\Enums\User\TenantRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\User\UserInvitation;
use Illuminate\Database\Seeder;

/**
 * Seeder for UserInvitation model.
 *
 * Creates sample invitations for various scenarios.
 */
final class UserInvitationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get Acme Corporation tenant and its owner
        $acmeTenant = Tenant::where('slug', 'acme-corporation')->first();
        $acmeOwner = $acmeTenant ? User::where('tenant_id', $acmeTenant->id)
            ->where('role_in_tenant', TenantRole::OWNER)
            ->first() : null;

        if ($acmeTenant && $acmeOwner) {
            // 2 pending invitations for Acme Corporation
            UserInvitation::firstOrCreate(
                ['tenant_id' => $acmeTenant->id, 'email' => 'new.hire1@acme.example.com'],
                [
                    'role_in_tenant' => TenantRole::MEMBER,
                    'workspace_memberships' => [
                        ['workspace_id' => fake()->uuid(), 'role' => 'EDITOR'],
                    ],
                    'invited_by' => $acmeOwner->id,
                    'token' => UserInvitation::generateToken(),
                    'status' => InvitationStatus::PENDING,
                    'expires_at' => now()->addDays(7),
                ]
            );

            UserInvitation::firstOrCreate(
                ['tenant_id' => $acmeTenant->id, 'email' => 'new.hire2@acme.example.com'],
                [
                    'role_in_tenant' => TenantRole::ADMIN,
                    'workspace_memberships' => null,
                    'invited_by' => $acmeOwner->id,
                    'token' => UserInvitation::generateToken(),
                    'status' => InvitationStatus::PENDING,
                    'expires_at' => now()->addDays(5),
                ]
            );

            // 1 expired invitation
            UserInvitation::firstOrCreate(
                ['tenant_id' => $acmeTenant->id, 'email' => 'expired.invite@acme.example.com'],
                [
                    'role_in_tenant' => TenantRole::MEMBER,
                    'workspace_memberships' => null,
                    'invited_by' => $acmeOwner->id,
                    'token' => UserInvitation::generateToken(),
                    'status' => InvitationStatus::EXPIRED,
                    'expires_at' => now()->subDays(10),
                ]
            );

            // 1 accepted invitation
            UserInvitation::firstOrCreate(
                ['tenant_id' => $acmeTenant->id, 'email' => 'bob.member@acme.example.com'],
                [
                    'role_in_tenant' => TenantRole::MEMBER,
                    'workspace_memberships' => null,
                    'invited_by' => $acmeOwner->id,
                    'token' => UserInvitation::generateToken(),
                    'status' => InvitationStatus::ACCEPTED,
                    'expires_at' => now()->subDays(20),
                    'accepted_at' => now()->subDays(30),
                ]
            );
        }

        // Get StartupXYZ and create a revoked invitation
        $startupTenant = Tenant::where('slug', 'startupxyz')->first();
        $startupOwner = $startupTenant ? User::where('tenant_id', $startupTenant->id)
            ->where('role_in_tenant', TenantRole::OWNER)
            ->first() : null;

        if ($startupTenant && $startupOwner) {
            UserInvitation::firstOrCreate(
                ['tenant_id' => $startupTenant->id, 'email' => 'revoked.user@startupxyz.example.com'],
                [
                    'role_in_tenant' => TenantRole::MEMBER,
                    'workspace_memberships' => null,
                    'invited_by' => $startupOwner->id,
                    'token' => UserInvitation::generateToken(),
                    'status' => InvitationStatus::REVOKED,
                    'expires_at' => now()->addDays(3),
                ]
            );
        }

        $this->command->info('User invitations seeded successfully.');
    }
}
