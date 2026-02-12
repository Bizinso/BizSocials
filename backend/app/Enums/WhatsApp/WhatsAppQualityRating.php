<?php

declare(strict_types=1);

namespace App\Enums\WhatsApp;

enum WhatsAppQualityRating: string
{
    case GREEN = 'green';
    case YELLOW = 'yellow';
    case RED = 'red';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::GREEN => 'Green',
            self::YELLOW => 'Yellow',
            self::RED => 'Red',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::GREEN => '#22C55E',
            self::YELLOW => '#EAB308',
            self::RED => '#EF4444',
            self::UNKNOWN => '#6B7280',
        };
    }

    public function isHealthy(): bool
    {
        return match ($this) {
            self::GREEN, self::UNKNOWN => true,
            self::YELLOW, self::RED => false,
        };
    }

    public function requiresAction(): bool
    {
        return match ($this) {
            self::YELLOW, self::RED => true,
            self::GREEN, self::UNKNOWN => false,
        };
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
