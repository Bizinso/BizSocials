<?php

declare(strict_types=1);

namespace App\Enums\Analytics;

enum PeriodType: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::MONTHLY => 'Monthly',
        };
    }

    public function days(): int
    {
        return match ($this) {
            self::DAILY => 1,
            self::WEEKLY => 7,
            self::MONTHLY => 30,
        };
    }

    public function dateFormat(): string
    {
        return match ($this) {
            self::DAILY => 'Y-m-d',
            self::WEEKLY => 'Y-W',
            self::MONTHLY => 'Y-m',
        };
    }
}
