<?php

declare(strict_types=1);

namespace App\Enums\WhatsApp;

enum WhatsAppConversationStatus: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case RESOLVED = 'resolved';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::PENDING => 'Pending',
            self::RESOLVED => 'Resolved',
            self::ARCHIVED => 'Archived',
        };
    }

    public function isOpen(): bool
    {
        return match ($this) {
            self::ACTIVE, self::PENDING => true,
            self::RESOLVED, self::ARCHIVED => false,
        };
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
