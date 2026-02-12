<?php

declare(strict_types=1);

namespace App\Enums\Billing;

/**
 * InvoiceStatus Enum
 *
 * Defines the status of an invoice.
 *
 * - DRAFT: Being prepared
 * - ISSUED: Sent to customer
 * - PAID: Payment received
 * - CANCELLED: Cancelled
 * - EXPIRED: Past due date
 */
enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::ISSUED => 'Issued',
            self::PAID => 'Paid',
            self::CANCELLED => 'Cancelled',
            self::EXPIRED => 'Expired',
        };
    }

    /**
     * Check if the invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this === self::PAID;
    }

    /**
     * Check if the invoice can be paid (only ISSUED invoices are payable).
     */
    public function isPayable(): bool
    {
        return $this === self::ISSUED;
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
