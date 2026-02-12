<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

/**
 * FeedbackType Enum
 *
 * Defines the type of feedback submitted by users.
 *
 * - FEATURE_REQUEST: Request for a new feature
 * - IMPROVEMENT: Suggestion to improve existing functionality
 * - BUG_REPORT: Report of a bug or issue
 * - INTEGRATION_REQUEST: Request for new integration
 * - UX_FEEDBACK: Feedback about user experience
 * - DOCUMENTATION: Feedback about documentation
 * - PRICING_FEEDBACK: Feedback about pricing
 * - OTHER: Other feedback that doesn't fit categories
 */
enum FeedbackType: string
{
    case FEATURE_REQUEST = 'feature_request';
    case IMPROVEMENT = 'improvement';
    case BUG_REPORT = 'bug_report';
    case INTEGRATION_REQUEST = 'integration_request';
    case UX_FEEDBACK = 'ux_feedback';
    case DOCUMENTATION = 'documentation';
    case PRICING_FEEDBACK = 'pricing_feedback';
    case OTHER = 'other';

    /**
     * Get human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::FEATURE_REQUEST => 'Feature Request',
            self::IMPROVEMENT => 'Improvement',
            self::BUG_REPORT => 'Bug Report',
            self::INTEGRATION_REQUEST => 'Integration Request',
            self::UX_FEEDBACK => 'UX Feedback',
            self::DOCUMENTATION => 'Documentation',
            self::PRICING_FEEDBACK => 'Pricing Feedback',
            self::OTHER => 'Other',
        };
    }

    /**
     * Get icon for the type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::FEATURE_REQUEST => 'lightbulb',
            self::IMPROVEMENT => 'trending-up',
            self::BUG_REPORT => 'bug',
            self::INTEGRATION_REQUEST => 'plug',
            self::UX_FEEDBACK => 'layout',
            self::DOCUMENTATION => 'book-open',
            self::PRICING_FEEDBACK => 'dollar-sign',
            self::OTHER => 'message-circle',
        };
    }

    /**
     * Get color for the type.
     */
    public function color(): string
    {
        return match ($this) {
            self::FEATURE_REQUEST => '#8B5CF6',
            self::IMPROVEMENT => '#3B82F6',
            self::BUG_REPORT => '#EF4444',
            self::INTEGRATION_REQUEST => '#10B981',
            self::UX_FEEDBACK => '#F59E0B',
            self::DOCUMENTATION => '#6366F1',
            self::PRICING_FEEDBACK => '#EC4899',
            self::OTHER => '#6B7280',
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
