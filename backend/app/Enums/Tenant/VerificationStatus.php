<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

/**
 * VerificationStatus Enum
 *
 * Defines the verification status for tenant business profiles.
 *
 * - PENDING: Verification is pending review
 * - VERIFIED: Business has been verified
 * - FAILED: Verification failed
 */
enum VerificationStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case FAILED = 'failed';

    /**
     * Get human-readable label for the verification status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::VERIFIED => 'Verified',
            self::FAILED => 'Failed',
        };
    }

    /**
     * Check if the status indicates verified.
     */
    public function isVerified(): bool
    {
        return $this === self::VERIFIED;
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
