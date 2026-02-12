<?php

declare(strict_types=1);

namespace Database\Seeders\Tenant;

use App\Enums\Platform\PlanCode;
use App\Enums\Tenant\TenantStatus;
use App\Enums\Tenant\TenantType;
use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Seeder;

/**
 * Seeder for Tenant model.
 *
 * Creates sample tenants representing different business types and statuses.
 */
final class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            'FREE' => PlanDefinition::where('code', PlanCode::FREE)->first(),
            'STARTER' => PlanDefinition::where('code', PlanCode::STARTER)->first(),
            'PROFESSIONAL' => PlanDefinition::where('code', PlanCode::PROFESSIONAL)->first(),
            'BUSINESS' => PlanDefinition::where('code', PlanCode::BUSINESS)->first(),
            'ENTERPRISE' => PlanDefinition::where('code', PlanCode::ENTERPRISE)->first(),
        ];

        $tenants = [
            // 1. Acme Corporation - B2B Enterprise, Active, on Professional plan
            [
                'name' => 'Acme Corporation',
                'slug' => 'acme-corporation',
                'type' => TenantType::B2B_ENTERPRISE,
                'status' => TenantStatus::ACTIVE,
                'plan_id' => $plans['PROFESSIONAL']?->id,
                'trial_ends_at' => null,
                'settings' => [
                    'timezone' => 'Asia/Kolkata',
                    'language' => 'en',
                    'notifications' => [
                        'email' => true,
                        'in_app' => true,
                        'digest' => 'daily',
                    ],
                    'branding' => [
                        'logo_url' => null,
                        'primary_color' => '#1a56db',
                    ],
                    'security' => [
                        'require_mfa' => true,
                        'session_timeout_minutes' => 30,
                    ],
                ],
                'onboarding_completed_at' => now()->subDays(60),
            ],
            // 2. StartupXYZ - B2B SMB, Active, on Starter plan, on trial
            [
                'name' => 'StartupXYZ',
                'slug' => 'startupxyz',
                'type' => TenantType::B2B_SMB,
                'status' => TenantStatus::ACTIVE,
                'plan_id' => $plans['STARTER']?->id,
                'trial_ends_at' => now()->addDays(10),
                'settings' => [
                    'timezone' => 'Asia/Kolkata',
                    'language' => 'en',
                    'notifications' => [
                        'email' => true,
                        'in_app' => true,
                        'digest' => 'weekly',
                    ],
                    'branding' => [
                        'logo_url' => null,
                        'primary_color' => '#059669',
                    ],
                    'security' => [
                        'require_mfa' => false,
                        'session_timeout_minutes' => 60,
                    ],
                ],
                'onboarding_completed_at' => now()->subDays(4),
            ],
            // 3. Fashion Brand Co - B2C Brand, Active, on Business plan
            [
                'name' => 'Fashion Brand Co',
                'slug' => 'fashion-brand-co',
                'type' => TenantType::B2C_BRAND,
                'status' => TenantStatus::ACTIVE,
                'plan_id' => $plans['BUSINESS']?->id,
                'trial_ends_at' => null,
                'settings' => [
                    'timezone' => 'America/New_York',
                    'language' => 'en',
                    'notifications' => [
                        'email' => true,
                        'in_app' => true,
                        'digest' => 'daily',
                    ],
                    'branding' => [
                        'logo_url' => null,
                        'primary_color' => '#db2777',
                    ],
                    'security' => [
                        'require_mfa' => false,
                        'session_timeout_minutes' => 60,
                    ],
                ],
                'onboarding_completed_at' => now()->subDays(45),
            ],
            // 4. John Freelancer - Individual, Active, on Free plan
            [
                'name' => 'John Freelancer',
                'slug' => 'john-freelancer',
                'type' => TenantType::INDIVIDUAL,
                'status' => TenantStatus::ACTIVE,
                'plan_id' => $plans['FREE']?->id,
                'trial_ends_at' => null,
                'settings' => [
                    'timezone' => 'Europe/London',
                    'language' => 'en',
                    'notifications' => [
                        'email' => true,
                        'in_app' => true,
                        'digest' => 'weekly',
                    ],
                    'branding' => [
                        'logo_url' => null,
                        'primary_color' => '#2563eb',
                    ],
                    'security' => [
                        'require_mfa' => false,
                        'session_timeout_minutes' => 120,
                    ],
                ],
                'onboarding_completed_at' => now()->subDays(20),
            ],
            // 5. Influencer Sarah - Influencer, Active, on Professional plan
            [
                'name' => 'Sarah Lifestyle',
                'slug' => 'sarah-lifestyle',
                'type' => TenantType::INFLUENCER,
                'status' => TenantStatus::ACTIVE,
                'plan_id' => $plans['PROFESSIONAL']?->id,
                'trial_ends_at' => null,
                'settings' => [
                    'timezone' => 'America/Los_Angeles',
                    'language' => 'en',
                    'notifications' => [
                        'email' => true,
                        'in_app' => true,
                        'digest' => 'daily',
                    ],
                    'branding' => [
                        'logo_url' => null,
                        'primary_color' => '#7c3aed',
                    ],
                    'security' => [
                        'require_mfa' => true,
                        'session_timeout_minutes' => 60,
                    ],
                ],
                'onboarding_completed_at' => now()->subDays(30),
            ],
            // 6. Green Earth NGO - Non-Profit, Active, on Starter plan (Non-Profit pricing)
            [
                'name' => 'Green Earth Foundation',
                'slug' => 'green-earth-foundation',
                'type' => TenantType::NON_PROFIT,
                'status' => TenantStatus::ACTIVE,
                'plan_id' => $plans['STARTER']?->id,
                'trial_ends_at' => null,
                'settings' => [
                    'timezone' => 'Asia/Kolkata',
                    'language' => 'en',
                    'notifications' => [
                        'email' => true,
                        'in_app' => true,
                        'digest' => 'weekly',
                    ],
                    'branding' => [
                        'logo_url' => null,
                        'primary_color' => '#16a34a',
                    ],
                    'security' => [
                        'require_mfa' => false,
                        'session_timeout_minutes' => 60,
                    ],
                ],
                'onboarding_completed_at' => now()->subDays(90),
            ],
            // 7. Pending Corp - B2B SMB, Pending (just signed up)
            [
                'name' => 'Pending Corp',
                'slug' => 'pending-corp',
                'type' => TenantType::B2B_SMB,
                'status' => TenantStatus::PENDING,
                'plan_id' => null,
                'trial_ends_at' => null,
                'settings' => [
                    'timezone' => 'Asia/Kolkata',
                    'language' => 'en',
                    'notifications' => [
                        'email' => true,
                        'in_app' => true,
                        'digest' => 'daily',
                    ],
                    'branding' => [
                        'logo_url' => null,
                        'primary_color' => '#2563eb',
                    ],
                    'security' => [
                        'require_mfa' => false,
                        'session_timeout_minutes' => 60,
                    ],
                ],
                'onboarding_completed_at' => null,
            ],
            // 8. Suspended Inc - B2B Enterprise, Suspended (payment failed)
            [
                'name' => 'Suspended Inc',
                'slug' => 'suspended-inc',
                'type' => TenantType::B2B_ENTERPRISE,
                'status' => TenantStatus::SUSPENDED,
                'plan_id' => $plans['ENTERPRISE']?->id,
                'trial_ends_at' => null,
                'settings' => [
                    'timezone' => 'America/New_York',
                    'language' => 'en',
                    'notifications' => [
                        'email' => true,
                        'in_app' => false,
                        'digest' => 'none',
                    ],
                    'branding' => [
                        'logo_url' => null,
                        'primary_color' => '#dc2626',
                    ],
                    'security' => [
                        'require_mfa' => true,
                        'session_timeout_minutes' => 30,
                    ],
                ],
                'onboarding_completed_at' => now()->subDays(120),
                'metadata' => [
                    'suspension_reason' => 'Payment failed - 3 consecutive attempts',
                    'suspended_at' => now()->subDays(5)->toIso8601String(),
                ],
            ],
        ];

        foreach ($tenants as $tenantData) {
            Tenant::firstOrCreate(
                ['slug' => $tenantData['slug']],
                $tenantData
            );
        }

        $this->command->info('Tenants seeded successfully.');
    }
}
