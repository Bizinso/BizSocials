<?php

declare(strict_types=1);

namespace App\Enums\Audit;

/**
 * DataRequestStatus Enum
 *
 * Defines the status of a GDPR data request.
 *
 * - PENDING: Request is awaiting processing
 * - PROCESSING: Request is being processed
 * - COMPLETED: Request has been fulfilled
 * - FAILED: Request processing failed
 * - CANCELLED: Request was cancelled
 */
enum DataRequestStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Check if this is a final status.
     * COMPLETED, FAILED, CANCELLED are considered final.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::CANCELLED], true);
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
