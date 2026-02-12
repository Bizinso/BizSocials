<?php

declare(strict_types=1);

namespace App\Enums\Audit;

/**
 * SessionStatus Enum
 *
 * Defines the status of a user session.
 *
 * - ACTIVE: Session is currently active
 * - EXPIRED: Session has expired
 * - REVOKED: Session was manually revoked
 * - LOGGED_OUT: User logged out normally
 */
enum SessionStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';
    case LOGGED_OUT = 'logged_out';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::EXPIRED => 'Expired',
            self::REVOKED => 'Revoked',
            self::LOGGED_OUT => 'Logged Out',
        };
    }

    /**
     * Check if the session is currently active.
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
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
