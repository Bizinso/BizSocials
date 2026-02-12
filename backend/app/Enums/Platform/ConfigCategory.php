<?php

declare(strict_types=1);

namespace App\Enums\Platform;

/**
 * ConfigCategory Enum
 *
 * Defines the categories for platform configuration settings.
 *
 * - GENERAL: General platform settings (name, URLs, contact info)
 * - FEATURES: Feature-specific configuration
 * - INTEGRATIONS: Third-party integration settings
 * - LIMITS: Platform-wide limits and quotas
 * - NOTIFICATIONS: Notification and email settings
 * - SECURITY: Security-related configuration
 */
enum ConfigCategory: string
{
    case GENERAL = 'general';
    case FEATURES = 'features';
    case INTEGRATIONS = 'integrations';
    case LIMITS = 'limits';
    case NOTIFICATIONS = 'notifications';
    case SECURITY = 'security';

    /**
     * Get human-readable label for the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::GENERAL => 'General',
            self::FEATURES => 'Features',
            self::INTEGRATIONS => 'Integrations',
            self::LIMITS => 'Limits',
            self::NOTIFICATIONS => 'Notifications',
            self::SECURITY => 'Security',
        };
    }

    /**
     * Get description for the category.
     */
    public function description(): string
    {
        return match ($this) {
            self::GENERAL => 'General platform settings like name, URLs, and contact info',
            self::FEATURES => 'Feature-specific configuration options',
            self::INTEGRATIONS => 'Third-party integration settings and API keys',
            self::LIMITS => 'Platform-wide limits and quotas',
            self::NOTIFICATIONS => 'Notification and email configuration',
            self::SECURITY => 'Security-related settings and policies',
        };
    }

    /**
     * Check if the category contains sensitive data.
     */
    public function mayContainSensitiveData(): bool
    {
        return in_array($this, [self::SECURITY, self::INTEGRATIONS], true);
    }

    /**
     * Get all categories as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
