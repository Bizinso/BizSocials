<?php

declare(strict_types=1);

namespace App\Data\Feedback;

use App\Enums\Feedback\RoadmapCategory;
use App\Enums\Feedback\RoadmapStatus;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class CreateRoadmapItemData extends Data
{
    public function __construct(
        #[Required, Max(200)]
        public string $title,
        public ?string $description = null,
        public ?string $detailed_description = null,
        public RoadmapCategory $category = RoadmapCategory::PLATFORM,
        public RoadmapStatus $status = RoadmapStatus::PLANNED,
        #[Max(20)]
        public ?string $target_quarter = null,
        public ?string $target_date = null,
        public bool $is_public = true,
    ) {}
}
