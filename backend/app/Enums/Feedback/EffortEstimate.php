<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

/**
 * EffortEstimate Enum
 *
 * Defines the estimated effort to implement feedback.
 *
 * - XS: Extra small (< 2 hours)
 * - S: Small (2-8 hours)
 * - M: Medium (1-3 days)
 * - L: Large (1-2 weeks)
 * - XL: Extra large (2+ weeks)
 */
enum EffortEstimate: string
{
    case XS = 'xs';
    case S = 's';
    case M = 'm';
    case L = 'l';
    case XL = 'xl';

    /**
     * Get human-readable label for the estimate.
     */
    public function label(): string
    {
        return match ($this) {
            self::XS => 'XS',
            self::S => 'S',
            self::M => 'M',
            self::L => 'L',
            self::XL => 'XL',
        };
    }

    /**
     * Get description with time estimate.
     */
    public function description(): string
    {
        return match ($this) {
            self::XS => 'Extra Small (< 2 hours)',
            self::S => 'Small (2-8 hours)',
            self::M => 'Medium (1-3 days)',
            self::L => 'Large (1-2 weeks)',
            self::XL => 'Extra Large (2+ weeks)',
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
