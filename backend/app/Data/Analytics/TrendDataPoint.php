<?php

declare(strict_types=1);

namespace App\Data\Analytics;

use Spatie\LaravelData\Data;

final class TrendDataPoint extends Data
{
    public function __construct(
        public string $date,
        public int|float $value,
        public int|float|null $previous_value = null,
        public ?float $change_percent = null,
    ) {}
}
