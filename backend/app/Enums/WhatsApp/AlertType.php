<?php

declare(strict_types=1);

namespace App\Enums\WhatsApp;

enum AlertType: string
{
    case QUALITY_DROP = 'quality_drop';
    case RATE_LIMIT_HIT = 'rate_limit_hit';
    case TEMPLATE_REJECTION_SPIKE = 'template_rejection_spike';
    case SUSPENSION_RISK = 'suspension_risk';
    case ACCOUNT_BANNED = 'account_banned';

    public function label(): string
    {
        return match ($this) {
            self::QUALITY_DROP => 'Quality Rating Drop',
            self::RATE_LIMIT_HIT => 'Rate Limit Hit',
            self::TEMPLATE_REJECTION_SPIKE => 'Template Rejection Spike',
            self::SUSPENSION_RISK => 'Suspension Risk',
            self::ACCOUNT_BANNED => 'Account Banned',
        };
    }

    public function defaultSeverity(): AlertSeverity
    {
        return match ($this) {
            self::QUALITY_DROP => AlertSeverity::WARNING,
            self::RATE_LIMIT_HIT => AlertSeverity::WARNING,
            self::TEMPLATE_REJECTION_SPIKE => AlertSeverity::WARNING,
            self::SUSPENSION_RISK => AlertSeverity::CRITICAL,
            self::ACCOUNT_BANNED => AlertSeverity::CRITICAL,
        };
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
