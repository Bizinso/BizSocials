<?php

declare(strict_types=1);

namespace App\Data\User;

use Spatie\LaravelData\Data;

final class UpdateProfileData extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $timezone = null,
        public ?string $phone = null,
        public ?string $job_title = null,
    ) {}
}
