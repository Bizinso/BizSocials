<?php

declare(strict_types=1);

namespace App\Services\Social\Adapters;

use App\Data\Social\OAuthTokenData;
use App\Data\Social\PlatformCredentials;
use App\Enums\Inbox\InboxItemType;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Services\Social\Contracts\PublishResult;
use App\Services\Social\Contracts\SocialPlatformAdapter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;

final class FacebookAdapter implements SocialPlatformAdapter
{
    private readonly string $graphBase;

    public function __construct(
        private readonly Client $client,
        private readonly PlatformCredentials $credentials,
    ) {
        $this->graphBase = 'https://graph.facebook.com/' . $this->credentials->apiVersion;
    }

    public function exchangeCode(string $code, string $redirectUri): OAuthTokenData
    {
        // Step 1: Exchange authorization code for short-lived user token (~1 hour)
        $response = $this->client->get($this->graphBase . '/oauth/access_token', [
            'query' => [
                'client_id' => $this->credentials->appId,
                'client_secret' => $this->credentials->appSecret,
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        // Step 2: Exchange short-lived token for long-lived user token (~60 days)
        $longLived = $this->exchangeForLongLivedToken($data['access_token']);
        $userToken = $longLived['access_token'];
        $userTokenExpiresIn = $longLived['expires_in'] ?? 5184000;

        // Step 3: Get page access tokens via long-lived user token
        // Page tokens obtained this way are non-expiring
        $pages = $this->getPages($userToken);
        $page = $pages[0] ?? null;

        if ($page) {
            return new OAuthTokenData(
                access_token: $page['access_token'],
                refresh_token: null,
                expires_in: null, // Page tokens from long-lived user tokens never expire
                platform_account_id: $page['id'],
                account_name: $page['name'],
                account_username: null,
                profile_image_url: $this->graphBase . '/' . $page['id'] . '/picture?type=large',
                metadata: [
                    'page_id' => $page['id'],
                    'user_token' => $userToken,
                    'user_token_expires_in' => $userTokenExpiresIn,
                    'pages' => array_map(fn (array $p) => [
                        'id' => $p['id'],
                        'name' => $p['name'],
                    ], $pages),
                ],
            );
        }

        // Fallback: no pages found — return long-lived user token directly
        $profile = $this->getProfile($userToken);

        return new OAuthTokenData(
            access_token: $userToken,
            refresh_token: null,
            expires_in: $userTokenExpiresIn,
            platform_account_id: $profile['id'] ?? '',
            account_name: $profile['name'] ?? 'Facebook Account',
            account_username: null,
            profile_image_url: $profile['picture']['data']['url'] ?? null,
            metadata: [
                'page_id' => null,
                'user_token' => $userToken,
                'user_token_expires_in' => $userTokenExpiresIn,
            ],
        );
    }

    public function refreshToken(string $refreshToken): OAuthTokenData
    {
        // For Facebook, the input should be the long-lived user token (from metadata).
        // Exchange it for a new long-lived user token, then fetch page tokens.
        $longLived = $this->exchangeForLongLivedToken($refreshToken);
        $userToken = $longLived['access_token'];
        $userTokenExpiresIn = $longLived['expires_in'] ?? 5184000;

        // Get page access tokens (non-expiring via long-lived user token)
        $pages = $this->getPages($userToken);
        $page = $pages[0] ?? null;

        if ($page) {
            return new OAuthTokenData(
                access_token: $page['access_token'],
                refresh_token: null,
                expires_in: null, // Page tokens from long-lived user tokens never expire
                platform_account_id: $page['id'],
                account_name: $page['name'],
                account_username: null,
                profile_image_url: $this->graphBase . '/' . $page['id'] . '/picture?type=large',
                metadata: [
                    'page_id' => $page['id'],
                    'user_token' => $userToken,
                    'user_token_expires_in' => $userTokenExpiresIn,
                ],
            );
        }

        // Fallback: no pages — return refreshed user token
        $profile = $this->getProfile($userToken);

        return new OAuthTokenData(
            access_token: $userToken,
            refresh_token: null,
            expires_in: $userTokenExpiresIn,
            platform_account_id: $profile['id'] ?? '',
            account_name: $profile['name'] ?? 'Facebook Account',
            account_username: null,
            profile_image_url: $profile['picture']['data']['url'] ?? null,
            metadata: [
                'page_id' => null,
                'user_token' => $userToken,
                'user_token_expires_in' => $userTokenExpiresIn,
            ],
        );
    }

    public function revokeToken(string $accessToken): void
    {
        try {
            $this->client->delete($this->graphBase . '/me/permissions', [
                'query' => ['access_token' => $accessToken],
            ]);
        } catch (GuzzleException) {
            // Best-effort revocation
        }
    }

    public function publishPost(PostTarget $target, Post $post, Collection $media): PublishResult
    {
        try {
            $account = $target->socialAccount;
            $pageId = $account->getMetadata('page_id') ?? $account->platform_account_id;
            $content = $target->getContent();

            $params = [
                'message' => $content,
                'access_token' => $account->accessToken,
            ];

            // Add link if present
            if ($post->link_url) {
                $params['link'] = $post->link_url;
            }

            // Handle media
            if ($media->isNotEmpty()) {
                $firstMedia = $media->first();
                if (str_starts_with($firstMedia->mime_type ?? '', 'image/')) {
                    $params['url'] = $firstMedia->getUrl();
                    $endpoint = $this->graphBase . "/{$pageId}/photos";
                } elseif (str_starts_with($firstMedia->mime_type ?? '', 'video/')) {
                    // Facebook video upload via /{pageId}/videos
                    $params['file_url'] = $firstMedia->getUrl();
                    $params['description'] = $params['message'];
                    unset($params['message']);
                    $endpoint = $this->graphBase . "/{$pageId}/videos";
                } else {
                    $endpoint = $this->graphBase . "/{$pageId}/feed";
                }
            } else {
                $endpoint = $this->graphBase . "/{$pageId}/feed";
            }

            $response = $this->client->post($endpoint, [
                'form_params' => $params,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $postId = $data['id'] ?? $data['post_id'] ?? '';

            // Build appropriate URL based on content type
            $externalUrl = str_contains($endpoint, '/videos')
                ? "https://www.facebook.com/{$pageId}/videos/{$postId}"
                : "https://www.facebook.com/{$postId}";

            return PublishResult::success(
                externalPostId: $postId,
                externalPostUrl: $externalUrl,
            );
        } catch (GuzzleException $e) {
            $body = $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : null;

            return PublishResult::failure(
                errorCode: (string) ($body['error']['code'] ?? 'FACEBOOK_PUBLISH_ERROR'),
                errorMessage: $body['error']['message'] ?? $e->getMessage(),
            );
        }
    }

    public function fetchInboxItems(SocialAccount $account, ?\DateTimeInterface $since = null): array
    {
        try {
            $pageId = $account->getMetadata('page_id') ?? $account->platform_account_id;

            $params = [
                'fields' => 'id,message,from{name,id,picture},created_time,comments{id,message,from{name,id,picture},created_time}',
                'limit' => 50,
                'access_token' => $account->accessToken,
            ];

            if ($since) {
                $params['since'] = $since->getTimestamp();
            }

            $response = $this->client->get($this->graphBase . "/{$pageId}/feed", [
                'query' => $params,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $items = [];

            foreach ($data['data'] ?? [] as $feedPost) {
                foreach ($feedPost['comments']['data'] ?? [] as $comment) {
                    $items[] = [
                        'platform_item_id' => $comment['id'],
                        'platform_post_id' => $feedPost['id'],
                        'post_target_id' => null,
                        'type' => InboxItemType::COMMENT,
                        'author_name' => $comment['from']['name'] ?? 'Facebook User',
                        'author_username' => null,
                        'author_profile_url' => "https://facebook.com/{$comment['from']['id']}",
                        'author_avatar_url' => $comment['from']['picture']['data']['url'] ?? null,
                        'content_text' => $comment['message'] ?? '',
                        'platform_created_at' => $comment['created_time'],
                        'metadata' => ['post_id' => $feedPost['id']],
                    ];
                }
            }

            return $items;
        } catch (GuzzleException) {
            return [];
        }
    }

    public function fetchPostMetrics(SocialAccount $account, string $externalPostId): array
    {
        try {
            $response = $this->client->get($this->graphBase . "/{$externalPostId}/insights", [
                'query' => [
                    'metric' => 'post_impressions,post_impressions_unique,post_engaged_users,post_clicks',
                    'access_token' => $account->accessToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $metrics = $this->emptyMetrics();

            foreach ($data['data'] ?? [] as $insight) {
                $value = $insight['values'][0]['value'] ?? 0;
                match ($insight['name']) {
                    'post_impressions' => $metrics['impressions'] = $value,
                    'post_impressions_unique' => $metrics['reach'] = $value,
                    'post_engaged_users' => $metrics['engagements'] = $value,
                    'post_clicks' => $metrics['clicks'] = $value,
                    default => null,
                };
            }

            // Fetch reactions/comments/shares separately
            $engResponse = $this->client->get($this->graphBase . "/{$externalPostId}", [
                'query' => [
                    'fields' => 'likes.summary(true),comments.summary(true),shares',
                    'access_token' => $account->accessToken,
                ],
            ]);

            $engData = json_decode($engResponse->getBody()->getContents(), true);
            $metrics['likes'] = $engData['likes']['summary']['total_count'] ?? 0;
            $metrics['comments'] = $engData['comments']['summary']['total_count'] ?? 0;
            $metrics['shares'] = $engData['shares']['count'] ?? 0;

            return $metrics;
        } catch (GuzzleException) {
            return $this->emptyMetrics();
        }
    }

    public function getProfile(string $accessToken): array
    {
        try {
            $response = $this->client->get($this->graphBase . '/me', [
                'query' => [
                    'fields' => 'id,name,email,picture{url}',
                    'access_token' => $accessToken,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException) {
            return [];
        }
    }

    private function exchangeForLongLivedToken(string $shortLivedToken): array
    {
        $response = $this->client->get($this->graphBase . '/oauth/access_token', [
            'query' => [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->credentials->appId,
                'client_secret' => $this->credentials->appSecret,
                'fb_exchange_token' => $shortLivedToken,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    private function getPages(string $userToken): array
    {
        try {
            $response = $this->client->get($this->graphBase . '/me/accounts', [
                'query' => [
                    'fields' => 'id,name,access_token',
                    'access_token' => $userToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['data'] ?? [];
        } catch (GuzzleException) {
            return [];
        }
    }

    private function emptyMetrics(): array
    {
        return [
            'impressions' => 0,
            'reach' => 0,
            'engagements' => 0,
            'likes' => 0,
            'comments' => 0,
            'shares' => 0,
            'saves' => 0,
            'clicks' => 0,
            'video_views' => 0,
        ];
    }
}
