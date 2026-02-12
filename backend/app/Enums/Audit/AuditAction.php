<?php

declare(strict_types=1);

namespace App\Enums\Audit;

/**
 * AuditAction Enum
 *
 * Defines the type of action performed in an audit log entry.
 *
 * - CREATE: Resource was created
 * - UPDATE: Resource was updated
 * - DELETE: Resource was deleted
 * - RESTORE: Resource was restored from soft delete
 * - VIEW: Resource was viewed
 * - EXPORT: Data was exported
 * - IMPORT: Data was imported
 * - LOGIN: User logged in
 * - LOGOUT: User logged out
 * - PERMISSION_CHANGE: Permissions were modified
 * - SETTINGS_CHANGE: Settings were modified
 * - SUBSCRIPTION_CHANGE: Subscription was modified
 */
enum AuditAction: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case RESTORE = 'restore';
    case VIEW = 'view';
    case EXPORT = 'export';
    case IMPORT = 'import';
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case PERMISSION_CHANGE = 'permission_change';
    case SETTINGS_CHANGE = 'settings_change';
    case SUBSCRIPTION_CHANGE = 'subscription_change';

    /**
     * Get human-readable label for the action.
     */
    public function label(): string
    {
        return match ($this) {
            self::CREATE => 'Create',
            self::UPDATE => 'Update',
            self::DELETE => 'Delete',
            self::RESTORE => 'Restore',
            self::VIEW => 'View',
            self::EXPORT => 'Export',
            self::IMPORT => 'Import',
            self::LOGIN => 'Login',
            self::LOGOUT => 'Logout',
            self::PERMISSION_CHANGE => 'Permission Change',
            self::SETTINGS_CHANGE => 'Settings Change',
            self::SUBSCRIPTION_CHANGE => 'Subscription Change',
        };
    }

    /**
     * Check if the action is a write operation.
     * CREATE, UPDATE, DELETE are considered write operations.
     */
    public function isWrite(): bool
    {
        return in_array($this, [self::CREATE, self::UPDATE, self::DELETE], true);
    }

    /**
     * Check if the action is a read operation.
     * VIEW, EXPORT are considered read operations.
     */
    public function isRead(): bool
    {
        return in_array($this, [self::VIEW, self::EXPORT], true);
    }

    /**
     * Check if the action is an authentication operation.
     * LOGIN, LOGOUT are considered auth operations.
     */
    public function isAuth(): bool
    {
        return in_array($this, [self::LOGIN, self::LOGOUT], true);
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
