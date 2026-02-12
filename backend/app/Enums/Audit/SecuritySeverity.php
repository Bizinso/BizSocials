<?php

declare(strict_types=1);

namespace App\Enums\Audit;

/**
 * SecuritySeverity Enum
 *
 * Defines the severity level of a security event.
 *
 * - INFO: Informational event, no action required
 * - LOW: Low severity, should be monitored
 * - MEDIUM: Medium severity, may require attention
 * - HIGH: High severity, requires investigation
 * - CRITICAL: Critical severity, immediate action required
 */
enum SecuritySeverity: string
{
    case INFO = 'info';
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    /**
     * Get human-readable label for the severity.
     */
    public function label(): string
    {
        return match ($this) {
            self::INFO => 'Info',
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::CRITICAL => 'Critical',
        };
    }

    /**
     * Get the color associated with this severity level.
     */
    public function color(): string
    {
        return match ($this) {
            self::INFO => 'blue',
            self::LOW => 'green',
            self::MEDIUM => 'yellow',
            self::HIGH => 'orange',
            self::CRITICAL => 'red',
        };
    }

    /**
     * Get the weight (1-5) for this severity level.
     */
    public function weight(): int
    {
        return match ($this) {
            self::INFO => 1,
            self::LOW => 2,
            self::MEDIUM => 3,
            self::HIGH => 4,
            self::CRITICAL => 5,
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
