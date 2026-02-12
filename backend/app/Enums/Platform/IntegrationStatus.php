<?php

declare(strict_types=1);

namespace App\Enums\Platform;

enum IntegrationStatus: string
{
    case ACTIVE = 'active';
    case MAINTENANCE = 'maintenance';
    case DISABLED = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::MAINTENANCE => 'Maintenance',
            self::DISABLED => 'Disabled',
        };
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
