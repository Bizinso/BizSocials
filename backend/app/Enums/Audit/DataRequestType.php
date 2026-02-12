<?php

declare(strict_types=1);

namespace App\Enums\Audit;

/**
 * DataRequestType Enum
 *
 * Defines the type of GDPR data request.
 *
 * - EXPORT: Request to export personal data (Article 20)
 * - DELETION: Request to delete personal data (Article 17)
 * - RECTIFICATION: Request to correct personal data (Article 16)
 * - ACCESS: Request to access personal data (Article 15)
 */
enum DataRequestType: string
{
    case EXPORT = 'export';
    case DELETION = 'deletion';
    case RECTIFICATION = 'rectification';
    case ACCESS = 'access';

    /**
     * Get human-readable label for the request type.
     */
    public function label(): string
    {
        return match ($this) {
            self::EXPORT => 'Data Export',
            self::DELETION => 'Data Deletion',
            self::RECTIFICATION => 'Data Rectification',
            self::ACCESS => 'Data Access',
        };
    }

    /**
     * Get the GDPR article reference for this request type.
     */
    public function gdprArticle(): string
    {
        return match ($this) {
            self::EXPORT => 'Article 20',
            self::DELETION => 'Article 17',
            self::RECTIFICATION => 'Article 16',
            self::ACCESS => 'Article 15',
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
