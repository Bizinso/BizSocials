<?php

declare(strict_types=1);

namespace App\Enums\Content;

/**
 * MediaType Enum
 *
 * Defines the type of media attachment for a post.
 *
 * - IMAGE: Static image files (JPEG, PNG, WebP)
 * - VIDEO: Video files (MP4, MOV)
 * - GIF: Animated GIF files
 * - DOCUMENT: Document files (PDF, for LinkedIn)
 */
enum MediaType: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';
    case GIF = 'gif';
    case DOCUMENT = 'document';

    /**
     * Get human-readable label for the media type.
     */
    public function label(): string
    {
        return match ($this) {
            self::IMAGE => 'Image',
            self::VIDEO => 'Video',
            self::GIF => 'GIF',
            self::DOCUMENT => 'Document',
        };
    }

    /**
     * Get the maximum file size in bytes for this media type.
     */
    public function maxFileSize(): int
    {
        return match ($this) {
            self::IMAGE => 10 * 1024 * 1024,      // 10 MB
            self::VIDEO => 500 * 1024 * 1024,     // 500 MB
            self::GIF => 15 * 1024 * 1024,        // 15 MB
            self::DOCUMENT => 100 * 1024 * 1024,  // 100 MB
        };
    }

    /**
     * Get the allowed MIME types for this media type.
     *
     * @return array<string>
     */
    public function allowedMimeTypes(): array
    {
        return match ($this) {
            self::IMAGE => [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/heic',
                'image/heif',
            ],
            self::VIDEO => [
                'video/mp4',
                'video/quicktime',
                'video/x-msvideo',
                'video/webm',
            ],
            self::GIF => [
                'image/gif',
            ],
            self::DOCUMENT => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ],
        };
    }

    /**
     * Get all media types as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
