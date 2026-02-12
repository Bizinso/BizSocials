<?php

declare(strict_types=1);

namespace App\Enums\Support;

/**
 * SupportChannel Enum
 *
 * Defines the channels through which support tickets can be submitted.
 *
 * - WEB_FORM: Submitted via web form
 * - EMAIL: Submitted via email
 * - IN_APP: Submitted from within the application
 * - CHAT: Submitted via live chat
 * - API: Submitted via API integration
 */
enum SupportChannel: string
{
    case WEB_FORM = 'web_form';
    case EMAIL = 'email';
    case IN_APP = 'in_app';
    case CHAT = 'chat';
    case API = 'api';

    /**
     * Get human-readable label for the channel.
     */
    public function label(): string
    {
        return match ($this) {
            self::WEB_FORM => 'Web Form',
            self::EMAIL => 'Email',
            self::IN_APP => 'In-App',
            self::CHAT => 'Chat',
            self::API => 'API',
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
