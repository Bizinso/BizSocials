<?php

declare(strict_types=1);

namespace App\Data\Admin;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class SuspendData extends Data
{
    public function __construct(
        #[Required, Max(1000)]
        public string $reason,
    ) {}
}
