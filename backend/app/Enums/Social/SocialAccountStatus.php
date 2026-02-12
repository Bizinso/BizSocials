<?php

declare(strict_types=1);

namespace App\Enums\Social;

/**
 * SocialAccountStatus Enum
 *
 * Defines the connection status of a social account.
 *
 * - CONNECTED: OAuth valid, operational
 * - TOKEN_EXPIRED: Token expired, refresh failed
 * - REVOKED: User revoked on platform
 * - DISCONNECTED: User disconnected in app
 */
enum SocialAccountStatus: string
{
    case CONNECTED = 'connected';
    case TOKEN_EXPIRED = 'token_expired';
    case REVOKED = 'revoked';
    case DISCONNECTED = 'disconnected';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::CONNECTED => 'Connected',
            self::TOKEN_EXPIRED => 'Token Expired',
            self::REVOKED => 'Revoked',
            self::DISCONNECTED => 'Disconnected',
        };
    }

    /**
     * Check if the account is healthy (operational).
     * Only CONNECTED status is healthy.
     */
    public function isHealthy(): bool
    {
        return $this === self::CONNECTED;
    }

    /**
     * Check if the account can publish content.
     * Only CONNECTED status can publish.
     */
    public function canPublish(): bool
    {
        return $this === self::CONNECTED;
    }

    /**
     * Check if the account requires reconnection.
     * TOKEN_EXPIRED and REVOKED require reconnection.
     */
    public function requiresReconnect(): bool
    {
        return match ($this) {
            self::TOKEN_EXPIRED, self::REVOKED => true,
            self::CONNECTED, self::DISCONNECTED => false,
        };
    }

    /**
     * Get all statuses as array for validation.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
