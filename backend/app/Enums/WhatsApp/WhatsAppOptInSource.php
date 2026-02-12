<?php

declare(strict_types=1);

namespace App\Enums\WhatsApp;

enum WhatsAppOptInSource: string
{
    case MANUAL = 'manual';
    case IMPORT = 'import';
    case WEBSITE_FORM = 'website_form';
    case API = 'api';
    case CONVERSATION = 'conversation';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::IMPORT => 'Import',
            self::WEBSITE_FORM => 'Website Form',
            self::API => 'API',
            self::CONVERSATION => 'Conversation',
        };
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
