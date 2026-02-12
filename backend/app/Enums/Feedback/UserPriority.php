<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

/**
 * UserPriority Enum
 *
 * Defines the priority level as perceived by the user submitting feedback.
 *
 * - NICE_TO_HAVE: Would be nice but not essential
 * - IMPORTANT: Important for workflow
 * - CRITICAL: Blocking or critical issue
 */
enum UserPriority: string
{
    case NICE_TO_HAVE = 'nice_to_have';
    case IMPORTANT = 'important';
    case CRITICAL = 'critical';

    /**
     * Get human-readable label for the priority.
     */
    public function label(): string
    {
        return match ($this) {
            self::NICE_TO_HAVE => 'Nice to Have',
            self::IMPORTANT => 'Important',
            self::CRITICAL => 'Critical',
        };
    }

    /**
     * Get the weight for scoring/sorting.
     */
    public function weight(): int
    {
        return match ($this) {
            self::NICE_TO_HAVE => 1,
            self::IMPORTANT => 2,
            self::CRITICAL => 3,
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
