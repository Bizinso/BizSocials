<?php

declare(strict_types=1);

namespace App\Data\Analytics;

use Spatie\LaravelData\Data;

final class DashboardMetricsData extends Data
{
    public function __construct(
        public int $impressions,
        public int $reach,
        public int $engagements,
        public int $likes,
        public int $comments,
        public int $shares,
        public int $posts_published,
        public int $followers_total,
        public int $followers_gained,
        public float $engagement_rate,
        public ?float $impressions_change,
        public ?float $reach_change,
        public ?float $engagement_change,
        public ?float $followers_change,
        public string $period,
        public string $start_date,
        public string $end_date,
    ) {}
}
