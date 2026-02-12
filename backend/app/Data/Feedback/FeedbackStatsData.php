<?php

declare(strict_types=1);

namespace App\Data\Feedback;

use Spatie\LaravelData\Data;

final class FeedbackStatsData extends Data
{
    /**
     * @param array<string, int> $by_status
     * @param array<string, int> $by_type
     * @param array<string, int> $by_category
     */
    public function __construct(
        public int $total_feedback,
        public int $new_feedback,
        public int $under_review,
        public int $planned,
        public int $shipped,
        public int $declined,
        public array $by_status,
        public array $by_type,
        public array $by_category,
    ) {}
}
