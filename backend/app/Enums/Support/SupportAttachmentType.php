<?php

declare(strict_types=1);

namespace App\Enums\Support;

/**
 * SupportAttachmentType Enum
 *
 * Defines the types of file attachments on support tickets.
 *
 * - IMAGE: Image files (png, jpg, gif, etc.)
 * - DOCUMENT: Document files (pdf, doc, docx, etc.)
 * - VIDEO: Video files (mp4, mov, etc.)
 * - ARCHIVE: Archive files (zip, rar, etc.)
 * - OTHER: Other file types
 */
enum SupportAttachmentType: string
{
    case IMAGE = 'image';
    case DOCUMENT = 'document';
    case VIDEO = 'video';
    case ARCHIVE = 'archive';
    case OTHER = 'other';

    /**
     * Get human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::IMAGE => 'Image',
            self::DOCUMENT => 'Document',
            self::VIDEO => 'Video',
            self::ARCHIVE => 'Archive',
            self::OTHER => 'Other',
        };
    }

    /**
     * Get allowed file extensions for this type.
     *
     * @return array<string>
     */
    public function allowedExtensions(): array
    {
        return match ($this) {
            self::IMAGE => ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp'],
            self::DOCUMENT => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'],
            self::VIDEO => ['mp4', 'mov', 'avi', 'wmv', 'webm'],
            self::ARCHIVE => ['zip', 'rar', '7z', 'tar', 'gz'],
            self::OTHER => [],
        };
    }

    /**
     * Get maximum file size in bytes for this type.
     */
    public function maxSizeBytes(): int
    {
        return match ($this) {
            self::IMAGE => 10 * 1024 * 1024,      // 10 MB
            self::DOCUMENT => 25 * 1024 * 1024,   // 25 MB
            self::VIDEO => 100 * 1024 * 1024,     // 100 MB
            self::ARCHIVE => 50 * 1024 * 1024,    // 50 MB
            self::OTHER => 10 * 1024 * 1024,      // 10 MB
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
