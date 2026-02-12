<?php

declare(strict_types=1);

namespace App\Data\Social;

use Spatie\LaravelData\Data;

final class HealthStatusData extends Data
{
    public function __construct(
        public int $total_accounts,
        public int $connected_count,
        public int $expired_count,
        public int $revoked_count,
        public int $disconnected_count,
        public array $by_platform,
    ) {}
}
