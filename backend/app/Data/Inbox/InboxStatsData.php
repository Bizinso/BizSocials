<?php

declare(strict_types=1);

namespace App\Data\Inbox;

use Spatie\LaravelData\Data;

final class InboxStatsData extends Data
{
    /**
     * @param array<string, int> $by_type
     * @param array<string, int> $by_platform
     */
    public function __construct(
        public int $total,
        public int $unread,
        public int $read,
        public int $resolved,
        public int $archived,
        public int $assigned_to_me,
        public array $by_type,
        public array $by_platform,
    ) {}
}
