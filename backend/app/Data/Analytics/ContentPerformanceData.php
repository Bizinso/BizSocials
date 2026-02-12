<?php

declare(strict_types=1);

namespace App\Data\Analytics;

use Spatie\LaravelData\Data;

final class ContentPerformanceData extends Data
{
    public function __construct(
        public string $content_type,
        public string $content_type_label,
        public int $total_posts,
        public int $total_impressions,
        public int $total_reach,
        public int $total_engagements,
        public float $avg_impressions,
        public float $avg_reach,
        public float $avg_engagements,
        public float $avg_engagement_rate,
        public float $share_of_posts,
        public float $share_of_engagement,
    ) {}
}
