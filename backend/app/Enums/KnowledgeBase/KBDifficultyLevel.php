<?php

declare(strict_types=1);

namespace App\Enums\KnowledgeBase;

/**
 * KBDifficultyLevel Enum
 *
 * Defines the difficulty level of knowledge base articles.
 *
 * - BEGINNER: Suitable for new users with no prior experience
 * - INTERMEDIATE: Requires some familiarity with the platform
 * - ADVANCED: For experienced users with deep knowledge
 */
enum KBDifficultyLevel: string
{
    case BEGINNER = 'beginner';
    case INTERMEDIATE = 'intermediate';
    case ADVANCED = 'advanced';

    /**
     * Get human-readable label for the difficulty level.
     */
    public function label(): string
    {
        return match ($this) {
            self::BEGINNER => 'Beginner',
            self::INTERMEDIATE => 'Intermediate',
            self::ADVANCED => 'Advanced',
        };
    }

    /**
     * Get sort order for the difficulty level.
     * Lower numbers come first.
     */
    public function sortOrder(): int
    {
        return match ($this) {
            self::BEGINNER => 1,
            self::INTERMEDIATE => 2,
            self::ADVANCED => 3,
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
