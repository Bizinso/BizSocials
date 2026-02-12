<?php

declare(strict_types=1);

namespace Database\Seeders\Platform;

use App\Enums\Platform\PlanCode;
use App\Models\Platform\PlanDefinition;
use App\Models\Platform\PlanLimit;
use Illuminate\Database\Seeder;

/**
 * Seeder for PlanLimit model.
 *
 * Creates limits for all subscription plans as defined in the architecture.
 */
final class PlanLimitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define limits for each plan
        // Value of -1 indicates unlimited
        $limitsMatrix = [
            PlanCode::FREE->value => [
                'max_workspaces' => 1,
                'max_users' => 1,
                'max_social_accounts' => 2,
                'max_posts_per_month' => 30,
                'max_scheduled_posts' => 10,
                'max_team_members_per_workspace' => 1,
                'max_storage_gb' => 1, // 0.5 GB stored as 1 (minimum)
                'max_api_calls_per_day' => 0, // No API access
                'ai_requests_per_month' => 20,
                'analytics_history_days' => 7,
            ],
            PlanCode::STARTER->value => [
                'max_workspaces' => 2,
                'max_users' => 2,
                'max_social_accounts' => 5,
                'max_posts_per_month' => 150,
                'max_scheduled_posts' => 50,
                'max_team_members_per_workspace' => 2,
                'max_storage_gb' => 2,
                'max_api_calls_per_day' => 0, // No API access
                'ai_requests_per_month' => 50,
                'analytics_history_days' => 30,
            ],
            PlanCode::PROFESSIONAL->value => [
                'max_workspaces' => 5,
                'max_users' => 5,
                'max_social_accounts' => 15,
                'max_posts_per_month' => 500,
                'max_scheduled_posts' => 200,
                'max_team_members_per_workspace' => 5,
                'max_storage_gb' => 10,
                'max_api_calls_per_day' => 0, // No API access
                'ai_requests_per_month' => 200,
                'analytics_history_days' => 90,
            ],
            PlanCode::BUSINESS->value => [
                'max_workspaces' => 10,
                'max_users' => 15,
                'max_social_accounts' => 50,
                'max_posts_per_month' => -1, // Unlimited
                'max_scheduled_posts' => -1, // Unlimited
                'max_team_members_per_workspace' => 15,
                'max_storage_gb' => 50,
                'max_api_calls_per_day' => 10000,
                'ai_requests_per_month' => 500,
                'analytics_history_days' => 365,
            ],
            PlanCode::ENTERPRISE->value => [
                'max_workspaces' => -1, // Unlimited
                'max_users' => -1, // Unlimited
                'max_social_accounts' => -1, // Unlimited
                'max_posts_per_month' => -1, // Unlimited
                'max_scheduled_posts' => -1, // Unlimited
                'max_team_members_per_workspace' => -1, // Unlimited
                'max_storage_gb' => -1, // Unlimited
                'max_api_calls_per_day' => -1, // Unlimited
                'ai_requests_per_month' => -1, // Unlimited
                'analytics_history_days' => -1, // Unlimited
            ],
        ];

        foreach ($limitsMatrix as $planCode => $limits) {
            $plan = PlanDefinition::where('code', $planCode)->first();

            if ($plan === null) {
                $this->command->warn("Plan {$planCode} not found, skipping limits.");

                continue;
            }

            foreach ($limits as $limitKey => $limitValue) {
                PlanLimit::firstOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'limit_key' => $limitKey,
                    ],
                    [
                        'limit_value' => $limitValue,
                    ]
                );
            }
        }

        $this->command->info('Plan limits seeded successfully.');
    }
}
