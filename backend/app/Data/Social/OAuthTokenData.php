<?php

declare(strict_types=1);

namespace App\Data\Social;

use Spatie\LaravelData\Data;

final class OAuthTokenData extends Data
{
    public function __construct(
        public string $access_token,
        public ?string $refresh_token,
        public ?int $expires_in,
        public string $platform_account_id,
        public string $account_name,
        public ?string $account_username,
        public ?string $profile_image_url,
        public ?array $metadata,
    ) {}
}
