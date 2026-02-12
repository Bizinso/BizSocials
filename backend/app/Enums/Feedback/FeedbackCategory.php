<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

/**
 * FeedbackCategory Enum
 *
 * Defines the product area category for feedback.
 *
 * - PUBLISHING: Content publishing features
 * - SCHEDULING: Scheduling and calendar features
 * - ANALYTICS: Analytics and reporting features
 * - INBOX: Inbox and engagement features
 * - TEAM_COLLABORATION: Team collaboration features
 * - INTEGRATIONS: Third-party integrations
 * - MOBILE_APP: Mobile application
 * - API: API and developer features
 * - BILLING: Billing and subscription features
 * - ONBOARDING: Onboarding experience
 * - GENERAL: General feedback
 */
enum FeedbackCategory: string
{
    case PUBLISHING = 'publishing';
    case SCHEDULING = 'scheduling';
    case ANALYTICS = 'analytics';
    case INBOX = 'inbox';
    case TEAM_COLLABORATION = 'team_collaboration';
    case INTEGRATIONS = 'integrations';
    case MOBILE_APP = 'mobile_app';
    case API = 'api';
    case BILLING = 'billing';
    case ONBOARDING = 'onboarding';
    case GENERAL = 'general';

    /**
     * Get human-readable label for the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::PUBLISHING => 'Publishing',
            self::SCHEDULING => 'Scheduling',
            self::ANALYTICS => 'Analytics',
            self::INBOX => 'Inbox',
            self::TEAM_COLLABORATION => 'Team Collaboration',
            self::INTEGRATIONS => 'Integrations',
            self::MOBILE_APP => 'Mobile App',
            self::API => 'API',
            self::BILLING => 'Billing',
            self::ONBOARDING => 'Onboarding',
            self::GENERAL => 'General',
        };
    }

    /**
     * Get all values as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
