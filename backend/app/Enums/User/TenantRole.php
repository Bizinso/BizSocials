<?php

declare(strict_types=1);

namespace App\Enums\User;

/**
 * TenantRole Enum
 *
 * Defines the role of a user within a tenant organization.
 *
 * - OWNER: Full control including billing and tenant deletion
 * - ADMIN: Full admin access but cannot delete tenant or manage billing
 * - MEMBER: Regular team member with limited access
 */
enum TenantRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';

    /**
     * Get human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Owner',
            self::ADMIN => 'Admin',
            self::MEMBER => 'Member',
        };
    }

    /**
     * Check if the role can manage billing.
     * Only OWNER can manage billing.
     */
    public function canManageBilling(): bool
    {
        return $this === self::OWNER;
    }

    /**
     * Check if the role can manage users.
     * OWNER and ADMIN can manage users.
     */
    public function canManageUsers(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN], true);
    }

    /**
     * Check if the role can delete the tenant.
     * Only OWNER can delete tenant.
     */
    public function canDeleteTenant(): bool
    {
        return $this === self::OWNER;
    }

    /**
     * Check if this role is at least as powerful as the given role.
     *
     * Hierarchy: OWNER > ADMIN > MEMBER
     */
    public function isAtLeast(TenantRole $role): bool
    {
        $hierarchy = [
            self::MEMBER->value => 1,
            self::ADMIN->value => 2,
            self::OWNER->value => 3,
        ];

        return $hierarchy[$this->value] >= $hierarchy[$role->value];
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
