<?php

declare(strict_types=1);

namespace App\Enums\Listening;

/**
 * SentimentType Enum
 *
 * Defines the sentiment classification for keyword mentions.
 *
 * - POSITIVE: Content has positive sentiment
 * - NEGATIVE: Content has negative sentiment
 * - NEUTRAL: Content has neutral sentiment
 * - UNKNOWN: Sentiment could not be determined
 */
enum SentimentType: string
{
    case POSITIVE = 'positive';
    case NEGATIVE = 'negative';
    case NEUTRAL = 'neutral';
    case UNKNOWN = 'unknown';

    /**
     * Get human-readable label for the sentiment.
     */
    public function label(): string
    {
        return match ($this) {
            self::POSITIVE => 'Positive',
            self::NEGATIVE => 'Negative',
            self::NEUTRAL => 'Neutral',
            self::UNKNOWN => 'Unknown',
        };
    }

    /**
     * Get color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::POSITIVE => 'green',
            self::NEGATIVE => 'red',
            self::NEUTRAL => 'gray',
            self::UNKNOWN => 'yellow',
        };
    }

    /**
     * Get icon for UI display.
     */
    public function icon(): string
    {
        return match ($this) {
            self::POSITIVE => 'thumbs-up',
            self::NEGATIVE => 'thumbs-down',
            self::NEUTRAL => 'minus',
            self::UNKNOWN => 'question-circle',
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
