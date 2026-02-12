<?php

declare(strict_types=1);

namespace Database\Seeders\Platform;

use App\Enums\Platform\PlanCode;
use App\Models\Platform\PlanDefinition;
use Illuminate\Database\Seeder;

/**
 * Seeder for PlanDefinition model.
 *
 * Creates all subscription plan definitions with pricing and features.
 */
final class PlanDefinitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'code' => PlanCode::FREE,
                'name' => 'Free',
                'description' => 'Perfect for getting started with social media management',
                'price_inr_monthly' => 0,
                'price_inr_yearly' => 0,
                'price_usd_monthly' => 0,
                'price_usd_yearly' => 0,
                'trial_days' => 0,
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 1,
                'features' => [
                    '1 User',
                    '1 Workspace',
                    '2 Social Accounts',
                    '30 Posts/month',
                    'Basic Analytics',
                    'Email Support',
                ],
            ],
            [
                'code' => PlanCode::STARTER,
                'name' => 'Starter',
                'description' => 'Ideal for small businesses and solo entrepreneurs',
                'price_inr_monthly' => 999,
                'price_inr_yearly' => 9590, // ~20% discount
                'price_usd_monthly' => 15,
                'price_usd_yearly' => 144, // ~20% discount
                'trial_days' => 14,
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 2,
                'features' => [
                    '2 Users',
                    '2 Workspaces',
                    '5 Social Accounts',
                    '150 Posts/month',
                    '50 Scheduled Posts',
                    'AI Caption Generation',
                    'AI Hashtag Suggestions',
                    '2 GB Storage',
                    '30 Days Analytics History',
                    'Priority Email Support',
                ],
            ],
            [
                'code' => PlanCode::PROFESSIONAL,
                'name' => 'Professional',
                'description' => 'For growing teams and agencies',
                'price_inr_monthly' => 2499,
                'price_inr_yearly' => 23990, // ~20% discount
                'price_usd_monthly' => 35,
                'price_usd_yearly' => 336, // ~20% discount
                'trial_days' => 14,
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 3,
                'features' => [
                    '5 Users',
                    '5 Workspaces',
                    '15 Social Accounts',
                    '500 Posts/month',
                    '200 Scheduled Posts',
                    'All AI Features',
                    '10 GB Storage',
                    '90 Days Analytics History',
                    'Advanced Analytics',
                    'Bulk Upload',
                    'Content Calendar',
                    'Chat Support',
                ],
            ],
            [
                'code' => PlanCode::BUSINESS,
                'name' => 'Business',
                'description' => 'For established businesses with multiple brands',
                'price_inr_monthly' => 4999,
                'price_inr_yearly' => 47990, // ~20% discount
                'price_usd_monthly' => 70,
                'price_usd_yearly' => 672, // ~20% discount
                'trial_days' => 14,
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 4,
                'features' => [
                    '15 Users',
                    '10 Workspaces',
                    '50 Social Accounts',
                    'Unlimited Posts',
                    'Unlimited Scheduled Posts',
                    'All AI Features',
                    '50 GB Storage',
                    '1 Year Analytics History',
                    'Advanced Analytics',
                    'Competitor Analytics',
                    'API Access',
                    'Team Collaboration',
                    'Approval Workflows',
                    'Priority Support',
                ],
            ],
            [
                'code' => PlanCode::ENTERPRISE,
                'name' => 'Enterprise',
                'description' => 'Custom solution for large organizations',
                'price_inr_monthly' => 9999,
                'price_inr_yearly' => 95990, // ~20% discount
                'price_usd_monthly' => 150,
                'price_usd_yearly' => 1440, // ~20% discount
                'trial_days' => 30,
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 5,
                'features' => [
                    'Unlimited Users',
                    'Unlimited Workspaces',
                    'Unlimited Social Accounts',
                    'Unlimited Posts',
                    'Unlimited Scheduled Posts',
                    'All AI Features',
                    'AI Image Generation',
                    'Unlimited Storage',
                    'Unlimited Analytics History',
                    'White Label',
                    'Custom Integrations',
                    'Dedicated Account Manager',
                    'SLA Guarantee',
                    '24/7 Phone Support',
                    'Custom Contract',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            PlanDefinition::firstOrCreate(
                ['code' => $plan['code']],
                [
                    'name' => $plan['name'],
                    'description' => $plan['description'],
                    'price_inr_monthly' => $plan['price_inr_monthly'],
                    'price_inr_yearly' => $plan['price_inr_yearly'],
                    'price_usd_monthly' => $plan['price_usd_monthly'],
                    'price_usd_yearly' => $plan['price_usd_yearly'],
                    'trial_days' => $plan['trial_days'],
                    'is_active' => $plan['is_active'],
                    'is_public' => $plan['is_public'],
                    'sort_order' => $plan['sort_order'],
                    'features' => $plan['features'],
                ]
            );
        }

        $this->command->info('Plan definitions seeded successfully.');
    }
}
