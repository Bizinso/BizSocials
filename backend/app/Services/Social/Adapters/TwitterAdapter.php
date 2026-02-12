<?php

declare(strict_types=1);

namespace App\Services\Social\Adapters;

use App\Data\Social\OAuthTokenData;
use App\Enums\Inbox\InboxItemType;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Services\Social\Contracts\PublishResult;
use App\Services\Social\Contracts\SocialPlatformAdapter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;

final class TwitterAdapter implements SocialPlatformAdapter
{
    private const TOKEN_URL = 'https://api.twitter.com/2/oauth2/token';
    private const API_BASE = 'https://api.twitter.com/2';

    public function __construct(
        private readonly Client $client,
    ) {}

    public function exchangeCode(string $code, string $redirectUri): OAuthTokenData
    {
        $response = $this->client->post(self::TOKEN_URL, [
            'auth' => [
                config('services.twitter.client_id'),
                config('services.twitter.client_secret'),
            ],
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'code_verifier' => session('twitter_code_verifier', 'challenge'),
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $profile = $this->getProfile($data['access_token']);

        return new OAuthTokenData(
            access_token: $data['access_token'],
            refresh_token: $data['refresh_token'] ?? null,
            expires_in: $data['expires_in'] ?? 7200,
            platform_account_id: $profile['data']['id'] ?? '',
            account_name: $profile['data']['name'] ?? 'Twitter Account',
            account_username: $profile['data']['username'] ?? null,
            profile_image_url: $profile['data']['profile_image_url'] ?? null,
            metadata: ['user_id' => $profile['data']['id'] ?? null],
        );
    }

    public function refreshToken(string $refreshToken): OAuthTokenData
    {
        $response = $this->client->post(self::TOKEN_URL, [
            'auth' => [
                config('services.twitter.client_id'),
                config('services.twitter.client_secret'),
            ],
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $profile = $this->getProfile($data['access_token']);

        return new OAuthTokenData(
            access_token: $data['access_token'],
            refresh_token: $data['refresh_token'] ?? $refreshToken,
            expires_in: $data['expires_in'] ?? 7200,
            platform_account_id: $profile['data']['id'] ?? '',
            account_name: $profile['data']['name'] ?? 'Twitter Account',
            account_username: $profile['data']['username'] ?? null,
            profile_image_url: $profile['data']['profile_image_url'] ?? null,
            metadata: null,
        );
    }

    public function revokeToken(string $accessToken): void
    {
        try {
            $this->client->post(self::TOKEN_URL . '/revoke', [
                'auth' => [
                    config('services.twitter.client_id'),
                    config('services.twitter.client_secret'),
                ],
                'form_params' => [
                    'token' => $accessToken,
                    'token_type_hint' => 'access_token',
                ],
            ]);
        } catch (GuzzleException) {
            // Best-effort
        }
    }

    public function publishPost(PostTarget $target, Post $post, Collection $media): PublishResult
    {
        try {
            $account = $target->socialAccount;
            $content = $target->getContent();

            $tweetBody = ['text' => $content];

            // Upload media if present
            if ($media->isNotEmpty()) {
                $mediaIds = [];
                foreach ($media as $item) {
                    $mediaId = $this->uploadMedia($account->accessToken, $item->url);
                    if ($mediaId) {
                        $mediaIds[] = $mediaId;
                    }
                }

                if (!empty($mediaIds)) {
                    $tweetBody['media'] = ['media_ids' => $mediaIds];
                }
            }

            $response = $this->client->post(self::API_BASE . '/tweets', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $account->accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $tweetBody,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $tweetId = $data['data']['id'] ?? '';
            $username = $account->account_username ?? '';

            return PublishResult::success(
                externalPostId: $tweetId,
                externalPostUrl: "https://twitter.com/{$username}/status/{$tweetId}",
            );
        } catch (GuzzleException $e) {
            $body = $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : null;

            return PublishResult::failure(
                errorCode: 'TWITTER_PUBLISH_ERROR',
                errorMessage: $body['detail'] ?? $body['title'] ?? $e->getMessage(),
            );
        }
    }

    public function fetchInboxItems(SocialAccount $account, ?\DateTimeInterface $since = null): array
    {
        try {
            $userId = $account->getMetadata('user_id') ?? $account->platform_account_id;

            $params = [
                'tweet.fields' => 'created_at,author_id,text',
                'user.fields' => 'name,username,profile_image_url',
                'expansions' => 'author_id',
                'max_results' => 50,
            ];

            if ($since) {
                $params['start_time'] = $since->format('Y-m-d\TH:i:s\Z');
            }

            $response = $this->client->get(self::API_BASE . "/users/{$userId}/mentions", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $account->accessToken,
                ],
                'query' => $params,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $items = [];

            // Build user lookup map
            $userMap = [];
            foreach ($data['includes']['users'] ?? [] as $user) {
                $userMap[$user['id']] = $user;
            }

            foreach ($data['data'] ?? [] as $tweet) {
                $author = $userMap[$tweet['author_id']] ?? [];

                $items[] = [
                    'platform_item_id' => $tweet['id'],
                    'platform_post_id' => null,
                    'post_target_id' => null,
                    'type' => InboxItemType::MENTION,
                    'author_name' => $author['name'] ?? 'Twitter User',
                    'author_username' => $author['username'] ?? null,
                    'author_profile_url' => isset($author['username'])
                        ? "https://twitter.com/{$author['username']}"
                        : null,
                    'author_avatar_url' => $author['profile_image_url'] ?? null,
                    'content_text' => $tweet['text'] ?? '',
                    'platform_created_at' => $tweet['created_at'] ?? now()->toIso8601String(),
                    'metadata' => ['tweet_id' => $tweet['id']],
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
            $response = $this->client->get(self::API_BASE . "/tweets/{$externalPostId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $account->accessToken,
                ],
                'query' => [
                    'tweet.fields' => 'public_metrics,non_public_metrics,organic_metrics',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $public = $data['data']['public_metrics'] ?? [];
            $nonPublic = $data['data']['non_public_metrics'] ?? [];

            return [
                'impressions' => $nonPublic['impression_count'] ?? $public['impression_count'] ?? 0,
                'reach' => 0,
                'engagements' => 0,
                'likes' => $public['like_count'] ?? 0,
                'comments' => $public['reply_count'] ?? 0,
                'shares' => $public['retweet_count'] ?? 0,
                'saves' => $public['bookmark_count'] ?? 0,
                'clicks' => $nonPublic['url_link_clicks'] ?? 0,
                'video_views' => 0,
            ];
        } catch (GuzzleException) {
            return $this->emptyMetrics();
        }
    }

    public function getProfile(string $accessToken): array
    {
        try {
            $response = $this->client->get(self::API_BASE . '/users/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => [
                    'user.fields' => 'id,name,username,profile_image_url',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException) {
            return [];
        }
    }

    private function uploadMedia(string $accessToken, string $mediaUrl): ?string
    {
        try {
            // Download media content
            $mediaContent = $this->client->get($mediaUrl)->getBody()->getContents();

            // Upload to Twitter media endpoint (v1.1 - still required for media upload)
            $response = $this->client->post('https://upload.twitter.com/1.1/media/upload.json', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'multipart' => [
                    [
                        'name' => 'media_data',
                        'contents' => base64_encode($mediaContent),
                    ],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['media_id_string'] ?? null;
        } catch (GuzzleException) {
            return null;
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
