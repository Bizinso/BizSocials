<?php

declare(strict_types=1);

namespace App\Enums\Inbox;

/**
 * InboxAutomationTrigger Enum
 *
 * Defines the trigger types for inbox automation rules.
 *
 * - NEW_ITEM: Triggered when a new inbox item arrives
 * - KEYWORD_MATCH: Triggered when content matches specific keywords
 * - PLATFORM_MATCH: Triggered when the item is from a specific platform
 */
enum InboxAutomationTrigger: string
{
    case NEW_ITEM = 'new_item';
    case KEYWORD_MATCH = 'keyword_match';
    case PLATFORM_MATCH = 'platform_match';

    /**
     * Get human-readable label for the trigger type.
     */
    public function label(): string
    {
        return match ($this) {
            self::NEW_ITEM => 'New Item',
            self::KEYWORD_MATCH => 'Keyword Match',
            self::PLATFORM_MATCH => 'Platform Match',
        };
    }

    /**
     * Get all trigger types as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
