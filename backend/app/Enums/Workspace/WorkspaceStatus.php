<?php

declare(strict_types=1);

namespace App\Enums\Workspace;

/**
 * WorkspaceStatus Enum
 *
 * Defines the lifecycle status of a workspace.
 *
 * - ACTIVE: Normal operation, full access
 * - SUSPENDED: Payment failed or admin action, limited access
 * - DELETED: Soft-deleted, 30-day retention period
 */
enum WorkspaceStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case DELETED = 'deleted';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::DELETED => 'Deleted',
        };
    }

    /**
     * Check if the workspace has access (can be used normally).
     * Only ACTIVE workspaces have access.
     */
    public function hasAccess(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if the workspace can transition to a given status.
     *
     * Valid transitions:
     * - ACTIVE -> SUSPENDED
     * - ACTIVE -> DELETED
     * - SUSPENDED -> ACTIVE
     * - SUSPENDED -> DELETED
     */
    public function canTransitionTo(WorkspaceStatus $status): bool
    {
        return match ($this) {
            self::ACTIVE => in_array($status, [self::SUSPENDED, self::DELETED], true),
            self::SUSPENDED => in_array($status, [self::ACTIVE, self::DELETED], true),
            self::DELETED => false,
        };
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
