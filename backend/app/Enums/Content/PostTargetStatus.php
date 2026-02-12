<?php

declare(strict_types=1);

namespace App\Enums\Content;

/**
 * PostTargetStatus Enum
 *
 * Defines the publishing status of a post to a specific social account.
 *
 * - PENDING: Awaiting publishing
 * - PUBLISHING: Currently being published
 * - PUBLISHED: Successfully published
 * - FAILED: Publishing failed
 */
enum PostTargetStatus: string
{
    case PENDING = 'pending';
    case PUBLISHING = 'publishing';
    case PUBLISHED = 'published';
    case FAILED = 'failed';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PUBLISHING => 'Publishing',
            self::PUBLISHED => 'Published',
            self::FAILED => 'Failed',
        };
    }

    /**
     * Check if the post has been successfully published.
     */
    public function isPublished(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * Check if the publishing has failed.
     */
    public function hasFailed(): bool
    {
        return $this === self::FAILED;
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
