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

final class InstagramAdapter implements SocialPlatformAdapter
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
        // Instagram Business uses Facebook OAuth (Meta Graph API)
        $response = $this->client->get($this->graphBase . '/oauth/access_token', [
            'query' => [
                'client_id' => $this->credentials->appId,
                'client_secret' => $this->credentials->appSecret,
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        // Exchange for long-lived token
        $longLived = $this->exchangeForLongLivedToken($data['access_token']);

        // Find Instagram business account connected to Facebook page
        $igAccount = $this->findInstagramAccount($longLived['access_token']);

        return new OAuthTokenData(
            access_token: $longLived['access_token'],
            refresh_token: null,
            expires_in: $longLived['expires_in'] ?? 5184000,
            platform_account_id: $igAccount['id'] ?? '',
            account_name: $igAccount['name'] ?? 'Instagram Account',
            account_username: $igAccount['username'] ?? null,
            profile_image_url: $igAccount['profile_picture_url'] ?? null,
            metadata: [
                'ig_user_id' => $igAccount['id'] ?? null,
                'account_type' => 'BUSINESS',
            ],
        );
    }

    public function refreshToken(string $refreshToken): OAuthTokenData
    {
        // Instagram (via Facebook) uses long-lived token exchange
        $response = $this->client->get($this->graphBase . '/oauth/access_token', [
            'query' => [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->credentials->appId,
                'client_secret' => $this->credentials->appSecret,
                'fb_exchange_token' => $refreshToken,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $igAccount = $this->findInstagramAccount($data['access_token']);

        return new OAuthTokenData(
            access_token: $data['access_token'],
            refresh_token: null,
            expires_in: $data['expires_in'] ?? 5184000,
            platform_account_id: $igAccount['id'] ?? '',
            account_name: $igAccount['name'] ?? 'Instagram Account',
            account_username: $igAccount['username'] ?? null,
            profile_image_url: $igAccount['profile_picture_url'] ?? null,
            metadata: null,
        );
    }

    public function revokeToken(string $accessToken): void
    {
        try {
            $this->client->delete($this->graphBase . '/me/permissions', [
                'query' => ['access_token' => $accessToken],
            ]);
        } catch (GuzzleException) {
            // Best-effort
        }
    }

    public function publishPost(PostTarget $target, Post $post, Collection $media): PublishResult
    {
        try {
            $account = $target->socialAccount;
            $igUserId = $account->getMetadata('ig_user_id') ?? $account->platform_account_id;
            $content = $target->getContent();

            // Step 1: Create media container
            $containerParams = [
                'caption' => $content,
                'access_token' => $account->accessToken,
            ];

            if ($media->isNotEmpty()) {
                $firstMedia = $media->first();
                if (str_starts_with($firstMedia->mime_type ?? '', 'video/')) {
                    $containerParams['media_type'] = 'VIDEO';
                    $containerParams['video_url'] = $firstMedia->getUrl();
                } else {
                    $containerParams['image_url'] = $firstMedia->getUrl();
                }

                // Carousel for multiple images
                if ($media->count() > 1) {
                    $childIds = [];
                    foreach ($media as $item) {
                        $childParams = [
                            'is_carousel_item' => true,
                            'access_token' => $account->accessToken,
                        ];
                        if (str_starts_with($item->mime_type ?? '', 'video/')) {
                            $childParams['media_type'] = 'VIDEO';
                            $childParams['video_url'] = $item->getUrl();
                        } else {
                            $childParams['image_url'] = $item->getUrl();
                        }

                        $childResponse = $this->client->post($this->graphBase . "/{$igUserId}/media", [
                            'form_params' => $childParams,
                        ]);
                        $childData = json_decode($childResponse->getBody()->getContents(), true);
                        $childIds[] = $childData['id'];
                    }

                    $containerParams = [
                        'media_type' => 'CAROUSEL',
                        'caption' => $content,
                        'children' => implode(',', $childIds),
                        'access_token' => $account->accessToken,
                    ];
                }
            }

            $containerResponse = $this->client->post($this->graphBase . "/{$igUserId}/media", [
                'form_params' => $containerParams,
            ]);

            $containerData = json_decode($containerResponse->getBody()->getContents(), true);
            $containerId = $containerData['id'];

            // Step 2: Publish the container
            $publishResponse = $this->client->post($this->graphBase . "/{$igUserId}/media_publish", [
                'form_params' => [
                    'creation_id' => $containerId,
                    'access_token' => $account->accessToken,
                ],
            ]);

            $publishData = json_decode($publishResponse->getBody()->getContents(), true);
            $mediaId = $publishData['id'];

            // Get permalink
            $permalinkResponse = $this->client->get($this->graphBase . "/{$mediaId}", [
                'query' => [
                    'fields' => 'permalink',
                    'access_token' => $account->accessToken,
                ],
            ]);
            $permalinkData = json_decode($permalinkResponse->getBody()->getContents(), true);

            return PublishResult::success(
                externalPostId: $mediaId,
                externalPostUrl: $permalinkData['permalink'] ?? "https://www.instagram.com/p/{$mediaId}",
            );
        } catch (GuzzleException $e) {
            $body = $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : null;

            return PublishResult::failure(
                errorCode: (string) ($body['error']['code'] ?? 'INSTAGRAM_PUBLISH_ERROR'),
                errorMessage: $body['error']['message'] ?? $e->getMessage(),
            );
        }
    }

    public function fetchInboxItems(SocialAccount $account, ?\DateTimeInterface $since = null): array
    {
        try {
            $igUserId = $account->getMetadata('ig_user_id') ?? $account->platform_account_id;

            // Fetch recent media to get comments
            $mediaResponse = $this->client->get($this->graphBase . "/{$igUserId}/media", [
                'query' => [
                    'fields' => 'id,comments{id,text,username,timestamp}',
                    'limit' => 25,
                    'access_token' => $account->accessToken,
                ],
            ]);

            $mediaData = json_decode($mediaResponse->getBody()->getContents(), true);
            $items = [];

            foreach ($mediaData['data'] ?? [] as $media) {
                foreach ($media['comments']['data'] ?? [] as $comment) {
                    $createdAt = $comment['timestamp'] ?? now()->toIso8601String();

                    if ($since && strtotime($createdAt) < $since->getTimestamp()) {
                        continue;
                    }

                    $items[] = [
                        'platform_item_id' => $comment['id'],
                        'platform_post_id' => $media['id'],
                        'post_target_id' => null,
                        'type' => InboxItemType::COMMENT,
                        'author_name' => $comment['username'] ?? 'Instagram User',
                        'author_username' => $comment['username'] ?? null,
                        'author_profile_url' => isset($comment['username'])
                            ? "https://instagram.com/{$comment['username']}"
                            : null,
                        'author_avatar_url' => null,
                        'content_text' => $comment['text'] ?? '',
                        'platform_created_at' => $createdAt,
                        'metadata' => ['media_id' => $media['id']],
                    ];
                }
            }

            // Fetch mentions
            $mentionsResponse = $this->client->get($this->graphBase . "/{$igUserId}/tags", [
                'query' => [
                    'fields' => 'id,caption,username,timestamp,permalink',
                    'limit' => 25,
                    'access_token' => $account->accessToken,
                ],
            ]);

            $mentionsData = json_decode($mentionsResponse->getBody()->getContents(), true);

            foreach ($mentionsData['data'] ?? [] as $mention) {
                $createdAt = $mention['timestamp'] ?? now()->toIso8601String();

                if ($since && strtotime($createdAt) < $since->getTimestamp()) {
                    continue;
                }

                $items[] = [
                    'platform_item_id' => 'mention_' . $mention['id'],
                    'platform_post_id' => $mention['id'],
                    'post_target_id' => null,
                    'type' => InboxItemType::MENTION,
                    'author_name' => $mention['username'] ?? 'Instagram User',
                    'author_username' => $mention['username'] ?? null,
                    'author_profile_url' => isset($mention['username'])
                        ? "https://instagram.com/{$mention['username']}"
                        : null,
                    'author_avatar_url' => null,
                    'content_text' => $mention['caption'] ?? '',
                    'platform_created_at' => $createdAt,
                    'metadata' => ['permalink' => $mention['permalink'] ?? null],
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
            $response = $this->client->get($this->graphBase . "/{$externalPostId}/insights", [
                'query' => [
                    'metric' => 'impressions,reach,saved,video_views',
                    'access_token' => $account->accessToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $metrics = $this->emptyMetrics();

            foreach ($data['data'] ?? [] as $insight) {
                $value = $insight['values'][0]['value'] ?? 0;
                match ($insight['name']) {
                    'impressions' => $metrics['impressions'] = $value,
                    'reach' => $metrics['reach'] = $value,
                    'saved' => $metrics['saves'] = $value,
                    'video_views' => $metrics['video_views'] = $value,
                    default => null,
                };
            }

            // Fetch engagement counts from media endpoint
            $mediaResponse = $this->client->get($this->graphBase . "/{$externalPostId}", [
                'query' => [
                    'fields' => 'like_count,comments_count',
                    'access_token' => $account->accessToken,
                ],
            ]);

            $mediaData = json_decode($mediaResponse->getBody()->getContents(), true);
            $metrics['likes'] = $mediaData['like_count'] ?? 0;
            $metrics['comments'] = $mediaData['comments_count'] ?? 0;

            return $metrics;
        } catch (GuzzleException) {
            return $this->emptyMetrics();
        }
    }

    public function getProfile(string $accessToken): array
    {
        try {
            $igAccount = $this->findInstagramAccount($accessToken);
            return $igAccount ?: [];
        } catch (GuzzleException) {
            return [];
        }
    }

    private function findInstagramAccount(string $accessToken): array
    {
        try {
            // Get pages, then find IG business account linked to a page
            $response = $this->client->get($this->graphBase . '/me/accounts', [
                'query' => [
                    'fields' => 'id,instagram_business_account{id,name,username,profile_picture_url}',
                    'access_token' => $accessToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            foreach ($data['data'] ?? [] as $page) {
                if (isset($page['instagram_business_account'])) {
                    return $page['instagram_business_account'];
                }
            }

            return [];
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
