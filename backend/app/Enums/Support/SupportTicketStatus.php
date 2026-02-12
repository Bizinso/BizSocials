<?php

declare(strict_types=1);

namespace App\Enums\Support;

/**
 * SupportTicketStatus Enum
 *
 * Defines the status of a support ticket throughout its lifecycle.
 *
 * - NEW: Newly submitted, not yet assigned
 * - OPEN: Assigned and being worked on
 * - IN_PROGRESS: Actively being resolved
 * - WAITING_CUSTOMER: Waiting for customer response
 * - WAITING_INTERNAL: Waiting for internal team response
 * - RESOLVED: Issue resolved, pending customer confirmation
 * - CLOSED: Ticket completed and closed
 * - REOPENED: Previously closed ticket reopened
 */
enum SupportTicketStatus: string
{
    case NEW = 'new';
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case WAITING_CUSTOMER = 'waiting_customer';
    case WAITING_INTERNAL = 'waiting_internal';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
    case REOPENED = 'reopened';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New',
            self::OPEN => 'Open',
            self::IN_PROGRESS => 'In Progress',
            self::WAITING_CUSTOMER => 'Waiting on Customer',
            self::WAITING_INTERNAL => 'Waiting Internal',
            self::RESOLVED => 'Resolved',
            self::CLOSED => 'Closed',
            self::REOPENED => 'Reopened',
        };
    }

    /**
     * Check if the ticket is in an open state.
     * NEW, OPEN, IN_PROGRESS, and REOPENED are considered open.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::NEW, self::OPEN, self::IN_PROGRESS, self::REOPENED], true);
    }

    /**
     * Check if the ticket is in a pending state.
     * WAITING_CUSTOMER and WAITING_INTERNAL are considered pending.
     */
    public function isPending(): bool
    {
        return in_array($this, [self::WAITING_CUSTOMER, self::WAITING_INTERNAL], true);
    }

    /**
     * Check if the ticket is in a closed state.
     * RESOLVED and CLOSED are considered closed.
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::RESOLVED, self::CLOSED], true);
    }

    /**
     * Check if the status can transition to the target status.
     *
     * Valid transitions:
     * - NEW -> OPEN, IN_PROGRESS, WAITING_CUSTOMER, CLOSED
     * - OPEN -> IN_PROGRESS, WAITING_CUSTOMER, WAITING_INTERNAL, RESOLVED, CLOSED
     * - IN_PROGRESS -> WAITING_CUSTOMER, WAITING_INTERNAL, RESOLVED, CLOSED
     * - WAITING_CUSTOMER -> OPEN, IN_PROGRESS, RESOLVED, CLOSED
     * - WAITING_INTERNAL -> OPEN, IN_PROGRESS, RESOLVED, CLOSED
     * - RESOLVED -> CLOSED, REOPENED
     * - CLOSED -> REOPENED
     * - REOPENED -> OPEN, IN_PROGRESS, WAITING_CUSTOMER, RESOLVED, CLOSED
     */
    public function canTransitionTo(SupportTicketStatus $status): bool
    {
        if ($this === $status) {
            return false;
        }

        return match ($this) {
            self::NEW => in_array($status, [self::OPEN, self::IN_PROGRESS, self::WAITING_CUSTOMER, self::CLOSED], true),
            self::OPEN => in_array($status, [self::IN_PROGRESS, self::WAITING_CUSTOMER, self::WAITING_INTERNAL, self::RESOLVED, self::CLOSED], true),
            self::IN_PROGRESS => in_array($status, [self::WAITING_CUSTOMER, self::WAITING_INTERNAL, self::RESOLVED, self::CLOSED], true),
            self::WAITING_CUSTOMER => in_array($status, [self::OPEN, self::IN_PROGRESS, self::RESOLVED, self::CLOSED], true),
            self::WAITING_INTERNAL => in_array($status, [self::OPEN, self::IN_PROGRESS, self::RESOLVED, self::CLOSED], true),
            self::RESOLVED => in_array($status, [self::CLOSED, self::REOPENED], true),
            self::CLOSED => in_array($status, [self::REOPENED], true),
            self::REOPENED => in_array($status, [self::OPEN, self::IN_PROGRESS, self::WAITING_CUSTOMER, self::RESOLVED, self::CLOSED], true),
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
