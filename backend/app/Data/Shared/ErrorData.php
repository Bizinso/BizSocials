<?php

declare(strict_types=1);

namespace App\Data\Shared;

use Spatie\LaravelData\Data;

final class ErrorData extends Data
{
    public function __construct(
        public string $message,
        public ?string $field = null,
        public ?string $code = null,
    ) {}
}
