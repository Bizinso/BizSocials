<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

/**
 * CompanySize Enum
 *
 * Defines company size categories for tenant profiles.
 *
 * - SOLO: 1 person (freelancer/solopreneur)
 * - SMALL: 2-10 employees
 * - MEDIUM: 11-50 employees
 * - LARGE: 51-200 employees
 * - ENTERPRISE: 200+ employees
 */
enum CompanySize: string
{
    case SOLO = 'solo';
    case SMALL = 'small';
    case MEDIUM = 'medium';
    case LARGE = 'large';
    case ENTERPRISE = 'enterprise';

    /**
     * Get human-readable label for the company size.
     */
    public function label(): string
    {
        return match ($this) {
            self::SOLO => 'Solo',
            self::SMALL => 'Small',
            self::MEDIUM => 'Medium',
            self::LARGE => 'Large',
            self::ENTERPRISE => 'Enterprise',
        };
    }

    /**
     * Get the employee range as a string.
     */
    public function range(): string
    {
        return match ($this) {
            self::SOLO => '1 person',
            self::SMALL => '2-10 employees',
            self::MEDIUM => '11-50 employees',
            self::LARGE => '51-200 employees',
            self::ENTERPRISE => '200+ employees',
        };
    }

    /**
     * Get the minimum number of employees for this size.
     */
    public function minEmployees(): int
    {
        return match ($this) {
            self::SOLO => 1,
            self::SMALL => 2,
            self::MEDIUM => 11,
            self::LARGE => 51,
            self::ENTERPRISE => 201,
        };
    }

    /**
     * Get the maximum number of employees for this size.
     * Returns null for ENTERPRISE (no upper limit).
     */
    public function maxEmployees(): ?int
    {
        return match ($this) {
            self::SOLO => 1,
            self::SMALL => 10,
            self::MEDIUM => 50,
            self::LARGE => 200,
            self::ENTERPRISE => null,
        };
    }

    /**
     * Get all sizes as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
