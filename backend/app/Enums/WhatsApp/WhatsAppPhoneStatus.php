<?php

declare(strict_types=1);

namespace App\Enums\WhatsApp;

enum WhatsAppPhoneStatus: string
{
    case ACTIVE = 'active';
    case FLAGGED = 'flagged';
    case RESTRICTED = 'restricted';
    case DISABLED = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::FLAGGED => 'Flagged',
            self::RESTRICTED => 'Restricted',
            self::DISABLED => 'Disabled',
        };
    }

    public function canSend(): bool
    {
        return $this === self::ACTIVE;
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
