<?php

declare(strict_types=1);

namespace App\Enums\Billing;

/**
 * PaymentStatus Enum
 *
 * Defines the status of a payment transaction.
 *
 * - CREATED: Payment initiated
 * - AUTHORIZED: Authorized, not captured
 * - CAPTURED: Payment successful
 * - FAILED: Payment failed
 * - REFUNDED: Refunded
 */
enum PaymentStatus: string
{
    case CREATED = 'created';
    case AUTHORIZED = 'authorized';
    case CAPTURED = 'captured';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Created',
            self::AUTHORIZED => 'Authorized',
            self::CAPTURED => 'Captured',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
        };
    }

    /**
     * Check if the payment was successful (captured).
     */
    public function isSuccessful(): bool
    {
        return $this === self::CAPTURED;
    }

    /**
     * Check if this is a terminal/final state.
     * CAPTURED, FAILED, and REFUNDED are final states.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::CAPTURED, self::FAILED, self::REFUNDED], true);
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
