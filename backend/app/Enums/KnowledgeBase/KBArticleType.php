<?php

declare(strict_types=1);

namespace App\Enums\KnowledgeBase;

/**
 * KBArticleType Enum
 *
 * Defines the type of knowledge base article.
 *
 * - GETTING_STARTED: Introductory articles for new users
 * - HOW_TO: Step-by-step guides for specific tasks
 * - TUTORIAL: In-depth learning content
 * - REFERENCE: Technical reference documentation
 * - TROUBLESHOOTING: Problem-solving guides
 * - FAQ: Frequently asked questions
 * - BEST_PRACTICE: Recommended approaches
 * - RELEASE_NOTE: Version update information
 * - API_DOCUMENTATION: API reference documentation
 */
enum KBArticleType: string
{
    case GETTING_STARTED = 'getting_started';
    case HOW_TO = 'how_to';
    case TUTORIAL = 'tutorial';
    case REFERENCE = 'reference';
    case TROUBLESHOOTING = 'troubleshooting';
    case FAQ = 'faq';
    case BEST_PRACTICE = 'best_practice';
    case RELEASE_NOTE = 'release_note';
    case API_DOCUMENTATION = 'api_documentation';

    /**
     * Get human-readable label for the article type.
     */
    public function label(): string
    {
        return match ($this) {
            self::GETTING_STARTED => 'Getting Started',
            self::HOW_TO => 'How-To Guide',
            self::TUTORIAL => 'Tutorial',
            self::REFERENCE => 'Reference',
            self::TROUBLESHOOTING => 'Troubleshooting',
            self::FAQ => 'FAQ',
            self::BEST_PRACTICE => 'Best Practice',
            self::RELEASE_NOTE => 'Release Note',
            self::API_DOCUMENTATION => 'API Documentation',
        };
    }

    /**
     * Get icon name for the article type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::GETTING_STARTED => 'rocket',
            self::HOW_TO => 'clipboard-list',
            self::TUTORIAL => 'academic-cap',
            self::REFERENCE => 'book-open',
            self::TROUBLESHOOTING => 'wrench-screwdriver',
            self::FAQ => 'question-mark-circle',
            self::BEST_PRACTICE => 'star',
            self::RELEASE_NOTE => 'document-text',
            self::API_DOCUMENTATION => 'code-bracket',
        };
    }

    /**
     * Get description for the article type.
     */
    public function description(): string
    {
        return match ($this) {
            self::GETTING_STARTED => 'Introductory articles to help new users get up and running quickly.',
            self::HOW_TO => 'Step-by-step guides for completing specific tasks.',
            self::TUTORIAL => 'In-depth learning content for mastering features and workflows.',
            self::REFERENCE => 'Technical reference documentation for detailed specifications.',
            self::TROUBLESHOOTING => 'Problem-solving guides for common issues and errors.',
            self::FAQ => 'Answers to frequently asked questions.',
            self::BEST_PRACTICE => 'Recommended approaches and guidelines for optimal results.',
            self::RELEASE_NOTE => 'Information about new features, improvements, and fixes.',
            self::API_DOCUMENTATION => 'Technical documentation for API endpoints and integrations.',
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
