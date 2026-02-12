<?php

declare(strict_types=1);

namespace App\Enums\Support;

/**
 * SupportCommentType Enum
 *
 * Defines the type of comments on support tickets.
 *
 * - REPLY: Public reply visible to customer
 * - NOTE: Internal note (only visible to support staff)
 * - STATUS_CHANGE: System-generated status change comment
 * - ASSIGNMENT: System-generated assignment change comment
 * - SYSTEM: Other system-generated comment
 */
enum SupportCommentType: string
{
    case REPLY = 'reply';
    case NOTE = 'note';
    case STATUS_CHANGE = 'status_change';
    case ASSIGNMENT = 'assignment';
    case SYSTEM = 'system';

    /**
     * Get human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::REPLY => 'Reply',
            self::NOTE => 'Internal Note',
            self::STATUS_CHANGE => 'Status Change',
            self::ASSIGNMENT => 'Assignment',
            self::SYSTEM => 'System',
        };
    }

    /**
     * Check if the comment is public (visible to customer).
     * Only REPLY is public.
     */
    public function isPublic(): bool
    {
        return $this === self::REPLY;
    }

    /**
     * Check if the comment is internal (only visible to support staff).
     * NOTE and ASSIGNMENT are internal.
     */
    public function isInternal(): bool
    {
        return in_array($this, [self::NOTE, self::ASSIGNMENT], true);
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
