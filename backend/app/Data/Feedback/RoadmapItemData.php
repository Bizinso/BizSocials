<?php

declare(strict_types=1);

namespace App\Data\Feedback;

use App\Models\Feedback\RoadmapItem;
use Spatie\LaravelData\Data;

final class RoadmapItemData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public string $category,
        public string $category_label,
        public string $status,
        public string $status_label,
        public ?string $target_quarter,
        public ?string $target_date,
        public int $progress_percentage,
        public int $feedback_count,
        public int $vote_count,
        public string $created_at,
    ) {}

    /**
     * Create RoadmapItemData from a RoadmapItem model.
     */
    public static function fromModel(RoadmapItem $item): self
    {
        return new self(
            id: $item->id,
            title: $item->title,
            description: $item->description,
            category: $item->category->value,
            category_label: $item->category->label(),
            status: $item->status->value,
            status_label: $item->status->label(),
            target_quarter: $item->quarter,
            target_date: $item->target_date?->toDateString(),
            progress_percentage: $item->progress_percentage,
            feedback_count: $item->linked_feedback_count,
            vote_count: $item->total_votes,
            created_at: $item->created_at->toIso8601String(),
        );
    }
}
