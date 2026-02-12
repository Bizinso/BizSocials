<?php

declare(strict_types=1);

namespace App\Data\Audit;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class RequestDeletionData extends Data
{
    public function __construct(
        #[Required]
        public string $reason,
    ) {}
}
