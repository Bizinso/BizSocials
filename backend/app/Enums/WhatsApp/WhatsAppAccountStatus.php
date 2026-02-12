<?php

declare(strict_types=1);

namespace App\Enums\WhatsApp;

enum WhatsAppAccountStatus: string
{
    case PENDING_VERIFICATION = 'pending_verification';
    case VERIFIED = 'verified';
    case SUSPENDED = 'suspended';
    case BANNED = 'banned';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_VERIFICATION => 'Pending Verification',
            self::VERIFIED => 'Verified',
            self::SUSPENDED => 'Suspended',
            self::BANNED => 'Banned',
        };
    }

    public function isOperational(): bool
    {
        return $this === self::VERIFIED;
    }

    public function canSendMessages(): bool
    {
        return $this === self::VERIFIED;
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
