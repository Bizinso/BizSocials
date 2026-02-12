<?php

declare(strict_types=1);

namespace App\Enums\KnowledgeBase;

/**
 * KBArticleStatus Enum
 *
 * Defines the publication status of a knowledge base article.
 *
 * - DRAFT: Article is being created/edited
 * - PUBLISHED: Article is live and visible
 * - ARCHIVED: Article is no longer active
 */
enum KBArticleStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
        };
    }

    /**
     * Check if the article is visible to users.
     * Only PUBLISHED articles are visible.
     */
    public function isVisible(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * Check if the status can transition to the target status.
     *
     * Valid transitions:
     * - DRAFT -> PUBLISHED, ARCHIVED
     * - PUBLISHED -> DRAFT, ARCHIVED
     * - ARCHIVED -> DRAFT, PUBLISHED
     */
    public function canTransitionTo(KBArticleStatus $status): bool
    {
        if ($this === $status) {
            return false;
        }

        return match ($this) {
            self::DRAFT => in_array($status, [self::PUBLISHED, self::ARCHIVED], true),
            self::PUBLISHED => in_array($status, [self::DRAFT, self::ARCHIVED], true),
            self::ARCHIVED => in_array($status, [self::DRAFT, self::PUBLISHED], true),
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
