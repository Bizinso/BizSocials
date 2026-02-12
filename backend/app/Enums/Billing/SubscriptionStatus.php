<?php

declare(strict_types=1);

namespace App\Enums\Billing;

/**
 * SubscriptionStatus Enum
 *
 * Defines the lifecycle status of a subscription.
 *
 * - CREATED: Just created, awaiting payment
 * - AUTHENTICATED: Payment authenticated
 * - ACTIVE: Active subscription
 * - PENDING: Payment due, grace period
 * - HALTED: Payment failed multiple times
 * - CANCELLED: Cancelled by user
 * - COMPLETED: Natural end (yearly plan)
 * - EXPIRED: Authentication expired
 */
enum SubscriptionStatus: string
{
    case CREATED = 'created';
    case AUTHENTICATED = 'authenticated';
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case HALTED = 'halted';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Created',
            self::AUTHENTICATED => 'Authenticated',
            self::ACTIVE => 'Active',
            self::PENDING => 'Pending',
            self::HALTED => 'Halted',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
            self::EXPIRED => 'Expired',
        };
    }

    /**
     * Check if the subscription is currently active.
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if the subscription has platform access.
     * ACTIVE, PENDING, and AUTHENTICATED states allow access.
     */
    public function hasAccess(): bool
    {
        return in_array($this, [self::ACTIVE, self::PENDING, self::AUTHENTICATED], true);
    }

    /**
     * Check if this is a terminal/final state.
     * CANCELLED, COMPLETED, and EXPIRED are final states.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::CANCELLED, self::COMPLETED, self::EXPIRED], true);
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
