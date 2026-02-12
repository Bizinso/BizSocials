<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

/**
 * RoadmapCategory Enum
 *
 * Defines the category for roadmap items.
 *
 * - PUBLISHING: Content publishing features
 * - SCHEDULING: Scheduling and calendar features
 * - ANALYTICS: Analytics and reporting features
 * - INBOX: Inbox and engagement features
 * - TEAM_COLLABORATION: Team collaboration features
 * - INTEGRATIONS: Third-party integrations
 * - MOBILE_APP: Mobile application
 * - API: API and developer features
 * - PLATFORM: Core platform features
 * - SECURITY: Security features
 * - PERFORMANCE: Performance improvements
 */
enum RoadmapCategory: string
{
    case PUBLISHING = 'publishing';
    case SCHEDULING = 'scheduling';
    case ANALYTICS = 'analytics';
    case INBOX = 'inbox';
    case TEAM_COLLABORATION = 'team_collaboration';
    case INTEGRATIONS = 'integrations';
    case MOBILE_APP = 'mobile_app';
    case API = 'api';
    case PLATFORM = 'platform';
    case SECURITY = 'security';
    case PERFORMANCE = 'performance';

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
            self::PLATFORM => 'Platform',
            self::SECURITY => 'Security',
            self::PERFORMANCE => 'Performance',
        };
    }

    /**
     * Get color for the category.
     */
    public function color(): string
    {
        return match ($this) {
            self::PUBLISHING => '#3B82F6',
            self::SCHEDULING => '#8B5CF6',
            self::ANALYTICS => '#10B981',
            self::INBOX => '#F59E0B',
            self::TEAM_COLLABORATION => '#EC4899',
            self::INTEGRATIONS => '#6366F1',
            self::MOBILE_APP => '#14B8A6',
            self::API => '#F97316',
            self::PLATFORM => '#6B7280',
            self::SECURITY => '#EF4444',
            self::PERFORMANCE => '#22C55E',
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
