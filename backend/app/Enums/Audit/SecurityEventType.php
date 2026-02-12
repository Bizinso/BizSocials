<?php

declare(strict_types=1);

namespace App\Enums\Audit;

/**
 * SecurityEventType Enum
 *
 * Defines the type of security event that occurred.
 *
 * Authentication events:
 * - LOGIN_SUCCESS: Successful login
 * - LOGIN_FAILURE: Failed login attempt
 * - LOGOUT: User logged out
 *
 * Password events:
 * - PASSWORD_CHANGE: Password was changed
 * - PASSWORD_RESET_REQUEST: Password reset was requested
 * - PASSWORD_RESET_COMPLETE: Password reset was completed
 *
 * MFA events:
 * - MFA_ENABLED: MFA was enabled
 * - MFA_DISABLED: MFA was disabled
 * - MFA_CHALLENGE_SUCCESS: MFA challenge passed
 * - MFA_CHALLENGE_FAILURE: MFA challenge failed
 *
 * Security events:
 * - SUSPICIOUS_ACTIVITY: Suspicious activity detected
 * - ACCOUNT_LOCKED: Account was locked
 * - ACCOUNT_UNLOCKED: Account was unlocked
 * - SESSION_INVALIDATED: Session was invalidated
 * - API_KEY_CREATED: API key was created
 * - API_KEY_REVOKED: API key was revoked
 * - IP_BLOCKED: IP address was blocked
 * - IP_WHITELISTED: IP address was whitelisted
 */
enum SecurityEventType: string
{
    case LOGIN_SUCCESS = 'login_success';
    case LOGIN_FAILURE = 'login_failure';
    case LOGOUT = 'logout';
    case PASSWORD_CHANGE = 'password_change';
    case PASSWORD_RESET_REQUEST = 'password_reset_request';
    case PASSWORD_RESET_COMPLETE = 'password_reset_complete';
    case MFA_ENABLED = 'mfa_enabled';
    case MFA_DISABLED = 'mfa_disabled';
    case MFA_CHALLENGE_SUCCESS = 'mfa_challenge_success';
    case MFA_CHALLENGE_FAILURE = 'mfa_challenge_failure';
    case SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    case ACCOUNT_LOCKED = 'account_locked';
    case ACCOUNT_UNLOCKED = 'account_unlocked';
    case SESSION_INVALIDATED = 'session_invalidated';
    case API_KEY_CREATED = 'api_key_created';
    case API_KEY_REVOKED = 'api_key_revoked';
    case IP_BLOCKED = 'ip_blocked';
    case IP_WHITELISTED = 'ip_whitelisted';

    /**
     * Get human-readable label for the event type.
     */
    public function label(): string
    {
        return match ($this) {
            self::LOGIN_SUCCESS => 'Login Success',
            self::LOGIN_FAILURE => 'Login Failure',
            self::LOGOUT => 'Logout',
            self::PASSWORD_CHANGE => 'Password Change',
            self::PASSWORD_RESET_REQUEST => 'Password Reset Request',
            self::PASSWORD_RESET_COMPLETE => 'Password Reset Complete',
            self::MFA_ENABLED => 'MFA Enabled',
            self::MFA_DISABLED => 'MFA Disabled',
            self::MFA_CHALLENGE_SUCCESS => 'MFA Challenge Success',
            self::MFA_CHALLENGE_FAILURE => 'MFA Challenge Failure',
            self::SUSPICIOUS_ACTIVITY => 'Suspicious Activity',
            self::ACCOUNT_LOCKED => 'Account Locked',
            self::ACCOUNT_UNLOCKED => 'Account Unlocked',
            self::SESSION_INVALIDATED => 'Session Invalidated',
            self::API_KEY_CREATED => 'API Key Created',
            self::API_KEY_REVOKED => 'API Key Revoked',
            self::IP_BLOCKED => 'IP Blocked',
            self::IP_WHITELISTED => 'IP Whitelisted',
        };
    }

    /**
     * Get the severity level for this event type.
     */
    public function severity(): string
    {
        return match ($this) {
            self::LOGIN_SUCCESS,
            self::LOGOUT,
            self::PASSWORD_RESET_COMPLETE,
            self::MFA_ENABLED,
            self::MFA_CHALLENGE_SUCCESS,
            self::ACCOUNT_UNLOCKED,
            self::API_KEY_CREATED,
            self::IP_WHITELISTED => 'info',

            self::PASSWORD_CHANGE,
            self::PASSWORD_RESET_REQUEST,
            self::MFA_DISABLED,
            self::SESSION_INVALIDATED,
            self::API_KEY_REVOKED => 'low',

            self::LOGIN_FAILURE,
            self::MFA_CHALLENGE_FAILURE => 'medium',

            self::SUSPICIOUS_ACTIVITY,
            self::IP_BLOCKED => 'high',

            self::ACCOUNT_LOCKED => 'critical',
        };
    }

    /**
     * Check if this event type requires an alert notification.
     */
    public function requiresAlert(): bool
    {
        return in_array($this, [
            self::SUSPICIOUS_ACTIVITY,
            self::ACCOUNT_LOCKED,
            self::IP_BLOCKED,
            self::MFA_DISABLED,
        ], true);
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
