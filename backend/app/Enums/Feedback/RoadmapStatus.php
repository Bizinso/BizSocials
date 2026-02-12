<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

/**
 * RoadmapStatus Enum
 *
 * Defines the status of a roadmap item.
 *
 * - CONSIDERING: Under consideration
 * - PLANNED: Planned for implementation
 * - IN_PROGRESS: Currently being developed
 * - BETA: In beta testing
 * - SHIPPED: Deployed to production
 * - CANCELLED: Will not be implemented
 */
enum RoadmapStatus: string
{
    case CONSIDERING = 'considering';
    case PLANNED = 'planned';
    case IN_PROGRESS = 'in_progress';
    case BETA = 'beta';
    case SHIPPED = 'shipped';
    case CANCELLED = 'cancelled';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::CONSIDERING => 'Considering',
            self::PLANNED => 'Planned',
            self::IN_PROGRESS => 'In Progress',
            self::BETA => 'Beta',
            self::SHIPPED => 'Shipped',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Check if the roadmap item is in an active state.
     * PLANNED, IN_PROGRESS, and BETA are considered active.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::PLANNED, self::IN_PROGRESS, self::BETA], true);
    }

    /**
     * Check if the roadmap item should be public.
     * All except CANCELLED are public.
     */
    public function isPublic(): bool
    {
        return $this !== self::CANCELLED;
    }

    /**
     * Check if the status can transition to the target status.
     *
     * Valid transitions:
     * - CONSIDERING -> PLANNED, CANCELLED
     * - PLANNED -> IN_PROGRESS, CONSIDERING, CANCELLED
     * - IN_PROGRESS -> BETA, SHIPPED, PLANNED, CANCELLED
     * - BETA -> SHIPPED, IN_PROGRESS, CANCELLED
     * - SHIPPED -> (terminal state)
     * - CANCELLED -> CONSIDERING (can reopen)
     */
    public function canTransitionTo(RoadmapStatus $status): bool
    {
        if ($this === $status) {
            return false;
        }

        return match ($this) {
            self::CONSIDERING => in_array($status, [self::PLANNED, self::CANCELLED], true),
            self::PLANNED => in_array($status, [self::IN_PROGRESS, self::CONSIDERING, self::CANCELLED], true),
            self::IN_PROGRESS => in_array($status, [self::BETA, self::SHIPPED, self::PLANNED, self::CANCELLED], true),
            self::BETA => in_array($status, [self::SHIPPED, self::IN_PROGRESS, self::CANCELLED], true),
            self::SHIPPED => false,
            self::CANCELLED => in_array($status, [self::CONSIDERING], true),
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
