<?php

declare(strict_types=1);

namespace App\Enums\Support;

/**
 * CannedResponseCategory Enum
 *
 * Defines categories for canned (pre-defined) support responses.
 *
 * - GREETING: Initial greeting messages
 * - BILLING: Billing-related responses
 * - TECHNICAL: Technical support responses
 * - ACCOUNT: Account management responses
 * - FEATURE_REQUEST: Feature request handling responses
 * - BUG_REPORT: Bug report handling responses
 * - CLOSING: Ticket closing messages
 * - GENERAL: General purpose responses
 */
enum CannedResponseCategory: string
{
    case GREETING = 'greeting';
    case BILLING = 'billing';
    case TECHNICAL = 'technical';
    case ACCOUNT = 'account';
    case FEATURE_REQUEST = 'feature_request';
    case BUG_REPORT = 'bug_report';
    case CLOSING = 'closing';
    case GENERAL = 'general';

    /**
     * Get human-readable label for the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::GREETING => 'Greeting',
            self::BILLING => 'Billing',
            self::TECHNICAL => 'Technical',
            self::ACCOUNT => 'Account',
            self::FEATURE_REQUEST => 'Feature Request',
            self::BUG_REPORT => 'Bug Report',
            self::CLOSING => 'Closing',
            self::GENERAL => 'General',
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
