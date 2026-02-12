<?php

declare(strict_types=1);

namespace App\Data\Social;

use App\Models\Social\SocialAccount;
use Spatie\LaravelData\Data;

final class SocialAccountData extends Data
{
    public function __construct(
        public string $id,
        public string $workspace_id,
        public string $platform,
        public string $platform_account_id,
        public string $account_name,
        public ?string $account_username,
        public ?string $profile_image_url,
        public string $status,
        public bool $is_healthy,
        public bool $can_publish,
        public bool $requires_reconnect,
        public ?string $token_expires_at,
        public string $connected_at,
        public ?string $last_refreshed_at,
    ) {}

    /**
     * Create SocialAccountData from a SocialAccount model.
     */
    public static function fromModel(SocialAccount $account): self
    {
        return new self(
            id: $account->id,
            workspace_id: $account->workspace_id,
            platform: $account->platform->value,
            platform_account_id: $account->platform_account_id,
            account_name: $account->account_name,
            account_username: $account->account_username,
            profile_image_url: $account->profile_image_url,
            status: $account->status->value,
            is_healthy: $account->isHealthy(),
            can_publish: $account->canPublish(),
            requires_reconnect: $account->requiresReconnect(),
            token_expires_at: $account->token_expires_at?->toIso8601String(),
            connected_at: $account->connected_at->toIso8601String(),
            last_refreshed_at: $account->last_refreshed_at?->toIso8601String(),
        );
    }
}
