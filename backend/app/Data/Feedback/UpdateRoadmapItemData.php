<?php

declare(strict_types=1);

namespace App\Data\Feedback;

use App\Enums\Feedback\RoadmapCategory;
use App\Enums\Feedback\RoadmapStatus;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

final class UpdateRoadmapItemData extends Data
{
    public function __construct(
        #[Max(200)]
        public ?string $title = null,
        public ?string $description = null,
        public ?string $detailed_description = null,
        public ?RoadmapCategory $category = null,
        public ?RoadmapStatus $status = null,
        #[Max(20)]
        public ?string $target_quarter = null,
        public ?string $target_date = null,
        public ?int $progress_percentage = null,
        public ?bool $is_public = null,
    ) {}
}
