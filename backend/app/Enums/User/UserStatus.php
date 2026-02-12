<?php

declare(strict_types=1);

namespace App\Enums\User;

/**
 * UserStatus Enum
 *
 * Defines the lifecycle status of a user in the platform.
 *
 * - PENDING: User has been invited but has not yet accepted/verified
 * - ACTIVE: User is active and can access the platform
 * - SUSPENDED: User is temporarily suspended
 * - DEACTIVATED: User has been permanently deactivated
 */
enum UserStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case DEACTIVATED = 'deactivated';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::DEACTIVATED => 'Deactivated',
        };
    }

    /**
     * Check if the user can log in.
     * Only ACTIVE users can log in.
     */
    public function canLogin(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if the user can transition to a given status.
     *
     * Valid transitions:
     * - PENDING -> ACTIVE (accepted invitation/verified email)
     * - ACTIVE -> SUSPENDED
     * - ACTIVE -> DEACTIVATED
     * - SUSPENDED -> ACTIVE
     * - SUSPENDED -> DEACTIVATED
     */
    public function canTransitionTo(UserStatus $status): bool
    {
        return match ($this) {
            self::PENDING => $status === self::ACTIVE,
            self::ACTIVE => in_array($status, [self::SUSPENDED, self::DEACTIVATED], true),
            self::SUSPENDED => in_array($status, [self::ACTIVE, self::DEACTIVATED], true),
            self::DEACTIVATED => false,
        };
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
