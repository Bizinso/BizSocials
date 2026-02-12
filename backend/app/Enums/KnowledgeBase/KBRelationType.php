<?php

declare(strict_types=1);

namespace App\Enums\KnowledgeBase;

/**
 * KBRelationType Enum
 *
 * Defines the type of relationship between knowledge base articles.
 *
 * - RELATED: Articles that cover similar or related topics
 * - PREREQUISITE: Article that should be read before the current one
 * - NEXT_STEP: Article that should be read after the current one
 */
enum KBRelationType: string
{
    case RELATED = 'related';
    case PREREQUISITE = 'prerequisite';
    case NEXT_STEP = 'next_step';

    /**
     * Get human-readable label for the relation type.
     */
    public function label(): string
    {
        return match ($this) {
            self::RELATED => 'Related Article',
            self::PREREQUISITE => 'Prerequisite',
            self::NEXT_STEP => 'Next Step',
        };
    }

    /**
     * Get the inverse label for the relation type.
     * Used when displaying the reverse relationship.
     */
    public function inverseLabel(): string
    {
        return match ($this) {
            self::RELATED => 'Related To',
            self::PREREQUISITE => 'Required By',
            self::NEXT_STEP => 'Follows From',
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
