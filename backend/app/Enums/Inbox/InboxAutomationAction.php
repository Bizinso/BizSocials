<?php

declare(strict_types=1);

namespace App\Enums\Inbox;

/**
 * InboxAutomationAction Enum
 *
 * Defines the action types for inbox automation rules.
 *
 * - ASSIGN: Automatically assign the item to a user
 * - TAG: Automatically apply a tag to the item
 * - AUTO_REPLY: Automatically send a reply
 * - ARCHIVE: Automatically archive the item
 */
enum InboxAutomationAction: string
{
    case ASSIGN = 'assign';
    case TAG = 'tag';
    case AUTO_REPLY = 'auto_reply';
    case ARCHIVE = 'archive';

    /**
     * Get human-readable label for the action type.
     */
    public function label(): string
    {
        return match ($this) {
            self::ASSIGN => 'Assign',
            self::TAG => 'Tag',
            self::AUTO_REPLY => 'Auto Reply',
            self::ARCHIVE => 'Archive',
        };
    }

    /**
     * Get all action types as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
