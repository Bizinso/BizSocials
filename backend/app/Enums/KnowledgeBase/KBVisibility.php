<?php

declare(strict_types=1);

namespace App\Enums\KnowledgeBase;

/**
 * KBVisibility Enum
 *
 * Defines the visibility level of knowledge base articles and categories.
 *
 * - ALL: Public, visible to everyone including anonymous users
 * - AUTHENTICATED: Only visible to logged-in users
 * - SPECIFIC_PLANS: Only visible to users on specific subscription plans
 */
enum KBVisibility: string
{
    case ALL = 'all';
    case AUTHENTICATED = 'authenticated';
    case SPECIFIC_PLANS = 'specific_plans';

    /**
     * Get human-readable label for the visibility.
     */
    public function label(): string
    {
        return match ($this) {
            self::ALL => 'Public',
            self::AUTHENTICATED => 'Authenticated Users Only',
            self::SPECIFIC_PLANS => 'Specific Plans Only',
        };
    }

    /**
     * Check if the visibility is public (available to everyone).
     * Only ALL visibility is considered public.
     */
    public function isPublic(): bool
    {
        return $this === self::ALL;
    }

    /**
     * Check if authentication is required to view.
     * Both AUTHENTICATED and SPECIFIC_PLANS require authentication.
     */
    public function requiresAuth(): bool
    {
        return $this !== self::ALL;
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
