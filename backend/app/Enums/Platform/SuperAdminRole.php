<?php

declare(strict_types=1);

namespace App\Enums\Platform;

/**
 * SuperAdminRole Enum
 *
 * Defines the role levels for platform administrators (Bizinso team members).
 * Each role has different permission levels within the super admin panel.
 *
 * - SUPER_ADMIN: Full access to all platform features and settings
 * - ADMIN: Can manage tenants, configs, but cannot manage other admins
 * - SUPPORT: Can view and assist tenants, limited write access
 * - VIEWER: Read-only access for monitoring and reporting
 */
enum SuperAdminRole: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case ADMIN = 'ADMIN';
    case SUPPORT = 'SUPPORT';
    case VIEWER = 'VIEWER';

    /**
     * Get human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::SUPPORT => 'Support',
            self::VIEWER => 'Viewer',
        };
    }

    /**
     * Get description for the role.
     */
    public function description(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Full access to all platform features and settings',
            self::ADMIN => 'Can manage tenants and configs, but cannot manage other admins',
            self::SUPPORT => 'Can view and assist tenants with limited write access',
            self::VIEWER => 'Read-only access for monitoring and reporting',
        };
    }

    /**
     * Check if the role can manage other admins.
     */
    public function canManageAdmins(): bool
    {
        return $this === self::SUPER_ADMIN;
    }

    /**
     * Check if the role has write access.
     */
    public function hasWriteAccess(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::ADMIN, self::SUPPORT], true);
    }

    /**
     * Get all roles as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
