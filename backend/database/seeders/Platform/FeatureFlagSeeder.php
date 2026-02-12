<?php

declare(strict_types=1);

namespace Database\Seeders\Platform;

use App\Enums\Platform\PlanCode;
use App\Models\Platform\FeatureFlag;
use Illuminate\Database\Seeder;

/**
 * Seeder for FeatureFlag model.
 *
 * Creates default feature flags for gradual feature rollout.
 */
final class FeatureFlagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $featureFlags = [
            // AI Features
            [
                'key' => 'ai.caption_generation',
                'name' => 'AI Caption Generation',
                'description' => 'Generate post captions using AI based on images or topics',
                'is_enabled' => true,
                'rollout_percentage' => 100,
                'allowed_plans' => null, // All plans
            ],
            [
                'key' => 'ai.hashtag_suggestions',
                'name' => 'AI Hashtag Suggestions',
                'description' => 'Get AI-powered hashtag suggestions for better reach',
                'is_enabled' => true,
                'rollout_percentage' => 100,
                'allowed_plans' => null,
            ],
            [
                'key' => 'ai.best_time_posting',
                'name' => 'AI Best Time to Post',
                'description' => 'AI analyzes your audience to suggest optimal posting times',
                'is_enabled' => false,
                'rollout_percentage' => 0,
                'allowed_plans' => [
                    PlanCode::PROFESSIONAL->value,
                    PlanCode::BUSINESS->value,
                    PlanCode::ENTERPRISE->value,
                ],
            ],
            [
                'key' => 'ai.content_optimization',
                'name' => 'AI Content Optimization',
                'description' => 'Get AI suggestions to improve content engagement',
                'is_enabled' => false,
                'rollout_percentage' => 0,
                'allowed_plans' => [
                    PlanCode::BUSINESS->value,
                    PlanCode::ENTERPRISE->value,
                ],
            ],
            [
                'key' => 'ai.image_generation',
                'name' => 'AI Image Generation',
                'description' => 'Generate images using AI for social media posts',
                'is_enabled' => false,
                'rollout_percentage' => 0,
                'allowed_plans' => [PlanCode::ENTERPRISE->value],
            ],

            // Social Platform Integrations
            [
                'key' => 'social.tiktok',
                'name' => 'TikTok Integration',
                'description' => 'Connect and post to TikTok accounts',
                'is_enabled' => false,
                'rollout_percentage' => 0,
                'allowed_plans' => null,
            ],
            [
                'key' => 'social.youtube',
                'name' => 'YouTube Integration',
                'description' => 'Connect and post to YouTube channels',
                'is_enabled' => false,
                'rollout_percentage' => 0,
                'allowed_plans' => null,
            ],
            [
                'key' => 'social.pinterest',
                'name' => 'Pinterest Integration',
                'description' => 'Connect and post to Pinterest boards',
                'is_enabled' => false,
                'rollout_percentage' => 0,
                'allowed_plans' => null,
            ],
            [
                'key' => 'social.threads',
                'name' => 'Threads Integration',
                'description' => 'Connect and post to Threads accounts',
                'is_enabled' => false,
                'rollout_percentage' => 50,
                'allowed_plans' => null,
            ],

            // Premium Features
            [
                'key' => 'white_label.enabled',
                'name' => 'White Label Features',
                'description' => 'Enable white label customization for enterprise clients',
                'is_enabled' => true,
                'rollout_percentage' => 100,
                'allowed_plans' => [PlanCode::ENTERPRISE->value],
            ],
            [
                'key' => 'analytics.advanced',
                'name' => 'Advanced Analytics',
                'description' => 'Access advanced analytics and custom reports',
                'is_enabled' => true,
                'rollout_percentage' => 100,
                'allowed_plans' => [
                    PlanCode::PROFESSIONAL->value,
                    PlanCode::BUSINESS->value,
                    PlanCode::ENTERPRISE->value,
                ],
            ],
            [
                'key' => 'analytics.competitor',
                'name' => 'Competitor Analytics',
                'description' => 'Track and analyze competitor social media performance',
                'is_enabled' => false,
                'rollout_percentage' => 0,
                'allowed_plans' => [
                    PlanCode::BUSINESS->value,
                    PlanCode::ENTERPRISE->value,
                ],
            ],
            [
                'key' => 'bulk.upload',
                'name' => 'Bulk Upload',
                'description' => 'Upload and schedule multiple posts at once',
                'is_enabled' => true,
                'rollout_percentage' => 100,
                'allowed_plans' => [
                    PlanCode::STARTER->value,
                    PlanCode::PROFESSIONAL->value,
                    PlanCode::BUSINESS->value,
                    PlanCode::ENTERPRISE->value,
                ],
            ],
            [
                'key' => 'api.access',
                'name' => 'API Access',
                'description' => 'Access to the BizSocials REST API',
                'is_enabled' => true,
                'rollout_percentage' => 100,
                'allowed_plans' => [
                    PlanCode::BUSINESS->value,
                    PlanCode::ENTERPRISE->value,
                ],
            ],

            // Beta Features
            [
                'key' => 'beta.inbox_unified',
                'name' => 'Unified Inbox (Beta)',
                'description' => 'Manage all social messages in one unified inbox',
                'is_enabled' => false,
                'rollout_percentage' => 10,
                'allowed_plans' => null,
            ],
            [
                'key' => 'beta.social_listening',
                'name' => 'Social Listening (Beta)',
                'description' => 'Monitor brand mentions and keywords across platforms',
                'is_enabled' => false,
                'rollout_percentage' => 5,
                'allowed_plans' => [
                    PlanCode::BUSINESS->value,
                    PlanCode::ENTERPRISE->value,
                ],
            ],
        ];

        foreach ($featureFlags as $flag) {
            FeatureFlag::firstOrCreate(
                ['key' => $flag['key']],
                [
                    'name' => $flag['name'],
                    'description' => $flag['description'],
                    'is_enabled' => $flag['is_enabled'],
                    'rollout_percentage' => $flag['rollout_percentage'],
                    'allowed_plans' => $flag['allowed_plans'],
                ]
            );
        }

        $this->command->info('Feature flags seeded successfully.');
    }
}
