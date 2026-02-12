<?php

declare(strict_types=1);

namespace App\Data\Social;

use Spatie\LaravelData\Data;

final class OAuthUrlData extends Data
{
    public function __construct(
        public string $url,
        public string $state,
        public string $platform,
    ) {}
}
