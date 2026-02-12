<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

/**
 * FeedbackSource Enum
 *
 * Defines where the feedback was submitted from.
 *
 * - PORTAL: Feedback portal on website
 * - WIDGET: In-app feedback widget
 * - EMAIL: Email submission
 * - SUPPORT_TICKET: Converted from support ticket
 * - INTERNAL: Internal team submission
 */
enum FeedbackSource: string
{
    case PORTAL = 'portal';
    case WIDGET = 'widget';
    case EMAIL = 'email';
    case SUPPORT_TICKET = 'support_ticket';
    case INTERNAL = 'internal';

    /**
     * Get human-readable label for the source.
     */
    public function label(): string
    {
        return match ($this) {
            self::PORTAL => 'Feedback Portal',
            self::WIDGET => 'In-App Widget',
            self::EMAIL => 'Email',
            self::SUPPORT_TICKET => 'Support Ticket',
            self::INTERNAL => 'Internal',
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
