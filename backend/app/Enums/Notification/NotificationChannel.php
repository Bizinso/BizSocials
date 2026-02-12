<?php

declare(strict_types=1);

namespace App\Enums\Notification;

/**
 * NotificationChannel Enum
 *
 * Defines the delivery channels for notifications.
 *
 * - IN_APP: In-application notification (notification center)
 * - EMAIL: Email notification
 * - PUSH: Push notification (mobile/web)
 * - SMS: SMS text message notification
 */
enum NotificationChannel: string
{
    case IN_APP = 'in_app';
    case EMAIL = 'email';
    case PUSH = 'push';
    case SMS = 'sms';

    /**
     * Get human-readable label for the channel.
     */
    public function label(): string
    {
        return match ($this) {
            self::IN_APP => 'In-App',
            self::EMAIL => 'Email',
            self::PUSH => 'Push Notification',
            self::SMS => 'SMS',
        };
    }

    /**
     * Get description for the channel.
     */
    public function description(): string
    {
        return match ($this) {
            self::IN_APP => 'Notifications displayed in the application notification center',
            self::EMAIL => 'Notifications sent to your email address',
            self::PUSH => 'Push notifications sent to your mobile device or browser',
            self::SMS => 'Text message notifications sent to your phone',
        };
    }

    /**
     * Check if this channel is enabled by default for new users.
     */
    public function isEnabledByDefault(): bool
    {
        return match ($this) {
            self::IN_APP => true,
            self::EMAIL => true,
            self::PUSH => false,
            self::SMS => false,
        };
    }

    /**
     * Check if this channel requires additional setup.
     */
    public function requiresSetup(): bool
    {
        return match ($this) {
            self::IN_APP => false,
            self::EMAIL => false,
            self::PUSH => true,
            self::SMS => true,
        };
    }

    /**
     * Get the icon for this channel.
     */
    public function icon(): string
    {
        return match ($this) {
            self::IN_APP => 'bell',
            self::EMAIL => 'envelope',
            self::PUSH => 'device-phone-mobile',
            self::SMS => 'chat-bubble-left-ellipsis',
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

    /**
     * Get channels that are enabled by default.
     *
     * @return array<self>
     */
    public static function defaultEnabled(): array
    {
        return array_filter(
            self::cases(),
            fn (self $channel): bool => $channel->isEnabledByDefault()
        );
    }
}
