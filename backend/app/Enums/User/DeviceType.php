<?php

declare(strict_types=1);

namespace App\Enums\User;

/**
 * DeviceType Enum
 *
 * Defines the type of device used for a session.
 *
 * - DESKTOP: Desktop/laptop browser
 * - MOBILE: Mobile phone browser
 * - TABLET: Tablet browser
 * - API: API client (non-browser)
 */
enum DeviceType: string
{
    case DESKTOP = 'desktop';
    case MOBILE = 'mobile';
    case TABLET = 'tablet';
    case API = 'api';

    /**
     * Get human-readable label for the device type.
     */
    public function label(): string
    {
        return match ($this) {
            self::DESKTOP => 'Desktop',
            self::MOBILE => 'Mobile',
            self::TABLET => 'Tablet',
            self::API => 'API',
        };
    }

    /**
     * Detect device type from user agent string.
     */
    public static function fromUserAgent(string $userAgent): self
    {
        $userAgentLower = strtolower($userAgent);

        // Check for API clients first
        if (
            str_contains($userAgentLower, 'curl') ||
            str_contains($userAgentLower, 'postman') ||
            str_contains($userAgentLower, 'httpie') ||
            str_contains($userAgentLower, 'insomnia') ||
            str_contains($userAgentLower, 'axios') ||
            str_contains($userAgentLower, 'python-requests') ||
            str_contains($userAgentLower, 'guzzle')
        ) {
            return self::API;
        }

        // Check for tablets before mobile (tablets often include mobile keywords)
        if (
            str_contains($userAgentLower, 'ipad') ||
            str_contains($userAgentLower, 'tablet') ||
            (str_contains($userAgentLower, 'android') && !str_contains($userAgentLower, 'mobile'))
        ) {
            return self::TABLET;
        }

        // Check for mobile devices
        if (
            str_contains($userAgentLower, 'mobile') ||
            str_contains($userAgentLower, 'iphone') ||
            str_contains($userAgentLower, 'ipod') ||
            str_contains($userAgentLower, 'android') ||
            str_contains($userAgentLower, 'blackberry') ||
            str_contains($userAgentLower, 'windows phone')
        ) {
            return self::MOBILE;
        }

        // Default to desktop
        return self::DESKTOP;
    }

    /**
     * Get all device types as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
