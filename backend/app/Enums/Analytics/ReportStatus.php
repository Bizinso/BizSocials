<?php

declare(strict_types=1);

namespace App\Enums\Analytics;

enum ReportStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::EXPIRED => 'Expired',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::COMPLETED, self::FAILED, self::EXPIRED => true,
            default => false,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::PROCESSING => 'blue',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::EXPIRED => 'orange',
        };
    }

    public function isDownloadable(): bool
    {
        return $this === self::COMPLETED;
    }

    public function canRetry(): bool
    {
        return $this === self::FAILED;
    }
}
