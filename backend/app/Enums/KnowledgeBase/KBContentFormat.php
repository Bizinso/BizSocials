<?php

declare(strict_types=1);

namespace App\Enums\KnowledgeBase;

/**
 * KBContentFormat Enum
 *
 * Defines the content format of knowledge base articles.
 *
 * - MARKDOWN: Content written in Markdown syntax
 * - HTML: Raw HTML content
 * - RICH_TEXT: Rich text editor format (JSON-based)
 */
enum KBContentFormat: string
{
    case MARKDOWN = 'markdown';
    case HTML = 'html';
    case RICH_TEXT = 'rich_text';

    /**
     * Get human-readable label for the content format.
     */
    public function label(): string
    {
        return match ($this) {
            self::MARKDOWN => 'Markdown',
            self::HTML => 'HTML',
            self::RICH_TEXT => 'Rich Text',
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
