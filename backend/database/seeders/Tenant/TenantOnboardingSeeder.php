<?php

declare(strict_types=1);

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantOnboarding;
use Illuminate\Database\Seeder;

/**
 * Seeder for TenantOnboarding model.
 *
 * Creates onboarding records at various stages for each tenant.
 */
final class TenantOnboardingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $onboardingData = [
            // Acme Corporation - Completed
            [
                'tenant_slug' => 'acme-corporation',
                'current_step' => 'tour_completed',
                'steps_completed' => TenantOnboarding::STEPS,
                'started_at' => now()->subDays(65),
                'completed_at' => now()->subDays(60),
                'abandoned_at' => null,
                'metadata' => [
                    'referral_source' => 'google',
                    'signup_device' => 'desktop',
                    'completed_in_days' => 5,
                ],
            ],
            // StartupXYZ - In Progress (just started trial)
            [
                'tenant_slug' => 'startupxyz',
                'current_step' => 'first_social_account_connected',
                'steps_completed' => [
                    'account_created',
                    'email_verified',
                    'business_type_selected',
                    'profile_completed',
                    'plan_selected',
                    'payment_completed',
                    'first_workspace_created',
                ],
                'started_at' => now()->subDays(5),
                'completed_at' => null,
                'abandoned_at' => null,
                'metadata' => [
                    'referral_source' => 'referral',
                    'signup_device' => 'desktop',
                ],
            ],
            // Fashion Brand Co - Completed
            [
                'tenant_slug' => 'fashion-brand-co',
                'current_step' => 'tour_completed',
                'steps_completed' => TenantOnboarding::STEPS,
                'started_at' => now()->subDays(50),
                'completed_at' => now()->subDays(45),
                'abandoned_at' => null,
                'metadata' => [
                    'referral_source' => 'social',
                    'signup_device' => 'mobile',
                    'completed_in_days' => 5,
                ],
            ],
            // John Freelancer - Completed
            [
                'tenant_slug' => 'john-freelancer',
                'current_step' => 'tour_completed',
                'steps_completed' => TenantOnboarding::STEPS,
                'started_at' => now()->subDays(22),
                'completed_at' => now()->subDays(20),
                'abandoned_at' => null,
                'metadata' => [
                    'referral_source' => 'direct',
                    'signup_device' => 'desktop',
                    'completed_in_days' => 2,
                ],
            ],
            // Sarah Lifestyle - Completed
            [
                'tenant_slug' => 'sarah-lifestyle',
                'current_step' => 'tour_completed',
                'steps_completed' => TenantOnboarding::STEPS,
                'started_at' => now()->subDays(33),
                'completed_at' => now()->subDays(30),
                'abandoned_at' => null,
                'metadata' => [
                    'referral_source' => 'social',
                    'signup_device' => 'mobile',
                    'completed_in_days' => 3,
                ],
            ],
            // Green Earth Foundation - Completed
            [
                'tenant_slug' => 'green-earth-foundation',
                'current_step' => 'tour_completed',
                'steps_completed' => TenantOnboarding::STEPS,
                'started_at' => now()->subDays(95),
                'completed_at' => now()->subDays(90),
                'abandoned_at' => null,
                'metadata' => [
                    'referral_source' => 'referral',
                    'signup_device' => 'desktop',
                    'completed_in_days' => 5,
                ],
            ],
            // Pending Corp - Just Started
            [
                'tenant_slug' => 'pending-corp',
                'current_step' => 'email_verified',
                'steps_completed' => ['account_created'],
                'started_at' => now()->subHours(2),
                'completed_at' => null,
                'abandoned_at' => null,
                'metadata' => [
                    'referral_source' => 'google',
                    'signup_device' => 'desktop',
                ],
            ],
            // Suspended Inc - Completed (before suspension)
            [
                'tenant_slug' => 'suspended-inc',
                'current_step' => 'tour_completed',
                'steps_completed' => TenantOnboarding::STEPS,
                'started_at' => now()->subDays(130),
                'completed_at' => now()->subDays(120),
                'abandoned_at' => null,
                'metadata' => [
                    'referral_source' => 'direct',
                    'signup_device' => 'desktop',
                    'completed_in_days' => 10,
                ],
            ],
        ];

        foreach ($onboardingData as $data) {
            $tenantSlug = $data['tenant_slug'];
            unset($data['tenant_slug']);

            $tenant = Tenant::where('slug', $tenantSlug)->first();

            if ($tenant) {
                TenantOnboarding::firstOrCreate(
                    ['tenant_id' => $tenant->id],
                    $data
                );
            }
        }

        $this->command->info('Tenant onboarding records seeded successfully.');
    }
}
