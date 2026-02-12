<?php

declare(strict_types=1);

namespace App\Enums\Billing;

/**
 * PaymentMethodType Enum
 *
 * Defines the type of payment method.
 *
 * - CARD: Credit/Debit card
 * - UPI: UPI payment
 * - NETBANKING: Net banking
 * - WALLET: Digital wallet
 * - EMANDATE: Electronic mandate (auto-debit)
 */
enum PaymentMethodType: string
{
    case CARD = 'card';
    case UPI = 'upi';
    case NETBANKING = 'netbanking';
    case WALLET = 'wallet';
    case EMANDATE = 'emandate';

    /**
     * Get human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::CARD => 'Card',
            self::UPI => 'UPI',
            self::NETBANKING => 'Net Banking',
            self::WALLET => 'Wallet',
            self::EMANDATE => 'e-Mandate',
        };
    }

    /**
     * Get the icon identifier for this payment method.
     */
    public function icon(): string
    {
        return match ($this) {
            self::CARD => 'credit-card',
            self::UPI => 'smartphone',
            self::NETBANKING => 'building',
            self::WALLET => 'wallet',
            self::EMANDATE => 'repeat',
        };
    }

    /**
     * Check if this payment method supports recurring payments.
     */
    public function supportsRecurring(): bool
    {
        return in_array($this, [self::CARD, self::EMANDATE], true);
    }

    /**
     * Get all types as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
