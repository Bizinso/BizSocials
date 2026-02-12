<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

/**
 * ReleaseNoteStatus Enum
 *
 * Defines the publication status of a release note.
 *
 * - DRAFT: Being created/edited
 * - SCHEDULED: Scheduled for future publication
 * - PUBLISHED: Live and visible
 */
enum ReleaseNoteStatus: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case PUBLISHED = 'published';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SCHEDULED => 'Scheduled',
            self::PUBLISHED => 'Published',
        };
    }

    /**
     * Check if the release note is visible to users.
     * Only PUBLISHED release notes are visible.
     */
    public function isVisible(): bool
    {
        return $this === self::PUBLISHED;
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
