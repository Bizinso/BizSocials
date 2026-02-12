<?php

declare(strict_types=1);

namespace App\Enums\WhatsApp;

enum WhatsAppAutomationAction: string
{
    case AUTO_REPLY = 'auto_reply';
    case ASSIGN_USER = 'assign_user';
    case ASSIGN_TEAM = 'assign_team';
    case ADD_TAG = 'add_tag';
    case SEND_TEMPLATE = 'send_template';

    public function label(): string
    {
        return match ($this) {
            self::AUTO_REPLY => 'Auto Reply',
            self::ASSIGN_USER => 'Assign to User',
            self::ASSIGN_TEAM => 'Assign to Team',
            self::ADD_TAG => 'Add Tag',
            self::SEND_TEMPLATE => 'Send Template',
        };
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
