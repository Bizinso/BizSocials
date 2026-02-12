<?php

declare(strict_types=1);

namespace App\Enums\WhatsApp;

enum AlertSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::INFO => 'Info',
            self::WARNING => 'Warning',
            self::CRITICAL => 'Critical',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INFO => 'blue',
            self::WARNING => 'amber',
            self::CRITICAL => 'red',
        };
    }

    public function requiresImmediateAction(): bool
    {
        return $this === self::CRITICAL;
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
