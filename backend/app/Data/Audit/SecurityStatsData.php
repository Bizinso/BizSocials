<?php

declare(strict_types=1);

namespace App\Data\Audit;

use Spatie\LaravelData\Data;

final class SecurityStatsData extends Data
{
    /**
     * @param array<string, int> $events_by_type
     * @param array<string, int> $events_by_severity
     */
    public function __construct(
        public int $total_events,
        public int $critical_events,
        public int $high_events,
        public int $medium_events,
        public int $failed_logins_24h,
        public int $suspicious_activities,
        public int $unresolved_events,
        public array $events_by_type,
        public array $events_by_severity,
    ) {}
}
