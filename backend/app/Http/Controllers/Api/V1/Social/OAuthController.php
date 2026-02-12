<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Social;

use App\Data\Social\ConnectAccountData;
use App\Data\Social\OAuthTokenData;
use App\Data\Social\SocialAccountData;
use App\Enums\Social\SocialPlatform;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Social\OAuthConnectRequest;
use App\Http\Requests\Social\OAuthExchangeRequest;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Social\OAuthService;
use App\Services\Social\SocialAccountService;
use App\Services\Social\SocialPlatformAdapterFactory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class OAuthController extends Controller
{
    private const EXCHANGE_CACHE_TTL = 900; // 15 minutes

    public function __construct(
        private readonly OAuthService $oauthService,
        private readonly SocialAccountService $socialAccountService,
        private readonly SocialPlatformAdapterFactory $adapterFactory,
    ) {}

    /**
     * Get OAuth authorization URL for a platform.
     */
    public function getAuthorizationUrl(Request $request, string $platform): JsonResponse
    {
        $socialPlatform = SocialPlatform::tryFrom($platform);

        if ($socialPlatform === null) {
            return $this->error('Unsupported platform', 400);
        }

        $state = $this->oauthService->generateState();
        $oauthUrl = $this->oauthService->getAuthorizationUrl($socialPlatform, $state);

        return $this->success(
            $oauthUrl->toArray(),
            'Authorization URL generated successfully'
        );
    }

    /**
     * Handle OAuth callback from platform.
     *
     * Receives the redirect from the OAuth provider and forwards
     * code+state to the frontend callback page.
     */
    public function callback(Request $request, string $platform): RedirectResponse|JsonResponse
    {
        $frontendUrl = config('app.frontend_url', config('app.url'));

        $error = $request->query('error');
        $errorDescription = $request->query('error_description');

        if ($error !== null) {
            return redirect()->to(
                $frontendUrl . '/app/oauth/callback?' . http_build_query([
                    'error' => $error,
                    'error_description' => $errorDescription ?? 'Authorization was denied.',
                ])
            );
        }

        $code = $request->query('code');
        $state = $request->query('state');

        if ($code === null || $state === null) {
            return redirect()->to(
                $frontendUrl . '/app/oauth/callback?' . http_build_query([
                    'error' => 'missing_params',
                    'error_description' => 'Missing authorization code or state parameter.',
                ])
            );
        }

        return redirect()->to(
            $frontendUrl . '/app/oauth/callback?' . http_build_query([
                'platform' => $platform,
                'code' => $code,
                'state' => $state,
            ])
        );
    }

    /**
     * Exchange OAuth code for tokens and return available accounts/pages.
     *
     * Validates the state, exchanges the authorization code, caches the
     * resulting tokens, and returns available pages (Facebook) or the
     * detected account (Instagram) for user selection.
     */
    public function exchange(OAuthExchangeRequest $request, string $platform): JsonResponse
    {
        $socialPlatform = SocialPlatform::tryFrom($platform);

        if ($socialPlatform === null) {
            return $this->error('Unsupported platform', 400);
        }

        try {
            $code = $request->validated('code');
            $state = $request->validated('state');

            // Exchange code for tokens (validates state, single-use)
            $tokenData = $this->oauthService->handleCallback($socialPlatform, $code, $state);

            // Cache the full token data for the connect step
            $sessionKey = Str::random(64);
            Cache::put('oauth_exchange:' . $sessionKey, [
                'platform' => $socialPlatform->value,
                'token_data' => serialize($tokenData),
            ], self::EXCHANGE_CACHE_TTL);

            // Build response for frontend
            $response = [
                'session_key' => $sessionKey,
                'platform' => $socialPlatform->value,
                'account' => [
                    'platform_account_id' => $tokenData->platform_account_id,
                    'account_name' => $tokenData->account_name,
                    'account_username' => $tokenData->account_username,
                    'profile_image_url' => $tokenData->profile_image_url,
                ],
            ];

            // For Facebook, include the list of available pages for user selection
            if ($socialPlatform === SocialPlatform::FACEBOOK) {
                $response['pages'] = $tokenData->metadata['pages'] ?? [];
            }

            return $this->success($response, 'OAuth exchange successful');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Complete the OAuth connection by creating the social account.
     *
     * Uses the cached exchange data and optional page selection to
     * create the social account in the specified workspace.
     */
    public function connect(OAuthConnectRequest $request, string $platform): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $socialPlatform = SocialPlatform::tryFrom($platform);

        if ($socialPlatform === null) {
            return $this->error('Unsupported platform', 400);
        }

        $workspaceId = $request->validated('workspace_id');
        $sessionKey = $request->validated('session_key');
        $selectedPageId = $request->validated('page_id');

        // Retrieve cached exchange data
        $cacheKey = 'oauth_exchange:' . $sessionKey;
        $cached = Cache::get($cacheKey);

        if ($cached === null) {
            return $this->error('OAuth session expired. Please try connecting again.', 400);
        }

        if ($cached['platform'] !== $socialPlatform->value) {
            return $this->error('Platform mismatch.', 400);
        }

        /** @var OAuthTokenData $tokenData */
        $tokenData = unserialize($cached['token_data']);
        Cache::forget($cacheKey);

        // Validate workspace access
        $workspace = Workspace::find($workspaceId);

        if ($workspace === null) {
            return $this->notFound('Workspace not found');
        }

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        try {
            $this->socialAccountService->validateUserCanManageSocialAccounts($user, $workspace);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->forbidden($e->getMessage());
        }

        try {
            // For Facebook: resolve selected page if different from default
            if ($socialPlatform === SocialPlatform::FACEBOOK && $selectedPageId !== null) {
                $currentPageId = $tokenData->metadata['page_id'] ?? null;
                if ($selectedPageId !== $currentPageId) {
                    $tokenData = $this->resolveSelectedPage($tokenData, $selectedPageId);
                }
            }

            // Build ConnectAccountData
            $expiresAt = $tokenData->expires_in !== null
                ? Carbon::now()->addSeconds($tokenData->expires_in)->toIso8601String()
                : null;

            $connectData = new ConnectAccountData(
                platform: $socialPlatform,
                platform_account_id: $tokenData->platform_account_id,
                account_name: $tokenData->account_name,
                account_username: $tokenData->account_username,
                profile_image_url: $tokenData->profile_image_url,
                access_token: $tokenData->access_token,
                refresh_token: $tokenData->refresh_token,
                token_expires_at: $expiresAt,
                metadata: $tokenData->metadata,
            );

            $account = $this->socialAccountService->connect($workspace, $user, $connectData);

            return $this->created(
                SocialAccountData::fromModel($account)->toArray(),
                'Social account connected successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Resolve a selected Facebook page's access token.
     *
     * Uses the stored long-lived user token to fetch the selected page's
     * non-expiring access token from the Graph API.
     */
    private function resolveSelectedPage(OAuthTokenData $tokenData, string $pageId): OAuthTokenData
    {
        $userToken = $tokenData->metadata['user_token'] ?? null;

        if ($userToken === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'page_id' => ['Unable to resolve page. Missing user token.'],
            ]);
        }

        $adapter = $this->adapterFactory->create(SocialPlatform::FACEBOOK);
        $graphBase = 'https://graph.facebook.com/v24.0';

        // Fetch the specific page's access token using the user token
        $client = new \GuzzleHttp\Client(['timeout' => 30]);
        $response = $client->get($graphBase . '/me/accounts', [
            'query' => [
                'fields' => 'id,name,access_token',
                'access_token' => $userToken,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        foreach ($data['data'] ?? [] as $page) {
            if ($page['id'] === $pageId) {
                return new OAuthTokenData(
                    access_token: $page['access_token'],
                    refresh_token: null,
                    expires_in: null, // Page tokens from long-lived user tokens never expire
                    platform_account_id: $page['id'],
                    account_name: $page['name'],
                    account_username: null,
                    profile_image_url: $graphBase . '/' . $page['id'] . '/picture?type=large',
                    metadata: [
                        'page_id' => $page['id'],
                        'user_token' => $userToken,
                        'user_token_expires_in' => $tokenData->metadata['user_token_expires_in'] ?? null,
                        'pages' => $tokenData->metadata['pages'] ?? [],
                    ],
                );
            }
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            'page_id' => ['The selected page was not found. You may not have admin access to it.'],
        ]);
    }
}
