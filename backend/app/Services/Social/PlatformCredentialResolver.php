<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Data\Social\PlatformCredentials;
use App\Enums\Social\SocialPlatform;
use App\Models\Platform\SocialPlatformIntegration;

final class PlatformCredentialResolver
{
    /**
     * Resolve credentials for a social platform.
     *
     * Priority: DB (SocialPlatformIntegration) → env fallback (config/services.php).
     */
    public function resolve(SocialPlatform $platform): PlatformCredentials
    {
        $integration = SocialPlatformIntegration::forPlatform($platform)->active()->first();

        if ($integration !== null) {
            return new PlatformCredentials(
                appId: $integration->getAppId(),
                appSecret: $integration->getAppSecret(),
                redirectUri: $integration->getRedirectUri($platform),
                apiVersion: $integration->api_version,
                scopes: $integration->getScopesFor($platform),
            );
        }

        return $this->resolveFromConfig($platform);
    }

    private function resolveFromConfig(SocialPlatform $platform): PlatformCredentials
    {
        $configKey = match ($platform) {
            SocialPlatform::FACEBOOK, SocialPlatform::INSTAGRAM => 'facebook',
            default => $platform->value,
        };

        $redirectUri = (string) config("services.{$configKey}.redirect", '');
        if ($redirectUri === '') {
            $redirectUri = config('app.url') . '/api/v1/oauth/' . $platform->value . '/callback';
        }

        return new PlatformCredentials(
            appId: (string) config("services.{$configKey}.client_id", ''),
            appSecret: (string) config("services.{$configKey}.client_secret", ''),
            redirectUri: $redirectUri,
            apiVersion: 'v24.0',
            scopes: $platform->oauthScopes(),
        );
    }
}
