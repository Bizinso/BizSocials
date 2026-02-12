<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

/**
 * FeedbackStatus Enum
 *
 * Defines the processing status of a feedback item.
 *
 * - NEW: Newly submitted, not yet reviewed
 * - UNDER_REVIEW: Being reviewed by admin
 * - PLANNED: Scheduled for implementation
 * - IN_PROGRESS: Currently being implemented
 * - SHIPPED: Implemented and deployed
 * - DECLINED: Will not be implemented
 * - DUPLICATE: Duplicate of another feedback
 * - ARCHIVED: No longer relevant
 */
enum FeedbackStatus: string
{
    case NEW = 'new';
    case UNDER_REVIEW = 'under_review';
    case PLANNED = 'planned';
    case IN_PROGRESS = 'in_progress';
    case SHIPPED = 'shipped';
    case DECLINED = 'declined';
    case DUPLICATE = 'duplicate';
    case ARCHIVED = 'archived';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New',
            self::UNDER_REVIEW => 'Under Review',
            self::PLANNED => 'Planned',
            self::IN_PROGRESS => 'In Progress',
            self::SHIPPED => 'Shipped',
            self::DECLINED => 'Declined',
            self::DUPLICATE => 'Duplicate',
            self::ARCHIVED => 'Archived',
        };
    }

    /**
     * Check if the feedback is in an open state.
     * NEW and UNDER_REVIEW are considered open.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::NEW, self::UNDER_REVIEW], true);
    }

    /**
     * Check if the feedback is in an active state.
     * PLANNED and IN_PROGRESS are considered active.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::PLANNED, self::IN_PROGRESS], true);
    }

    /**
     * Check if the feedback is in a closed state.
     * SHIPPED, DECLINED, DUPLICATE, and ARCHIVED are closed.
     */
    public function isClosed(): bool
    {
        return in_array($this, [
            self::SHIPPED,
            self::DECLINED,
            self::DUPLICATE,
            self::ARCHIVED,
        ], true);
    }

    /**
     * Check if the status can transition to the target status.
     *
     * Valid transitions:
     * - NEW -> UNDER_REVIEW, DUPLICATE, ARCHIVED
     * - UNDER_REVIEW -> PLANNED, DECLINED, DUPLICATE, ARCHIVED
     * - PLANNED -> IN_PROGRESS, DECLINED, ARCHIVED
     * - IN_PROGRESS -> SHIPPED, PLANNED, ARCHIVED
     * - SHIPPED -> ARCHIVED
     * - DECLINED -> UNDER_REVIEW, ARCHIVED
     * - DUPLICATE -> ARCHIVED
     * - ARCHIVED -> NEW (can reopen)
     */
    public function canTransitionTo(FeedbackStatus $status): bool
    {
        if ($this === $status) {
            return false;
        }

        return match ($this) {
            self::NEW => in_array($status, [self::UNDER_REVIEW, self::DUPLICATE, self::ARCHIVED], true),
            self::UNDER_REVIEW => in_array($status, [self::PLANNED, self::DECLINED, self::DUPLICATE, self::ARCHIVED], true),
            self::PLANNED => in_array($status, [self::IN_PROGRESS, self::DECLINED, self::ARCHIVED], true),
            self::IN_PROGRESS => in_array($status, [self::SHIPPED, self::PLANNED, self::ARCHIVED], true),
            self::SHIPPED => in_array($status, [self::ARCHIVED], true),
            self::DECLINED => in_array($status, [self::UNDER_REVIEW, self::ARCHIVED], true),
            self::DUPLICATE => in_array($status, [self::ARCHIVED], true),
            self::ARCHIVED => in_array($status, [self::NEW], true),
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
