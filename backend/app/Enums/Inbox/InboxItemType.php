<?php

declare(strict_types=1);

namespace App\Enums\Inbox;

/**
 * InboxItemType Enum
 *
 * Defines the type of inbox item from social platforms.
 *
 * - COMMENT: A comment on one of our posts
 * - MENTION: A mention of our account in another user's post
 * - DM: A direct message from another user
 * - WHATSAPP_MESSAGE: A WhatsApp message from a customer
 * - REVIEW: A review on a business listing
 */
enum InboxItemType: string
{
    case COMMENT = 'comment';
    case MENTION = 'mention';
    case DM = 'dm';
    case WHATSAPP_MESSAGE = 'whatsapp_message';
    case REVIEW = 'review';

    /**
     * Get human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::COMMENT => 'Comment',
            self::MENTION => 'Mention',
            self::DM => 'Direct Message',
            self::WHATSAPP_MESSAGE => 'WhatsApp Message',
            self::REVIEW => 'Review',
        };
    }

    /**
     * Check if this type of inbox item can be replied to.
     */
    public function canReply(): bool
    {
        return match ($this) {
            self::COMMENT, self::DM, self::WHATSAPP_MESSAGE => true,
            self::MENTION, self::REVIEW => false,
        };
    }

    /**
     * Get all types as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
