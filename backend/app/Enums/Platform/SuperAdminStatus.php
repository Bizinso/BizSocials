<?php

declare(strict_types=1);

namespace App\Enums\Platform;

/**
 * SuperAdminStatus Enum
 *
 * Defines the account status for platform administrators.
 *
 * - ACTIVE: Account is active and can log in
 * - INACTIVE: Account is deactivated (soft disable)
 * - SUSPENDED: Account is suspended due to policy violation
 */
enum SuperAdminStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case SUSPENDED = 'SUSPENDED';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
        };
    }

    /**
     * Get description for the status.
     */
    public function description(): string
    {
        return match ($this) {
            self::ACTIVE => 'Account is active and can log in',
            self::INACTIVE => 'Account is deactivated',
            self::SUSPENDED => 'Account is suspended due to policy violation',
        };
    }

    /**
     * Check if the account can log in.
     */
    public function canLogin(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Get all statuses as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
