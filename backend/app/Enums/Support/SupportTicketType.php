<?php

declare(strict_types=1);

namespace App\Enums\Support;

/**
 * SupportTicketType Enum
 *
 * Defines the type/category of support tickets.
 *
 * - QUESTION: General question about the product
 * - PROBLEM: Something isn't working as expected
 * - FEATURE_REQUEST: Request for new functionality
 * - BUG_REPORT: Bug report
 * - BILLING: Billing related inquiry
 * - ACCOUNT: Account management issue
 * - OTHER: Other type of request
 */
enum SupportTicketType: string
{
    case QUESTION = 'question';
    case PROBLEM = 'problem';
    case FEATURE_REQUEST = 'feature_request';
    case BUG_REPORT = 'bug_report';
    case BILLING = 'billing';
    case ACCOUNT = 'account';
    case OTHER = 'other';

    /**
     * Get human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::QUESTION => 'Question',
            self::PROBLEM => 'Problem',
            self::FEATURE_REQUEST => 'Feature Request',
            self::BUG_REPORT => 'Bug Report',
            self::BILLING => 'Billing',
            self::ACCOUNT => 'Account',
            self::OTHER => 'Other',
        };
    }

    /**
     * Get the icon name for the type (for UI display).
     */
    public function icon(): string
    {
        return match ($this) {
            self::QUESTION => 'question-mark-circle',
            self::PROBLEM => 'exclamation-circle',
            self::FEATURE_REQUEST => 'light-bulb',
            self::BUG_REPORT => 'bug-ant',
            self::BILLING => 'credit-card',
            self::ACCOUNT => 'user-circle',
            self::OTHER => 'document-text',
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
