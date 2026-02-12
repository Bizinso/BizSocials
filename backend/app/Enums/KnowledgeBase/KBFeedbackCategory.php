<?php

declare(strict_types=1);

namespace App\Enums\KnowledgeBase;

/**
 * KBFeedbackCategory Enum
 *
 * Defines the category of feedback given on knowledge base articles.
 *
 * - OUTDATED: Content is no longer current
 * - INCOMPLETE: Missing important information
 * - UNCLEAR: Hard to understand
 * - INCORRECT: Contains errors or wrong information
 * - HELPFUL: Positive feedback indicating the article was useful
 * - OTHER: Other types of feedback
 */
enum KBFeedbackCategory: string
{
    case OUTDATED = 'outdated';
    case INCOMPLETE = 'incomplete';
    case UNCLEAR = 'unclear';
    case INCORRECT = 'incorrect';
    case HELPFUL = 'helpful';
    case OTHER = 'other';

    /**
     * Get human-readable label for the feedback category.
     */
    public function label(): string
    {
        return match ($this) {
            self::OUTDATED => 'Outdated Content',
            self::INCOMPLETE => 'Incomplete Information',
            self::UNCLEAR => 'Unclear/Confusing',
            self::INCORRECT => 'Incorrect Information',
            self::HELPFUL => 'Helpful',
            self::OTHER => 'Other',
        };
    }

    /**
     * Check if this is positive feedback.
     * Only HELPFUL is considered positive.
     */
    public function isPositive(): bool
    {
        return $this === self::HELPFUL;
    }

    /**
     * Check if this is negative feedback.
     * OUTDATED, INCOMPLETE, UNCLEAR, and INCORRECT are negative.
     */
    public function isNegative(): bool
    {
        return in_array($this, [
            self::OUTDATED,
            self::INCOMPLETE,
            self::UNCLEAR,
            self::INCORRECT,
        ], true);
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
