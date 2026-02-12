<?php

declare(strict_types=1);

namespace App\Data\Content;

use Spatie\LaravelData\Data;

final class SchedulePostData extends Data
{
    public function __construct(
        public string $scheduled_at,
        public ?string $timezone = null,
    ) {}
}
