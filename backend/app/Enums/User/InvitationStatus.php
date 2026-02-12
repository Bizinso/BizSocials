<?php

declare(strict_types=1);

namespace App\Enums\User;

/**
 * InvitationStatus Enum
 *
 * Defines the status of a user invitation.
 *
 * - PENDING: Invitation sent, awaiting response
 * - ACCEPTED: User has accepted and joined
 * - EXPIRED: Invitation TTL has passed
 * - REVOKED: Invitation was cancelled by the inviter
 */
enum InvitationStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACCEPTED => 'Accepted',
            self::EXPIRED => 'Expired',
            self::REVOKED => 'Revoked',
        };
    }

    /**
     * Check if this status is a final state (cannot be changed).
     * ACCEPTED, EXPIRED, and REVOKED are final states.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::ACCEPTED, self::EXPIRED, self::REVOKED], true);
    }

    /**
     * Check if the invitation can transition to a given status.
     *
     * Valid transitions:
     * - PENDING -> ACCEPTED (user accepts)
     * - PENDING -> EXPIRED (TTL passed)
     * - PENDING -> REVOKED (inviter cancels)
     * - Final states cannot transition
     */
    public function canTransitionTo(InvitationStatus $status): bool
    {
        if ($this->isFinal()) {
            return false;
        }

        return match ($this) {
            self::PENDING => in_array($status, [self::ACCEPTED, self::EXPIRED, self::REVOKED], true),
            default => false,
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
