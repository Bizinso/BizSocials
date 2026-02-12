<?php

declare(strict_types=1);

namespace App\Enums\Billing;

/**
 * BillingCycle Enum
 *
 * Defines the billing interval for subscriptions.
 *
 * - MONTHLY: Billed every month
 * - YEARLY: Billed once per year (with discount)
 */
enum BillingCycle: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    /**
     * Get human-readable label for the billing cycle.
     */
    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Monthly',
            self::YEARLY => 'Yearly',
        };
    }

    /**
     * Get the interval in months.
     */
    public function intervalMonths(): int
    {
        return match ($this) {
            self::MONTHLY => 1,
            self::YEARLY => 12,
        };
    }

    /**
     * Get the discount label for this billing cycle.
     */
    public function discountLabel(): string
    {
        return match ($this) {
            self::MONTHLY => '',
            self::YEARLY => '2 months free',
        };
    }

    /**
     * Get all billing cycles as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
