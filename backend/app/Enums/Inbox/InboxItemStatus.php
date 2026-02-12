<?php

declare(strict_types=1);

namespace App\Enums\Inbox;

/**
 * InboxItemStatus Enum
 *
 * Defines the status of an inbox item in the workflow.
 *
 * - UNREAD: New item that hasn't been viewed
 * - READ: Item has been viewed
 * - RESOLVED: Item has been handled/completed
 * - ARCHIVED: Item has been archived
 */
enum InboxItemStatus: string
{
    case UNREAD = 'unread';
    case READ = 'read';
    case RESOLVED = 'resolved';
    case ARCHIVED = 'archived';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::UNREAD => 'Unread',
            self::READ => 'Read',
            self::RESOLVED => 'Resolved',
            self::ARCHIVED => 'Archived',
        };
    }

    /**
     * Check if this status is considered active.
     * ARCHIVED is not active.
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::UNREAD, self::READ, self::RESOLVED => true,
            self::ARCHIVED => false,
        };
    }

    /**
     * Check if the status can transition to the target status.
     *
     * Transition rules:
     * - UNREAD -> READ, ARCHIVED
     * - READ -> RESOLVED, ARCHIVED
     * - RESOLVED -> READ (reopen), ARCHIVED
     * - ARCHIVED -> READ (reopen)
     */
    public function canTransitionTo(InboxItemStatus $status): bool
    {
        return match ($this) {
            self::UNREAD => in_array($status, [self::READ, self::ARCHIVED], true),
            self::READ => in_array($status, [self::RESOLVED, self::ARCHIVED], true),
            self::RESOLVED => in_array($status, [self::READ, self::ARCHIVED], true),
            self::ARCHIVED => $status === self::READ,
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
