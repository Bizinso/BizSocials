<?php

declare(strict_types=1);

namespace App\Services\Social\Adapters;

use App\Data\Social\OAuthTokenData;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Services\Social\Contracts\PublishResult;
use App\Services\Social\Contracts\SocialPlatformAdapter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;

final class LinkedInAdapter implements SocialPlatformAdapter
{
    private const TOKEN_URL = 'https://www.linkedin.com/oauth/v2/accessToken';
    private const API_BASE = 'https://api.linkedin.com/v2';
    private const REST_BASE = 'https://api.linkedin.com/rest';

    public function __construct(
        private readonly Client $client,
    ) {}

    public function exchangeCode(string $code, string $redirectUri): OAuthTokenData
    {
        $response = $this->client->post(self::TOKEN_URL, [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'client_id' => config('services.linkedin.client_id'),
                'client_secret' => config('services.linkedin.client_secret'),
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        $profile = $this->getProfile($data['access_token']);

        return new OAuthTokenData(
            access_token: $data['access_token'],
            refresh_token: $data['refresh_token'] ?? null,
            expires_in: $data['expires_in'] ?? 5184000,
            platform_account_id: $profile['sub'] ?? $profile['id'] ?? '',
            account_name: $profile['name'] ?? 'LinkedIn Account',
            account_username: null,
            profile_image_url: $profile['picture'] ?? null,
            metadata: ['organization_id' => $profile['organization_id'] ?? null],
        );
    }

    public function refreshToken(string $refreshToken): OAuthTokenData
    {
        $response = $this->client->post(self::TOKEN_URL, [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => config('services.linkedin.client_id'),
                'client_secret' => config('services.linkedin.client_secret'),
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        $profile = $this->getProfile($data['access_token']);

        return new OAuthTokenData(
            access_token: $data['access_token'],
            refresh_token: $data['refresh_token'] ?? $refreshToken,
            expires_in: $data['expires_in'] ?? 5184000,
            platform_account_id: $profile['sub'] ?? $profile['id'] ?? '',
            account_name: $profile['name'] ?? 'LinkedIn Account',
            account_username: null,
            profile_image_url: $profile['picture'] ?? null,
            metadata: null,
        );
    }

    public function revokeToken(string $accessToken): void
    {
        try {
            $this->client->post('https://www.linkedin.com/oauth/v2/revoke', [
                'form_params' => [
                    'client_id' => config('services.linkedin.client_id'),
                    'client_secret' => config('services.linkedin.client_secret'),
                    'token' => $accessToken,
                ],
            ]);
        } catch (GuzzleException) {
            // LinkedIn revocation is best-effort
        }
    }

    public function publishPost(PostTarget $target, Post $post, Collection $media): PublishResult
    {
        try {
            $account = $target->socialAccount;
            $content = $target->getContent();
            $authorUrn = 'urn:li:person:' . $account->platform_account_id;

            // Use organization URN if available
            $orgId = $account->getMetadata('organization_id');
            if ($orgId) {
                $authorUrn = $orgId;
                if (!str_starts_with($authorUrn, 'urn:li:organization:')) {
                    $authorUrn = 'urn:li:organization:' . $authorUrn;
                }
            }

            $postBody = [
                'author' => $authorUrn,
                'lifecycleState' => 'PUBLISHED',
                'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'],
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => ['text' => $content],
                        'shareMediaCategory' => 'NONE',
                    ],
                ],
            ];

            // Add media if present
            if ($media->isNotEmpty()) {
                $mediaEntries = [];
                foreach ($media as $item) {
                    $mediaEntries[] = [
                        'status' => 'READY',
                        'originalUrl' => $item->url,
                        'title' => ['text' => $item->alt_text ?? ''],
                    ];
                }
                $postBody['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'IMAGE';
                $postBody['specificContent']['com.linkedin.ugc.ShareContent']['media'] = $mediaEntries;
            }

            // Add link if present
            if ($post->link_url) {
                $postBody['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'ARTICLE';
                $postBody['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [
                    [
                        'status' => 'READY',
                        'originalUrl' => $post->link_url,
                    ],
                ];
            }

            $response = $this->client->post(self::API_BASE . '/ugcPosts', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $account->accessToken,
                    'Content-Type' => 'application/json',
                    'X-Restli-Protocol-Version' => '2.0.0',
                ],
                'json' => $postBody,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            $postId = $responseData['id'] ?? '';

            return PublishResult::success(
                externalPostId: $postId,
                externalPostUrl: 'https://www.linkedin.com/feed/update/' . $postId,
            );
        } catch (GuzzleException $e) {
            $body = $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : null;

            return PublishResult::failure(
                errorCode: 'LINKEDIN_PUBLISH_ERROR',
                errorMessage: $body['message'] ?? $e->getMessage(),
            );
        }
    }

    public function fetchInboxItems(SocialAccount $account, ?\DateTimeInterface $since = null): array
    {
        try {
            $params = ['count' => 50];

            $response = $this->client->get(self::API_BASE . '/socialActions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $account->accessToken,
                    'X-Restli-Protocol-Version' => '2.0.0',
                ],
                'query' => $params,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $items = [];

            foreach ($data['elements'] ?? [] as $element) {
                $createdAt = isset($element['created']['time'])
                    ? date('Y-m-d H:i:s', (int) ($element['created']['time'] / 1000))
                    : now()->toDateTimeString();

                if ($since && strtotime($createdAt) < $since->getTimestamp()) {
                    continue;
                }

                $items[] = [
                    'platform_item_id' => $element['$URN'] ?? $element['id'] ?? uniqid('li_'),
                    'platform_post_id' => $element['target'] ?? null,
                    'post_target_id' => null,
                    'type' => \App\Enums\Inbox\InboxItemType::COMMENT,
                    'author_name' => $element['actor'] ?? 'LinkedIn User',
                    'author_username' => null,
                    'author_profile_url' => null,
                    'author_avatar_url' => null,
                    'content_text' => $element['commentary'] ?? $element['text'] ?? '',
                    'platform_created_at' => $createdAt,
                    'metadata' => ['raw' => $element],
                ];
            }

            return $items;
        } catch (GuzzleException) {
            return [];
        }
    }

    public function fetchPostMetrics(SocialAccount $account, string $externalPostId): array
    {
        try {
            $encodedId = urlencode($externalPostId);

            $response = $this->client->get(self::API_BASE . "/organizationalEntityShareStatistics?q=organizationalEntity&shares=List({$encodedId})", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $account->accessToken,
                    'X-Restli-Protocol-Version' => '2.0.0',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $stats = $data['elements'][0]['totalShareStatistics'] ?? [];

            return [
                'impressions' => $stats['impressionCount'] ?? 0,
                'reach' => $stats['uniqueImpressionsCount'] ?? 0,
                'engagements' => $stats['clickCount'] ?? 0,
                'likes' => $stats['likeCount'] ?? 0,
                'comments' => $stats['commentCount'] ?? 0,
                'shares' => $stats['shareCount'] ?? 0,
                'saves' => 0,
                'clicks' => $stats['clickCount'] ?? 0,
                'video_views' => 0,
            ];
        } catch (GuzzleException) {
            return $this->emptyMetrics();
        }
    }

    public function getProfile(string $accessToken): array
    {
        try {
            $response = $this->client->get('https://api.linkedin.com/v2/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
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
