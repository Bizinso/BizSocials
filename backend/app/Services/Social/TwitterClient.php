<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Data\Social\PlatformCredentials;
use Carbon\Carbon;
use GuzzleHttp\Client;

/**
 * Twitter API Client
 *
 * Implements Twitter API v2 integration for posting tweets and replies.
 */
final class TwitterClient
{
    private const API_BASE = 'https://api.twitter.com/2';

    public function __construct(
        private readonly Client $httpClient,
        private readonly PlatformCredentials $credentials,
    ) {}

    /**
     * Post a tweet (optionally as a reply).
     *
     * @param string $accessToken OAuth 2.0 access token
     * @param string $text Tweet text
     * @param array|null $mediaIds Array of media IDs
     * @param string|null $replyToTweetId Tweet ID to reply to
     * @return array{success: bool, tweet_id?: string, error?: string}
     */
    public function postTweet(
        string $accessToken,
        string $text,
        ?array $mediaIds = null,
        ?string $replyToTweetId = null
    ): array {
        try {
            $payload = ['text' => $text];

            if ($mediaIds) {
                $payload['media'] = ['media_ids' => $mediaIds];
            }

            if ($replyToTweetId) {
                $payload['reply'] = ['in_reply_to_tweet_id' => $replyToTweetId];
            }

            $response = $this->httpClient->post(self::API_BASE . '/tweets', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'tweet_id' => $data['data']['id'] ?? '',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get analytics for a Twitter account.
     *
     * @param string $accountId
     * @param string $accessToken
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array<string, mixed>
     */
    public function getAnalytics(
        string $accountId,
        string $accessToken,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        try {
            // Fetch user's tweets in the date range
            $response = $this->httpClient->get(self::API_BASE . "/users/{$accountId}/tweets", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => [
                    'start_time' => $startDate->toIso8601String(),
                    'end_time' => $endDate->toIso8601String(),
                    'tweet.fields' => 'public_metrics,created_at',
                    'max_results' => 100,
                ],
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $tweets = $data['data'] ?? [];

            // Aggregate metrics
            $totalImpressions = 0;
            $totalLikes = 0;
            $totalRetweets = 0;
            $totalReplies = 0;
            $totalQuotes = 0;

            foreach ($tweets as $tweet) {
                $metrics = $tweet['public_metrics'] ?? [];
                $totalLikes += $metrics['like_count'] ?? 0;
                $totalRetweets += $metrics['retweet_count'] ?? 0;
                $totalReplies += $metrics['reply_count'] ?? 0;
                $totalQuotes += $metrics['quote_count'] ?? 0;
            }

            return [
                'total_tweets' => count($tweets),
                'total_likes' => $totalLikes,
                'total_retweets' => $totalRetweets,
                'total_replies' => $totalReplies,
                'total_quotes' => $totalQuotes,
                'total_engagements' => $totalLikes + $totalRetweets + $totalReplies + $totalQuotes,
                'tweets' => $tweets,
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'total_tweets' => 0,
                'total_likes' => 0,
                'total_retweets' => 0,
                'total_replies' => 0,
                'total_quotes' => 0,
                'total_engagements' => 0,
                'tweets' => [],
            ];
        }
    }

    /**
     * Get follower count for a Twitter account.
     *
     * @param string $accountId
     * @param string $accessToken
     * @return int
     */
    public function getFollowerCount(string $accountId, string $accessToken): int
    {
        try {
            $response = $this->httpClient->get(self::API_BASE . "/users/{$accountId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => [
                    'user.fields' => 'public_metrics',
                ],
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return $data['data']['public_metrics']['followers_count'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get user timeline (recent tweets).
     *
     * @param string $accountId
     * @param string $accessToken
     * @param int $maxResults
     * @return array<string, mixed>
     */
    public function getTimeline(
        string $accountId,
        string $accessToken,
        int $maxResults = 10
    ): array {
        try {
            $response = $this->httpClient->get(self::API_BASE . "/users/{$accountId}/tweets", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => [
                    'tweet.fields' => 'created_at,public_metrics,text',
                    'max_results' => min($maxResults, 100),
                ],
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'tweets' => $data['data'] ?? [],
                'meta' => $data['meta'] ?? [],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tweets' => [],
            ];
        }
    }

    /**
     * Upload media to Twitter.
     *
     * @param string $accessToken
     * @param string $mediaPath Local file path or URL
     * @param string $mediaType Type of media (image, video, gif)
     * @return array{success: bool, media_id?: string, error?: string}
     */
    public function uploadMedia(
        string $accessToken,
        string $mediaPath,
        string $mediaType = 'image'
    ): array {
        try {
            // Download media if it's a URL
            if (filter_var($mediaPath, FILTER_VALIDATE_URL)) {
                $mediaContent = $this->httpClient->get($mediaPath)->getBody()->getContents();
            } else {
                $mediaContent = file_get_contents($mediaPath);
            }

            if ($mediaContent === false) {
                return [
                    'success' => false,
                    'error' => 'Failed to read media file',
                ];
            }

            // Upload to Twitter media endpoint (v1.1 API still used for media upload)
            $response = $this->httpClient->post('https://upload.twitter.com/1.1/media/upload.json', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'multipart' => [
                    [
                        'name' => 'media',
                        'contents' => $mediaContent,
                    ],
                ],
                'timeout' => 60,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'media_id' => $data['media_id_string'] ?? '',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get tweet metrics by ID.
     *
     * @param string $accessToken
     * @param string $tweetId
     * @return array<string, mixed>
     */
    public function getTweetMetrics(string $accessToken, string $tweetId): array
    {
        try {
            $response = $this->httpClient->get(self::API_BASE . "/tweets/{$tweetId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => [
                    'tweet.fields' => 'public_metrics,non_public_metrics,organic_metrics',
                ],
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $public = $data['data']['public_metrics'] ?? [];
            $nonPublic = $data['data']['non_public_metrics'] ?? [];
            $organic = $data['data']['organic_metrics'] ?? [];

            return [
                'success' => true,
                'metrics' => [
                    'impressions' => $nonPublic['impression_count'] ?? $organic['impression_count'] ?? 0,
                    'likes' => $public['like_count'] ?? 0,
                    'retweets' => $public['retweet_count'] ?? 0,
                    'replies' => $public['reply_count'] ?? 0,
                    'quotes' => $public['quote_count'] ?? 0,
                    'bookmarks' => $public['bookmark_count'] ?? 0,
                    'url_clicks' => $nonPublic['url_link_clicks'] ?? 0,
                    'profile_clicks' => $nonPublic['user_profile_clicks'] ?? 0,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'metrics' => [],
            ];
        }
    }
}
