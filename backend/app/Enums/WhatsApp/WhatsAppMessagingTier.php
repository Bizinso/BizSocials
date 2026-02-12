<?php

declare(strict_types=1);

namespace App\Enums\WhatsApp;

enum WhatsAppMessagingTier: string
{
    case TIER_1K = 'tier_1k';
    case TIER_10K = 'tier_10k';
    case TIER_100K = 'tier_100k';
    case UNLIMITED = 'unlimited';

    public function label(): string
    {
        return match ($this) {
            self::TIER_1K => '1K Tier',
            self::TIER_10K => '10K Tier',
            self::TIER_100K => '100K Tier',
            self::UNLIMITED => 'Unlimited',
        };
    }

    public function dailyLimit(): int
    {
        return match ($this) {
            self::TIER_1K => 1000,
            self::TIER_10K => 10000,
            self::TIER_100K => 100000,
            self::UNLIMITED => PHP_INT_MAX,
        };
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
