<?php

declare(strict_types=1);

namespace App\Data\Support;

use Spatie\LaravelData\Data;

final class SupportStatsData extends Data
{
    /**
     * @param array<string, int> $by_priority
     * @param array<string, int> $by_type
     */
    public function __construct(
        public int $total_tickets,
        public int $open_tickets,
        public int $pending_tickets,
        public int $resolved_tickets,
        public int $closed_tickets,
        public int $unassigned_tickets,
        public array $by_priority,
        public array $by_type,
    ) {}
}
