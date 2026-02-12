<?php

declare(strict_types=1);

namespace App\Enums\Content;

/**
 * MediaProcessingStatus Enum
 *
 * Defines the processing status of uploaded media.
 *
 * - PENDING: Media uploaded, awaiting processing
 * - PROCESSING: Media is being processed (thumbnails, optimization)
 * - COMPLETED: Media processing completed successfully
 * - FAILED: Media processing failed
 */
enum MediaProcessingStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
        };
    }

    /**
     * Check if the media is ready for use.
     * Only COMPLETED status means the media is ready.
     */
    public function isReady(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Get all statuses as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
