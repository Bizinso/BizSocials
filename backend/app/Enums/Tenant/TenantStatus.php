<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

/**
 * TenantStatus Enum
 *
 * Defines the lifecycle status of a tenant in the platform.
 *
 * - PENDING: Tenant has signed up but not yet activated
 * - ACTIVE: Tenant is active and can access the platform
 * - SUSPENDED: Tenant is temporarily suspended (payment issues, policy violation)
 * - TERMINATED: Tenant has been permanently terminated
 */
enum TenantStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case TERMINATED = 'terminated';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::TERMINATED => 'Terminated',
        };
    }

    /**
     * Check if the tenant can access the platform.
     */
    public function canAccess(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if the tenant can transition to a given status.
     *
     * Valid transitions:
     * - PENDING -> ACTIVE
     * - ACTIVE -> SUSPENDED
     * - ACTIVE -> TERMINATED
     * - SUSPENDED -> ACTIVE
     * - SUSPENDED -> TERMINATED
     */
    public function canTransitionTo(TenantStatus $status): bool
    {
        return match ($this) {
            self::PENDING => $status === self::ACTIVE,
            self::ACTIVE => in_array($status, [self::SUSPENDED, self::TERMINATED], true),
            self::SUSPENDED => in_array($status, [self::ACTIVE, self::TERMINATED], true),
            self::TERMINATED => false,
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
