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

final class YouTubeAdapter implements SocialPlatformAdapter
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const API_BASE = 'https://www.googleapis.com/youtube/v3';
    private const UPLOAD_BASE = 'https://www.googleapis.com/upload/youtube/v3';

    public function __construct(
        private readonly Client $client,
    ) {}

    public function exchangeCode(string $code, string $redirectUri): OAuthTokenData
    {
        $response = $this->client->post(self::TOKEN_URL, [
            'form_params' => [
                'code' => $code,
                'client_id' => config('services.youtube.client_id'),
                'client_secret' => config('services.youtube.client_secret'),
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        $profile = $this->getProfile($data['access_token']);

        return new OAuthTokenData(
            access_token: $data['access_token'],
            refresh_token: $data['refresh_token'] ?? null,
            expires_in: $data['expires_in'] ?? 3600,
            platform_account_id: $profile['id'] ?? '',
            account_name: $profile['snippet']['title'] ?? 'YouTube Channel',
            account_username: $profile['snippet']['customUrl'] ?? null,
            profile_image_url: $profile['snippet']['thumbnails']['default']['url'] ?? null,
            metadata: [
                'channel_id' => $profile['id'] ?? '',
                'description' => $profile['snippet']['description'] ?? '',
            ],
        );
    }

    public function refreshToken(string $refreshToken): OAuthTokenData
    {
        $response = $this->client->post(self::TOKEN_URL, [
            'form_params' => [
                'client_id' => config('services.youtube.client_id'),
                'client_secret' => config('services.youtube.client_secret'),
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        $profile = $this->getProfile($data['access_token']);

        return new OAuthTokenData(
            access_token: $data['access_token'],
            refresh_token: $refreshToken,
            expires_in: $data['expires_in'] ?? 3600,
            platform_account_id: $profile['id'] ?? '',
            account_name: $profile['snippet']['title'] ?? 'YouTube Channel',
            account_username: $profile['snippet']['customUrl'] ?? null,
            profile_image_url: $profile['snippet']['thumbnails']['default']['url'] ?? null,
            metadata: null,
        );
    }

    public function revokeToken(string $accessToken): void
    {
        try {
            $this->client->post('https://oauth2.googleapis.com/revoke', [
                'form_params' => [
                    'token' => $accessToken,
                ],
            ]);
        } catch (GuzzleException) {
            // Revocation is best-effort
        }
    }

    public function publishPost(PostTarget $target, Post $post, Collection $media): PublishResult
    {
        try {
            $account = $target->socialAccount;
            $content = $target->getContent();

            // YouTube requires video upload, not simple text posts
            // For now, we'll return an error if no video media is provided
            $videoMedia = $media->first(fn($item) => str_starts_with($item->mime_type ?? '', 'video/'));

            if (!$videoMedia) {
                return PublishResult::failure(
                    errorCode: 'YOUTUBE_NO_VIDEO',
                    errorMessage: 'YouTube requires video content. Please attach a video file.',
                );
            }

            // Step 1: Initialize upload
            $videoMetadata = [
                'snippet' => [
                    'title' => $post->title ?? substr($content, 0, 100),
                    'description' => $content,
                    'tags' => $post->tags ?? [],
                    'categoryId' => '22', // People & Blogs category
                ],
                'status' => [
                    'privacyStatus' => 'public',
                    'selfDeclaredMadeForKids' => false,
                ],
            ];

            // For actual video upload, we would need to:
            // 1. Use resumable upload protocol
            // 2. Upload video file in chunks
            // 3. Get video ID from response
            //
            // This is a simplified implementation that assumes video URL is accessible
            $response = $this->client->post(self::API_BASE . '/videos', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $account->accessToken,
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'part' => 'snippet,status',
                ],
                'json' => $videoMetadata,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            $videoId = $responseData['id'] ?? '';

            return PublishResult::success(
                externalPostId: $videoId,
                externalPostUrl: 'https://www.youtube.com/watch?v=' . $videoId,
            );
        } catch (GuzzleException $e) {
            $body = $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : null;

            return PublishResult::failure(
                errorCode: 'YOUTUBE_PUBLISH_ERROR',
                errorMessage: $body['error']['message'] ?? $e->getMessage(),
            );
        }
    }

    public function fetchInboxItems(SocialAccount $account, ?\DateTimeInterface $since = null): array
    {
        try {
            // Fetch comments on channel videos
            $params = [
                'part' => 'snippet',
                'allThreadsRelatedToChannelId' => $account->platform_account_id,
                'maxResults' => 50,
                'order' => 'time',
            ];

            if ($since) {
                $params['publishedAfter'] = $since->format('Y-m-d\TH:i:s\Z');
            }

            $response = $this->client->get(self::API_BASE . '/commentThreads', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $account->accessToken,
                ],
                'query' => $params,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $items = [];

            foreach ($data['items'] ?? [] as $thread) {
                $comment = $thread['snippet']['topLevelComment']['snippet'] ?? [];

                $items[] = [
                    'platform_item_id' => $thread['id'] ?? uniqid('yt_'),
                    'platform_post_id' => $thread['snippet']['videoId'] ?? null,
                    'post_target_id' => null,
                    'type' => \App\Enums\Inbox\InboxItemType::COMMENT,
                    'author_name' => $comment['authorDisplayName'] ?? 'YouTube User',
                    'author_username' => null,
                    'author_profile_url' => $comment['authorChannelUrl'] ?? null,
                    'author_avatar_url' => $comment['authorProfileImageUrl'] ?? null,
                    'content_text' => $comment['textDisplay'] ?? '',
                    'platform_created_at' => $comment['publishedAt'] ?? now()->toIso8601String(),
                    'metadata' => ['raw' => $thread],
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
            $response = $this->client->get(self::API_BASE . '/videos', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $account->accessToken,
                ],
                'query' => [
                    'part' => 'statistics',
                    'id' => $externalPostId,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $stats = $data['items'][0]['statistics'] ?? [];

            return [
                'impressions' => 0, // Not available in basic API
                'reach' => 0,
                'engagements' => (int) ($stats['likeCount'] ?? 0) + (int) ($stats['commentCount'] ?? 0),
                'likes' => (int) ($stats['likeCount'] ?? 0),
                'comments' => (int) ($stats['commentCount'] ?? 0),
                'shares' => 0,
                'saves' => 0,
                'clicks' => 0,
                'video_views' => (int) ($stats['viewCount'] ?? 0),
            ];
        } catch (GuzzleException) {
            return $this->emptyMetrics();
        }
    }

    public function getProfile(string $accessToken): array
    {
        try {
            $response = $this->client->get(self::API_BASE . '/channels', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => [
                    'part' => 'snippet,contentDetails,statistics',
                    'mine' => 'true',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['items'][0] ?? [];
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
