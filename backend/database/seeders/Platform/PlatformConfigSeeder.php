<?php

declare(strict_types=1);

namespace Database\Seeders\Platform;

use App\Enums\Platform\ConfigCategory;
use App\Models\Platform\PlatformConfig;
use Illuminate\Database\Seeder;

/**
 * Seeder for PlatformConfig model.
 *
 * Creates default platform configuration settings.
 */
final class PlatformConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            // General configs
            [
                'key' => 'platform.name',
                'value' => ['value' => 'BizSocials'],
                'category' => ConfigCategory::GENERAL,
                'description' => 'The name of the platform',
                'is_sensitive' => false,
            ],
            [
                'key' => 'platform.url',
                'value' => ['value' => 'https://bizsocials.com'],
                'category' => ConfigCategory::GENERAL,
                'description' => 'The main URL of the platform',
                'is_sensitive' => false,
            ],
            [
                'key' => 'platform.support_email',
                'value' => ['value' => 'support@bizsocials.com'],
                'category' => ConfigCategory::GENERAL,
                'description' => 'Support email address',
                'is_sensitive' => false,
            ],
            [
                'key' => 'platform.logo_url',
                'value' => ['value' => '/images/logo.svg'],
                'category' => ConfigCategory::GENERAL,
                'description' => 'URL to the platform logo',
                'is_sensitive' => false,
            ],
            [
                'key' => 'platform.tagline',
                'value' => ['value' => 'Social Media Management Made Simple'],
                'category' => ConfigCategory::GENERAL,
                'description' => 'Platform tagline for marketing',
                'is_sensitive' => false,
            ],

            // Limits configs
            [
                'key' => 'trial.duration_days',
                'value' => ['value' => 14],
                'category' => ConfigCategory::LIMITS,
                'description' => 'Default trial period in days',
                'is_sensitive' => false,
            ],
            [
                'key' => 'limits.max_file_upload_mb',
                'value' => ['value' => 50],
                'category' => ConfigCategory::LIMITS,
                'description' => 'Maximum file upload size in MB',
                'is_sensitive' => false,
            ],
            [
                'key' => 'limits.max_image_dimension',
                'value' => ['value' => 4096],
                'category' => ConfigCategory::LIMITS,
                'description' => 'Maximum image dimension in pixels',
                'is_sensitive' => false,
            ],

            // Security configs
            [
                'key' => 'security.session_timeout_hours',
                'value' => ['value' => 24],
                'category' => ConfigCategory::SECURITY,
                'description' => 'Session timeout in hours',
                'is_sensitive' => false,
            ],
            [
                'key' => 'security.mfa_required_for_admins',
                'value' => ['value' => false],
                'category' => ConfigCategory::SECURITY,
                'description' => 'Require MFA for super admin users',
                'is_sensitive' => false,
            ],
            [
                'key' => 'security.password_min_length',
                'value' => ['value' => 8],
                'category' => ConfigCategory::SECURITY,
                'description' => 'Minimum password length',
                'is_sensitive' => false,
            ],
            [
                'key' => 'security.max_login_attempts',
                'value' => ['value' => 5],
                'category' => ConfigCategory::SECURITY,
                'description' => 'Maximum failed login attempts before lockout',
                'is_sensitive' => false,
            ],
            [
                'key' => 'security.lockout_duration_minutes',
                'value' => ['value' => 30],
                'category' => ConfigCategory::SECURITY,
                'description' => 'Account lockout duration in minutes',
                'is_sensitive' => false,
            ],

            // Notification configs
            [
                'key' => 'notifications.email_enabled',
                'value' => ['value' => true],
                'category' => ConfigCategory::NOTIFICATIONS,
                'description' => 'Enable email notifications',
                'is_sensitive' => false,
            ],
            [
                'key' => 'notifications.welcome_email_enabled',
                'value' => ['value' => true],
                'category' => ConfigCategory::NOTIFICATIONS,
                'description' => 'Send welcome email to new users',
                'is_sensitive' => false,
            ],

            // Integration configs
            [
                'key' => 'integrations.social_platforms_enabled',
                'value' => ['value' => ['facebook', 'instagram', 'twitter', 'linkedin']],
                'category' => ConfigCategory::INTEGRATIONS,
                'description' => 'List of enabled social platforms',
                'is_sensitive' => false,
            ],
        ];

        foreach ($configs as $config) {
            PlatformConfig::firstOrCreate(
                ['key' => $config['key']],
                [
                    'value' => $config['value'],
                    'category' => $config['category'],
                    'description' => $config['description'],
                    'is_sensitive' => $config['is_sensitive'],
                ]
            );
        }

        $this->command->info('Platform configs seeded successfully.');
    }
}
