<?php

declare(strict_types=1);

namespace App\Enums\WhatsApp;

enum WhatsAppMessageType: string
{
    case TEXT = 'text';
    case IMAGE = 'image';
    case VIDEO = 'video';
    case DOCUMENT = 'document';
    case AUDIO = 'audio';
    case LOCATION = 'location';
    case CONTACT = 'contact';
    case INTERACTIVE_BUTTONS = 'interactive_buttons';
    case INTERACTIVE_LIST = 'interactive_list';
    case TEMPLATE = 'template';
    case STICKER = 'sticker';
    case REACTION = 'reaction';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Text',
            self::IMAGE => 'Image',
            self::VIDEO => 'Video',
            self::DOCUMENT => 'Document',
            self::AUDIO => 'Audio',
            self::LOCATION => 'Location',
            self::CONTACT => 'Contact',
            self::INTERACTIVE_BUTTONS => 'Interactive Buttons',
            self::INTERACTIVE_LIST => 'Interactive List',
            self::TEMPLATE => 'Template',
            self::STICKER => 'Sticker',
            self::REACTION => 'Reaction',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function isMedia(): bool
    {
        return match ($this) {
            self::IMAGE, self::VIDEO, self::DOCUMENT, self::AUDIO, self::STICKER => true,
            default => false,
        };
    }

    public function isInteractive(): bool
    {
        return match ($this) {
            self::INTERACTIVE_BUTTONS, self::INTERACTIVE_LIST => true,
            default => false,
        };
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
