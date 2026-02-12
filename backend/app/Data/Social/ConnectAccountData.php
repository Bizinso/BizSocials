<?php

declare(strict_types=1);

namespace App\Data\Social;

use App\Enums\Social\SocialPlatform;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class ConnectAccountData extends Data
{
    public function __construct(
        #[Required]
        public SocialPlatform $platform,
        #[Required]
        public string $platform_account_id,
        #[Required]
        public string $account_name,
        public ?string $account_username = null,
        public ?string $profile_image_url = null,
        #[Required]
        public string $access_token,
        public ?string $refresh_token = null,
        public ?string $token_expires_at = null,
        public ?array $metadata = null,
    ) {}
}
