<?php

declare(strict_types=1);

namespace App\Enums\Feedback;

/**
 * ReleaseType Enum
 *
 * Defines the type of release for release notes.
 *
 * - MAJOR: Major version release (breaking changes, new features)
 * - MINOR: Minor version release (new features, no breaking changes)
 * - PATCH: Patch release (bug fixes)
 * - HOTFIX: Emergency hotfix
 * - BETA: Beta release
 * - ALPHA: Alpha release
 */
enum ReleaseType: string
{
    case MAJOR = 'major';
    case MINOR = 'minor';
    case PATCH = 'patch';
    case HOTFIX = 'hotfix';
    case BETA = 'beta';
    case ALPHA = 'alpha';

    /**
     * Get human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::MAJOR => 'Major Release',
            self::MINOR => 'Minor Release',
            self::PATCH => 'Patch Release',
            self::HOTFIX => 'Hotfix',
            self::BETA => 'Beta Release',
            self::ALPHA => 'Alpha Release',
        };
    }

    /**
     * Get badge text for display.
     */
    public function badge(): string
    {
        return match ($this) {
            self::MAJOR => 'Major',
            self::MINOR => 'Minor',
            self::PATCH => 'Patch',
            self::HOTFIX => 'Hotfix',
            self::BETA => 'Beta',
            self::ALPHA => 'Alpha',
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
