<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

/**
 * AdminPriority Enum
 *
 * Defines the priority level assigned by administrators.
 *
 * - LOW: Low priority, address when resources available
 * - MEDIUM: Medium priority, schedule soon
 * - HIGH: High priority, address promptly
 * - CRITICAL: Critical priority, immediate attention
 */
enum AdminPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    /**
     * Get human-readable label for the priority.
     */
    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::CRITICAL => 'Critical',
        };
    }

    /**
     * Get the weight for scoring/sorting.
     */
    public function weight(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::CRITICAL => 4,
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
