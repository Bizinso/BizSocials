<?php

declare(strict_types=1);

namespace App\Enums\Support;

/**
 * SupportTicketPriority Enum
 *
 * Defines the priority levels for support tickets.
 *
 * - LOW: Minor issue, no rush
 * - MEDIUM: Standard priority
 * - HIGH: Important, needs attention soon
 * - URGENT: Critical, needs immediate attention
 */
enum SupportTicketPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    /**
     * Get human-readable label for the priority.
     */
    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
        };
    }

    /**
     * Get the numeric weight for sorting (higher = more urgent).
     */
    public function weight(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::URGENT => 4,
        };
    }

    /**
     * Get the color code for the priority (for UI display).
     */
    public function color(): string
    {
        return match ($this) {
            self::LOW => '#6B7280',     // Gray
            self::MEDIUM => '#3B82F6',  // Blue
            self::HIGH => '#F59E0B',    // Amber
            self::URGENT => '#EF4444',  // Red
        };
    }

    /**
     * Get the SLA hours for this priority level.
     */
    public function slaHours(): int
    {
        return match ($this) {
            self::LOW => 72,
            self::MEDIUM => 24,
            self::HIGH => 8,
            self::URGENT => 4,
        };
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
