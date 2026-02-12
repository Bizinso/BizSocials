<?php

declare(strict_types=1);

namespace App\Enums\Content;

/**
 * PostStatus Enum
 *
 * Defines the workflow status of a social media post.
 *
 * - DRAFT: Post is being created/edited
 * - SUBMITTED: Post submitted for approval
 * - APPROVED: Post approved for publishing
 * - REJECTED: Post rejected, needs revision
 * - SCHEDULED: Post scheduled for future publishing
 * - PUBLISHING: Post is being published to platforms
 * - PUBLISHED: Post successfully published
 * - FAILED: Publishing failed
 * - CANCELLED: Post cancelled
 */
enum PostStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case SCHEDULED = 'scheduled';
    case PUBLISHING = 'publishing';
    case PUBLISHED = 'published';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::SCHEDULED => 'Scheduled',
            self::PUBLISHING => 'Publishing',
            self::PUBLISHED => 'Published',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Check if the post can be edited in this status.
     * Only DRAFT and REJECTED posts can be edited.
     */
    public function canEdit(): bool
    {
        return match ($this) {
            self::DRAFT, self::REJECTED => true,
            default => false,
        };
    }

    /**
     * Check if the post can be deleted in this status.
     * Published posts cannot be deleted.
     */
    public function canDelete(): bool
    {
        return $this !== self::PUBLISHED;
    }

    /**
     * Check if the post can be published in this status.
     * Only APPROVED and SCHEDULED posts can be published.
     */
    public function canPublish(): bool
    {
        return match ($this) {
            self::APPROVED, self::SCHEDULED => true,
            default => false,
        };
    }

    /**
     * Check if this is a terminal status.
     * Terminal statuses cannot transition to other statuses.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::PUBLISHED, self::CANCELLED => true,
            default => false,
        };
    }

    /**
     * Check if the status can transition to the target status.
     *
     * Transition rules:
     * - DRAFT → SUBMITTED, CANCELLED
     * - SUBMITTED → APPROVED, REJECTED
     * - APPROVED → SCHEDULED, PUBLISHING
     * - REJECTED → DRAFT (resubmit)
     * - SCHEDULED → PUBLISHING, CANCELLED, FAILED
     * - PUBLISHING → PUBLISHED, FAILED
     * - FAILED → PUBLISHING (retry)
     * - PUBLISHED, CANCELLED → none (terminal)
     */
    public function canTransitionTo(PostStatus $status): bool
    {
        return match ($this) {
            self::DRAFT => in_array($status, [self::SUBMITTED, self::CANCELLED], true),
            self::SUBMITTED => in_array($status, [self::APPROVED, self::REJECTED], true),
            self::APPROVED => in_array($status, [self::SCHEDULED, self::PUBLISHING], true),
            self::REJECTED => $status === self::DRAFT,
            self::SCHEDULED => in_array($status, [self::PUBLISHING, self::CANCELLED, self::FAILED], true),
            self::PUBLISHING => in_array($status, [self::PUBLISHED, self::FAILED], true),
            self::FAILED => $status === self::PUBLISHING,
            self::PUBLISHED, self::CANCELLED => false,
        };
    }

    /**
     * Get all statuses as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
