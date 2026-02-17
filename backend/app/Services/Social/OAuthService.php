<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Data\Social\OAuthTokenData;
use App\Data\Social\OAuthUrlData;
use App\Enums\Social\SocialPlatform;
use App\Models\Social\SocialAccount;
use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class OAuthService extends BaseService
{
    /**
     * Cache TTL for OAuth state (10 minutes).
     */
    private const STATE_CACHE_TTL = 600;

    public function __construct(
        private readonly SocialPlatformAdapterFactory $adapterFactory,
        private readonly PlatformCredentialResolver $credentialResolver,
    ) {}

    /**
     * Get the authorization URL for a platform.
     */
    public function getAuthorizationUrl(SocialPlatform $platform, string $state): OAuthUrlData
    {
        // Store state in cache for validation during callback
        $cacheKey = $this->getStateCacheKey($state);
        Cache::put($cacheKey, [
            'platform' => $platform->value,
            'created_at' => now()->toIso8601String(),
        ], self::STATE_CACHE_TTL);

        // Build authorization URL based on platform
        $url = match ($platform) {
            SocialPlatform::LINKEDIN => $this->buildLinkedInAuthUrl($state),
            SocialPlatform::FACEBOOK => $this->buildFacebookAuthUrl($state),
            SocialPlatform::INSTAGRAM => $this->buildInstagramAuthUrl($state),
            SocialPlatform::TWITTER => $this->buildTwitterAuthUrl($state),
            SocialPlatform::YOUTUBE => $this->buildYouTubeAuthUrl($state),
            SocialPlatform::WHATSAPP => $this->buildWhatsAppAuthUrl($state),
        };

        $this->log('OAuth authorization URL generated', [
            'platform' => $platform->value,
            'state' => $state,
        ]);

        return new OAuthUrlData(
            url: $url,
            state: $state,
            platform: $platform->value,
        );
    }

    /**
     * Handle OAuth callback and exchange code for tokens.
     *
     * @throws ValidationException
     */
    public function handleCallback(SocialPlatform $platform, string $code, string $state): OAuthTokenData
    {
        // Validate state
        $cacheKey = $this->getStateCacheKey($state);
        $stateData = Cache::get($cacheKey);

        if ($stateData === null) {
            throw ValidationException::withMessages([
                'state' => ['Invalid or expired OAuth state. Please try again.'],
            ]);
        }

        if ($stateData['platform'] !== $platform->value) {
            throw ValidationException::withMessages([
                'platform' => ['OAuth state platform mismatch.'],
            ]);
        }

        // Clear the used state
        Cache::forget($cacheKey);

        // For Twitter, retrieve and store code verifier in request context
        if ($platform === SocialPlatform::TWITTER) {
            $codeVerifier = Cache::pull('twitter_code_verifier:' . $state);
            if ($codeVerifier) {
                request()->merge(['twitter_code_verifier' => $codeVerifier]);
            }
        }

        // Exchange code for tokens via platform adapter
        $adapter = $this->adapterFactory->create($platform);
        $credentials = $this->credentialResolver->resolve($platform);
        $redirectUri = $credentials->redirectUri ?: $this->getCallbackUrl($platform);
        $tokenData = $adapter->exchangeCode($code, $redirectUri);

        $this->log('OAuth callback handled', [
            'platform' => $platform->value,
        ]);

        return $tokenData;
    }

    /**
     * Refresh tokens for a social account.
     *
     * @throws ValidationException
     */
    public function refreshToken(SocialAccount $account): OAuthTokenData
    {
        if ($account->refreshToken === null) {
            // For Facebook/Instagram, use long-lived token exchange instead of refresh tokens
            if (in_array($account->platform, [SocialPlatform::FACEBOOK, SocialPlatform::INSTAGRAM])) {
                $adapter = $this->adapterFactory->create($account->platform);

                // Facebook page accounts store the user token in metadata
                // (the access_token is a non-expiring page token that can't be exchanged)
                $tokenToRefresh = $account->platform === SocialPlatform::FACEBOOK
                    ? ($account->getMetadata('user_token') ?? $account->accessToken)
                    : $account->accessToken;

                $tokenData = $adapter->refreshToken($tokenToRefresh);

                // Update metadata with new user token if returned
                if ($tokenData->metadata !== null && isset($tokenData->metadata['user_token'])) {
                    $account->setMetadata('user_token', $tokenData->metadata['user_token']);
                    if (isset($tokenData->metadata['user_token_expires_in'])) {
                        $account->setMetadata('user_token_expires_in', $tokenData->metadata['user_token_expires_in']);
                    }
                }

                $this->log('OAuth token refreshed (long-lived exchange)', [
                    'account_id' => $account->id,
                    'platform' => $account->platform->value,
                ]);

                return $tokenData;
            }

            throw ValidationException::withMessages([
                'refresh_token' => ['No refresh token available.'],
            ]);
        }

        $adapter = $this->adapterFactory->create($account->platform);
        $tokenData = $adapter->refreshToken($account->refreshToken);

        $this->log('OAuth token refreshed', [
            'account_id' => $account->id,
            'platform' => $account->platform->value,
        ]);

        return $tokenData;
    }

    /**
     * Revoke tokens for a social account.
     */
    public function revokeToken(SocialAccount $account): void
    {
        $adapter = $this->adapterFactory->create($account->platform);
        $adapter->revokeToken($account->accessToken);

        $this->log('OAuth token revoked', [
            'account_id' => $account->id,
            'platform' => $account->platform->value,
        ]);
    }

    /**
     * Validate that a token is still valid.
     */
    public function validateToken(SocialAccount $account): bool
    {
        if ($account->isTokenExpired()) {
            return false;
        }

        return $account->isConnected() && !$account->isTokenExpired();
    }

    /**
     * Generate a new OAuth state string.
     */
    public function generateState(): string
    {
        return Str::random(40);
    }

    /**
     * Get the cache key for an OAuth state.
     */
    private function getStateCacheKey(string $state): string
    {
        return 'oauth_state:' . $state;
    }

    // ==========================================================================
    // Authorization URL Builders
    // ==========================================================================

    private function buildLinkedInAuthUrl(string $state): string
    {
        $params = [
            'response_type' => 'code',
            'client_id' => config('services.linkedin.client_id', ''),
            'redirect_uri' => $this->getCallbackUrl(SocialPlatform::LINKEDIN),
            'state' => $state,
            'scope' => implode(' ', SocialPlatform::LINKEDIN->oauthScopes()),
        ];

        return 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query($params);
    }

    private function buildFacebookAuthUrl(string $state): string
    {
        $credentials = $this->credentialResolver->resolve(SocialPlatform::FACEBOOK);

        $params = [
            'client_id' => $credentials->appId,
            'redirect_uri' => $credentials->redirectUri ?: $this->getCallbackUrl(SocialPlatform::FACEBOOK),
            'state' => $state,
            'scope' => implode(',', $credentials->scopes),
            'response_type' => 'code',
        ];

        return 'https://www.facebook.com/' . $credentials->apiVersion . '/dialog/oauth?' . http_build_query($params);
    }

    private function buildInstagramAuthUrl(string $state): string
    {
        $credentials = $this->credentialResolver->resolve(SocialPlatform::INSTAGRAM);

        $params = [
            'client_id' => $credentials->appId,
            'redirect_uri' => $credentials->redirectUri ?: $this->getCallbackUrl(SocialPlatform::INSTAGRAM),
            'state' => $state,
            'scope' => implode(',', $credentials->scopes),
            'response_type' => 'code',
        ];

        return 'https://www.facebook.com/' . $credentials->apiVersion . '/dialog/oauth?' . http_build_query($params);
    }

    private function buildTwitterAuthUrl(string $state): string
    {
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        // Store verifier for later use during code exchange
        Cache::put('twitter_code_verifier:' . $state, $verifier, self::STATE_CACHE_TTL);

        $params = [
            'response_type' => 'code',
            'client_id' => config('services.twitter.client_id', ''),
            'redirect_uri' => $this->getCallbackUrl(SocialPlatform::TWITTER),
            'scope' => implode(' ', SocialPlatform::TWITTER->oauthScopes()),
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ];

        return 'https://twitter.com/i/oauth2/authorize?' . http_build_query($params);
    }

    private function buildWhatsAppAuthUrl(string $state): string
    {
        $params = [
            'client_id' => config('services.whatsapp.app_id', ''),
            'redirect_uri' => $this->getCallbackUrl(SocialPlatform::WHATSAPP),
            'state' => $state,
            'scope' => implode(',', SocialPlatform::WHATSAPP->oauthScopes()),
            'response_type' => 'code',
            'config_id' => config('services.whatsapp.config_id', ''),
        ];

        return 'https://www.facebook.com/v19.0/dialog/oauth?' . http_build_query($params);
    }

    private function buildYouTubeAuthUrl(string $state): string
    {
        $params = [
            'client_id' => config('services.youtube.client_id', ''),
            'redirect_uri' => $this->getCallbackUrl(SocialPlatform::YOUTUBE),
            'response_type' => 'code',
            'scope' => implode(' ', SocialPlatform::YOUTUBE->oauthScopes()),
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    // ==========================================================================
    // Helper Methods
    // ==========================================================================

    /**
     * Get the OAuth callback URL for a platform.
     */
    private function getCallbackUrl(SocialPlatform $platform): string
    {
        return config('app.url') . '/api/v1/oauth/' . $platform->value . '/callback';
    }
}
