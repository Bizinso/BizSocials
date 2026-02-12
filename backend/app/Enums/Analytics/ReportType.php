<?php

declare(strict_types=1);

namespace App\Enums\Analytics;

enum ReportType: string
{
    case PERFORMANCE = 'performance';
    case ENGAGEMENT = 'engagement';
    case GROWTH = 'growth';
    case CONTENT = 'content';
    case AUDIENCE = 'audience';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::PERFORMANCE => 'Performance Report',
            self::ENGAGEMENT => 'Engagement Report',
            self::GROWTH => 'Growth Report',
            self::CONTENT => 'Content Report',
            self::AUDIENCE => 'Audience Report',
            self::CUSTOM => 'Custom Report',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PERFORMANCE => 'Overall performance metrics including impressions, reach, and engagement',
            self::ENGAGEMENT => 'Detailed engagement analysis with likes, comments, and shares breakdown',
            self::GROWTH => 'Follower growth trends and audience expansion metrics',
            self::CONTENT => 'Content performance analysis by type, platform, and posting time',
            self::AUDIENCE => 'Audience demographics and behavior insights',
            self::CUSTOM => 'Custom report with selected metrics and filters',
        };
    }

    /**
     * @return array<int, string>
     */
    public function defaultMetrics(): array
    {
        return match ($this) {
            self::PERFORMANCE => ['impressions', 'reach', 'engagements', 'engagement_rate'],
            self::ENGAGEMENT => ['likes', 'comments', 'shares', 'saves', 'engagement_rate'],
            self::GROWTH => ['followers_change', 'followers_end', 'posts_count'],
            self::CONTENT => ['posts_count', 'impressions', 'engagements', 'avg_engagement_per_post'],
            self::AUDIENCE => ['followers_end', 'reach', 'impressions'],
            self::CUSTOM => [],
        };
    }
}
