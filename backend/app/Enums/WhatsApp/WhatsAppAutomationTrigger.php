<?php

declare(strict_types=1);

namespace App\Enums\WhatsApp;

enum WhatsAppAutomationTrigger: string
{
    case NEW_CONVERSATION = 'new_conversation';
    case KEYWORD_MATCH = 'keyword_match';
    case OUTSIDE_BUSINESS_HOURS = 'outside_business_hours';
    case NO_RESPONSE_TIMEOUT = 'no_response_timeout';

    public function label(): string
    {
        return match ($this) {
            self::NEW_CONVERSATION => 'New Conversation',
            self::KEYWORD_MATCH => 'Keyword Match',
            self::OUTSIDE_BUSINESS_HOURS => 'Outside Business Hours',
            self::NO_RESPONSE_TIMEOUT => 'No Response Timeout',
        };
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
