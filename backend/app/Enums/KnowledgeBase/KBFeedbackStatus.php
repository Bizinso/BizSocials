<?php

declare(strict_types=1);

namespace App\Enums\KnowledgeBase;

/**
 * KBFeedbackStatus Enum
 *
 * Defines the processing status of article feedback.
 *
 * - PENDING: Feedback has been submitted but not yet reviewed
 * - REVIEWED: Feedback has been seen by an admin
 * - ACTIONED: Changes have been made based on the feedback
 * - DISMISSED: Feedback was reviewed but no action taken
 */
enum KBFeedbackStatus: string
{
    case PENDING = 'pending';
    case REVIEWED = 'reviewed';
    case ACTIONED = 'actioned';
    case DISMISSED = 'dismissed';

    /**
     * Get human-readable label for the feedback status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::REVIEWED => 'Reviewed',
            self::ACTIONED => 'Actioned',
            self::DISMISSED => 'Dismissed',
        };
    }

    /**
     * Check if the feedback is still open (not processed).
     * Only PENDING feedback is considered open.
     */
    public function isOpen(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if the feedback has been closed/processed.
     * REVIEWED, ACTIONED, and DISMISSED are closed statuses.
     */
    public function isClosed(): bool
    {
        return in_array($this, [
            self::REVIEWED,
            self::ACTIONED,
            self::DISMISSED,
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
