<?php

declare(strict_types=1);

namespace App\Enums\WhatsApp;

enum WhatsAppTemplateCategory: string
{
    case MARKETING = 'marketing';
    case UTILITY = 'utility';
    case AUTHENTICATION = 'authentication';

    public function label(): string
    {
        return match ($this) {
            self::MARKETING => 'Marketing',
            self::UTILITY => 'Utility',
            self::AUTHENTICATION => 'Authentication',
        };
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
