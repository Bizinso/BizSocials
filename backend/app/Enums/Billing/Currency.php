<?php

declare(strict_types=1);

namespace App\Enums\Billing;

/**
 * Currency Enum
 *
 * Defines the supported currencies for billing.
 *
 * - INR: Indian Rupee
 * - USD: US Dollar
 */
enum Currency: string
{
    case INR = 'INR';
    case USD = 'USD';

    /**
     * Get human-readable label for the currency.
     */
    public function label(): string
    {
        return match ($this) {
            self::INR => 'Indian Rupee',
            self::USD => 'US Dollar',
        };
    }

    /**
     * Get the currency symbol.
     */
    public function symbol(): string
    {
        return match ($this) {
            self::INR => '₹',
            self::USD => '$',
        };
    }

    /**
     * Get the number of minor units (paise/cents) per major unit.
     */
    public function minorUnits(): int
    {
        return 100;
    }

    /**
     * Get all currencies as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
