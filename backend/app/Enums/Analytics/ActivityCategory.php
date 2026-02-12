<?php

declare(strict_types=1);

namespace App\Enums\Analytics;

enum ActivityCategory: string
{
    case CONTENT_CREATION = 'content_creation';
    case PUBLISHING = 'publishing';
    case ENGAGEMENT = 'engagement';
    case ANALYTICS = 'analytics';
    case SETTINGS = 'settings';
    case AI_FEATURES = 'ai_features';
    case AUTHENTICATION = 'authentication';

    public function label(): string
    {
        return match ($this) {
            self::CONTENT_CREATION => 'Content Creation',
            self::PUBLISHING => 'Publishing',
            self::ENGAGEMENT => 'Engagement',
            self::ANALYTICS => 'Analytics',
            self::SETTINGS => 'Settings',
            self::AI_FEATURES => 'AI Features',
            self::AUTHENTICATION => 'Authentication',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CONTENT_CREATION => 'pencil',
            self::PUBLISHING => 'send',
            self::ENGAGEMENT => 'message-circle',
            self::ANALYTICS => 'bar-chart',
            self::SETTINGS => 'settings',
            self::AI_FEATURES => 'sparkles',
            self::AUTHENTICATION => 'key',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CONTENT_CREATION => 'blue',
            self::PUBLISHING => 'green',
            self::ENGAGEMENT => 'purple',
            self::ANALYTICS => 'orange',
            self::SETTINGS => 'gray',
            self::AI_FEATURES => 'pink',
            self::AUTHENTICATION => 'slate',
        };
    }
}
