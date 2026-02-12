<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

/**
 * VoteType Enum
 *
 * Defines the type of vote on feedback.
 *
 * - UPVOTE: Positive vote (support/agreement)
 * - DOWNVOTE: Negative vote (disagreement)
 */
enum VoteType: string
{
    case UPVOTE = 'upvote';
    case DOWNVOTE = 'downvote';

    /**
     * Get human-readable label for the vote type.
     */
    public function label(): string
    {
        return match ($this) {
            self::UPVOTE => 'Upvote',
            self::DOWNVOTE => 'Downvote',
        };
    }

    /**
     * Get the numeric value (+1 or -1).
     */
    public function value(): int
    {
        return match ($this) {
            self::UPVOTE => 1,
            self::DOWNVOTE => -1,
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
