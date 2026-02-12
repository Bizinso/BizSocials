<?php

declare(strict_types=1);

namespace App\Data\Social;

final readonly class PlatformCredentials
{
    /**
     * @param array<string> $scopes
     */
    public function __construct(
        public string $appId,
        public string $appSecret,
        public string $redirectUri,
        public string $apiVersion,
        public array $scopes,
    ) {}
}
