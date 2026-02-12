<?php

declare(strict_types=1);

namespace App\Enums\Content;

/**
 * ApprovalDecisionType Enum
 *
 * Defines the type of approval decision made on a post.
 *
 * - APPROVED: Post was approved for publishing
 * - REJECTED: Post was rejected and needs revision
 */
enum ApprovalDecisionType: string
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    /**
     * Get human-readable label for the decision type.
     */
    public function label(): string
    {
        return match ($this) {
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    /**
     * Get all decision types as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
