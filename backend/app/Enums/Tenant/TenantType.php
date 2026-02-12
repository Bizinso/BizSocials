<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

/**
 * TenantType Enum
 *
 * Defines the type of tenant/customer for the multi-tenant platform.
 * Each type has different requirements and capabilities.
 *
 * - B2B_ENTERPRISE: Large enterprise customers with complex needs
 * - B2B_SMB: Small and medium business customers
 * - B2C_BRAND: Consumer brands focusing on B2C marketing
 * - INDIVIDUAL: Individual users/freelancers
 * - INFLUENCER: Social media influencers
 * - NON_PROFIT: Non-profit organizations with special pricing
 */
enum TenantType: string
{
    case B2B_ENTERPRISE = 'b2b_enterprise';
    case B2B_SMB = 'b2b_smb';
    case B2C_BRAND = 'b2c_brand';
    case INDIVIDUAL = 'individual';
    case INFLUENCER = 'influencer';
    case NON_PROFIT = 'non_profit';

    /**
     * Get human-readable label for the tenant type.
     */
    public function label(): string
    {
        return match ($this) {
            self::B2B_ENTERPRISE => 'B2B Enterprise',
            self::B2B_SMB => 'B2B SMB',
            self::B2C_BRAND => 'B2C Brand',
            self::INDIVIDUAL => 'Individual',
            self::INFLUENCER => 'Influencer',
            self::NON_PROFIT => 'Non-Profit',
        };
    }

    /**
     * Get description for the tenant type.
     */
    public function description(): string
    {
        return match ($this) {
            self::B2B_ENTERPRISE => 'Large enterprise customers with complex needs',
            self::B2B_SMB => 'Small and medium business customers',
            self::B2C_BRAND => 'Consumer brands focusing on B2C marketing',
            self::INDIVIDUAL => 'Individual users and freelancers',
            self::INFLUENCER => 'Social media influencers and content creators',
            self::NON_PROFIT => 'Non-profit organizations with special pricing',
        };
    }

    /**
     * Check if the tenant type requires a business profile.
     */
    public function requiresBusinessProfile(): bool
    {
        return in_array($this, [
            self::B2B_ENTERPRISE,
            self::B2B_SMB,
            self::B2C_BRAND,
            self::NON_PROFIT,
        ], true);
    }

    /**
     * Check if the tenant type is a B2B type.
     */
    public function isB2B(): bool
    {
        return in_array($this, [
            self::B2B_ENTERPRISE,
            self::B2B_SMB,
        ], true);
    }

    /**
     * Get all types as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
