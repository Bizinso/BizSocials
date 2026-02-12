<?php

declare(strict_types=1);

namespace App\Enums\Platform;

/**
 * PlanCode Enum
 *
 * Defines the subscription plan codes available on the platform.
 *
 * - FREE: Basic free tier with limited features
 * - STARTER: Entry-level paid plan for small businesses
 * - PROFESSIONAL: Mid-tier plan with more features
 * - BUSINESS: Advanced plan for growing businesses
 * - ENTERPRISE: Full-featured plan for large organizations
 */
enum PlanCode: string
{
    case FREE = 'FREE';
    case STARTER = 'STARTER';
    case PROFESSIONAL = 'PROFESSIONAL';
    case BUSINESS = 'BUSINESS';
    case ENTERPRISE = 'ENTERPRISE';

    /**
     * Get human-readable label for the plan.
     */
    public function label(): string
    {
        return match ($this) {
            self::FREE => 'Free',
            self::STARTER => 'Starter',
            self::PROFESSIONAL => 'Professional',
            self::BUSINESS => 'Business',
            self::ENTERPRISE => 'Enterprise',
        };
    }

    /**
     * Get description for the plan.
     */
    public function description(): string
    {
        return match ($this) {
            self::FREE => 'Basic free tier with limited features',
            self::STARTER => 'Entry-level paid plan for small businesses',
            self::PROFESSIONAL => 'Mid-tier plan with more features',
            self::BUSINESS => 'Advanced plan for growing businesses',
            self::ENTERPRISE => 'Full-featured plan for large organizations',
        };
    }

    /**
     * Check if the plan is a paid plan.
     */
    public function isPaid(): bool
    {
        return $this !== self::FREE;
    }

    /**
     * Get the tier level (for comparison purposes).
     */
    public function tierLevel(): int
    {
        return match ($this) {
            self::FREE => 0,
            self::STARTER => 1,
            self::PROFESSIONAL => 2,
            self::BUSINESS => 3,
            self::ENTERPRISE => 4,
        };
    }

    /**
     * Check if this plan is higher than another plan.
     */
    public function isHigherThan(self $other): bool
    {
        return $this->tierLevel() > $other->tierLevel();
    }

    /**
     * Check if this plan is at least the level of another plan.
     */
    public function isAtLeast(self $other): bool
    {
        return $this->tierLevel() >= $other->tierLevel();
    }

    /**
     * Get all plan codes as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
