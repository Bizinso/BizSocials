<?php

declare(strict_types=1);

namespace App\Data\Support;

use Spatie\LaravelData\Data;

final class UpdateTicketData extends Data
{
    public function __construct(
        public ?string $subject = null,
        public ?string $description = null,
    ) {}
}
