<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

/**
 * ChangeType Enum
 *
 * Defines the type of change in a release note item.
 *
 * - NEW_FEATURE: New feature added
 * - IMPROVEMENT: Improvement to existing feature
 * - BUG_FIX: Bug fix
 * - SECURITY: Security fix or enhancement
 * - PERFORMANCE: Performance improvement
 * - DEPRECATION: Feature deprecation notice
 * - BREAKING_CHANGE: Breaking change notice
 */
enum ChangeType: string
{
    case NEW_FEATURE = 'new_feature';
    case IMPROVEMENT = 'improvement';
    case BUG_FIX = 'bug_fix';
    case SECURITY = 'security';
    case PERFORMANCE = 'performance';
    case DEPRECATION = 'deprecation';
    case BREAKING_CHANGE = 'breaking_change';

    /**
     * Get human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::NEW_FEATURE => 'New Feature',
            self::IMPROVEMENT => 'Improvement',
            self::BUG_FIX => 'Bug Fix',
            self::SECURITY => 'Security',
            self::PERFORMANCE => 'Performance',
            self::DEPRECATION => 'Deprecation',
            self::BREAKING_CHANGE => 'Breaking Change',
        };
    }

    /**
     * Get icon for the type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::NEW_FEATURE => 'sparkles',
            self::IMPROVEMENT => 'trending-up',
            self::BUG_FIX => 'bug',
            self::SECURITY => 'shield',
            self::PERFORMANCE => 'zap',
            self::DEPRECATION => 'alert-triangle',
            self::BREAKING_CHANGE => 'alert-octagon',
        };
    }

    /**
     * Get color for the type.
     */
    public function color(): string
    {
        return match ($this) {
            self::NEW_FEATURE => '#8B5CF6',
            self::IMPROVEMENT => '#3B82F6',
            self::BUG_FIX => '#10B981',
            self::SECURITY => '#EF4444',
            self::PERFORMANCE => '#F59E0B',
            self::DEPRECATION => '#F97316',
            self::BREAKING_CHANGE => '#DC2626',
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
