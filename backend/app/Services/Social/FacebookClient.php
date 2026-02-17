<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Data\Social\PlatformCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Facebook Graph API Client
 *
 * Provides methods for interacting with Facebook Graph API including:
 * - Publishing posts (text, images, videos)
 * - Fetching posts and comments
 * - Getting page insights and metrics
 * - Managing pages and permissions
 */
final class FacebookClient
{
    private readonly string $graphBase;
    private const RATE_LIMIT_KEY = 'facebook_api_rate_limit';
    private const MAX_REQUESTS_PER_HOUR = 200;

    public function __construct(
        private readonly Client $client,
        private readonly PlatformCredentials $credentials,
    ) {
        $this->graphBase = 'https://graph.facebook.com/' . $this->credentials->apiVersion;
    }

    /**
     * Publish a post to a Facebook page
     *
     * @param string $pageId Facebook page ID
     * @param string $accessToken Page access token
     * @param string $message Post content
     * @param array<string, mixed> $options Additional options (link, image_url, video_url, etc.)
     * @return array{success: bool, post_id?: string, error?: string}
     */
    public function publishPost(
        string $pageId,
        string $accessToken,
        string $message,
        array $options = []
    ): array {
        if (!$this->checkRateLimit()) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $params = [
                'message' => $message,
                'access_token' => $accessToken,
            ];

            // Add optional parameters
            if (isset($options['link'])) {
                $params['link'] = $options['link'];
            }

            // Determine endpoint based on media type
            $endpoint = $this->graphBase . "/{$pageId}/feed";

            if (isset($options['image_url'])) {
                $params['url'] = $options['image_url'];
                $endpoint = $this->graphBase . "/{$pageId}/photos";
            } elseif (isset($options['video_url'])) {
                $params['file_url'] = $options['video_url'];
                $params['description'] = $params['message'];
                unset($params['message']);
                $endpoint = $this->graphBase . "/{$pageId}/videos";
            }

            $response = $this->client->post($endpoint, [
                'form_params' => $params,
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->logApiCall('publishPost', $pageId, true);

            return [
                'success' => true,
                'post_id' => $data['id'] ?? $data['post_id'] ?? '',
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('publishPost', $pageId, false, $e->getMessage());

            $errorMessage = $this->extractErrorMessage($e);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        }
    }

    /**
     * Fetch posts from a Facebook page
     *
     * @param string $pageId Facebook page ID
     * @param string $accessToken Page access token
     * @param array<string, mixed> $options Query options (limit, since, until, fields)
     * @return array{success: bool, posts?: array<int, array<string, mixed>>, error?: string}
     */
    public function fetchPosts(
        string $pageId,
        string $accessToken,
        array $options = []
    ): array {
        if (!$this->checkRateLimit()) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $params = [
                'fields' => $options['fields'] ?? 'id,message,created_time,permalink_url,full_picture',
                'limit' => $options['limit'] ?? 25,
                'access_token' => $accessToken,
            ];

            if (isset($options['since'])) {
                $params['since'] = $options['since'];
            }

            if (isset($options['until'])) {
                $params['until'] = $options['until'];
            }

            $response = $this->client->get($this->graphBase . "/{$pageId}/posts", [
                'query' => $params,
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->logApiCall('fetchPosts', $pageId, true);

            return [
                'success' => true,
                'posts' => $data['data'] ?? [],
                'paging' => $data['paging'] ?? null,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('fetchPosts', $pageId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Get insights (metrics) for a Facebook post
     *
     * @param string $postId Facebook post ID
     * @param string $accessToken Access token
     * @param array<int, string> $metrics Metrics to fetch
     * @return array{success: bool, insights?: array<string, int>, error?: string}
     */
    public function getPostInsights(
        string $postId,
        string $accessToken,
        array $metrics = []
    ): array {
        if (!$this->checkRateLimit()) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $defaultMetrics = [
                'post_impressions',
                'post_impressions_unique',
                'post_engaged_users',
                'post_clicks',
            ];

            $metricsToFetch = empty($metrics) ? $defaultMetrics : $metrics;

            $response = $this->client->get($this->graphBase . "/{$postId}/insights", [
                'query' => [
                    'metric' => implode(',', $metricsToFetch),
                    'access_token' => $accessToken,
                ],
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $insights = [];
            foreach ($data['data'] ?? [] as $insight) {
                $insights[$insight['name']] = $insight['values'][0]['value'] ?? 0;
            }

            // Fetch engagement metrics separately
            $engagementResponse = $this->client->get($this->graphBase . "/{$postId}", [
                'query' => [
                    'fields' => 'likes.summary(true),comments.summary(true),shares',
                    'access_token' => $accessToken,
                ],
                'timeout' => 30,
            ]);

            $engagementData = json_decode($engagementResponse->getBody()->getContents(), true);

            $insights['likes'] = $engagementData['likes']['summary']['total_count'] ?? 0;
            $insights['comments'] = $engagementData['comments']['summary']['total_count'] ?? 0;
            $insights['shares'] = $engagementData['shares']['count'] ?? 0;

            $this->logApiCall('getPostInsights', $postId, true);

            return [
                'success' => true,
                'insights' => $insights,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('getPostInsights', $postId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Get page insights (analytics)
     *
     * @param string $pageId Facebook page ID
     * @param string $accessToken Page access token
     * @param array<int, string> $metrics Metrics to fetch
     * @param string $period Time period (day, week, days_28)
     * @return array{success: bool, insights?: array<string, mixed>, error?: string}
     */
    public function getPageInsights(
        string $pageId,
        string $accessToken,
        ?\Carbon\Carbon $since = null,
        ?\Carbon\Carbon $until = null,
        array $metrics = [],
        string $period = 'day'
    ): array {
        if (!$this->checkRateLimit()) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $defaultMetrics = [
                'page_impressions',
                'page_impressions_unique',
                'page_engaged_users',
                'page_post_engagements',
                'page_fans',
                'page_reach',
                'page_likes',
                'page_comments',
                'page_shares',
                'page_clicks',
                'page_video_views',
            ];

            $metricsToFetch = empty($metrics) ? $defaultMetrics : $metrics;

            $query = [
                'metric' => implode(',', $metricsToFetch),
                'period' => $period,
                'access_token' => $accessToken,
            ];

            if ($since !== null) {
                $query['since'] = $since->timestamp;
            }

            if ($until !== null) {
                $query['until'] = $until->timestamp;
            }

            $response = $this->client->get($this->graphBase . "/{$pageId}/insights", [
                'query' => $query,
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $insights = [];
            foreach ($data['data'] ?? [] as $insight) {
                // Get the latest value or sum all values
                $values = $insight['values'] ?? [];
                $latestValue = !empty($values) ? end($values)['value'] ?? 0 : 0;
                
                $insights[$insight['name']] = $latestValue;
            }

            $this->logApiCall('getPageInsights', $pageId, true);

            return $insights;
        } catch (GuzzleException $e) {
            $this->logApiCall('getPageInsights', $pageId, false, $e->getMessage());

            return [];
        }
    }

    /**
     * Fetch comments on a post
     *
     * @param string $postId Facebook post ID
     * @param string $accessToken Access token
     * @param array<string, mixed> $options Query options
     * @return array{success: bool, comments?: array<int, array<string, mixed>>, error?: string}
     */
    public function fetchComments(
        string $postId,
        string $accessToken,
        array $options = []
    ): array {
        if (!$this->checkRateLimit()) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $params = [
                'fields' => $options['fields'] ?? 'id,message,from{name,id,picture},created_time',
                'limit' => $options['limit'] ?? 50,
                'access_token' => $accessToken,
            ];

            $response = $this->client->get($this->graphBase . "/{$postId}/comments", [
                'query' => $params,
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->logApiCall('fetchComments', $postId, true);

            return [
                'success' => true,
                'comments' => $data['data'] ?? [],
                'paging' => $data['paging'] ?? null,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('fetchComments', $postId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Reply to a comment
     *
     * @param string $commentId Facebook comment ID
     * @param string $accessToken Access token
     * @param string $message Reply message
     * @return array{success: bool, comment_id?: string, error?: string}
     */
    public function replyToComment(
        string $commentId,
        string $accessToken,
        string $message
    ): array {
        if (!$this->checkRateLimit()) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $response = $this->client->post($this->graphBase . "/{$commentId}/comments", [
                'form_params' => [
                    'message' => $message,
                    'access_token' => $accessToken,
                ],
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->logApiCall('replyToComment', $commentId, true);

            return [
                'success' => true,
                'comment_id' => $data['id'] ?? '',
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('replyToComment', $commentId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Get pages managed by the user
     *
     * @param string $userToken User access token
     * @return array{success: bool, pages?: array<int, array<string, mixed>>, error?: string}
     */
    public function getPages(string $userToken): array {
        if (!$this->checkRateLimit()) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $response = $this->client->get($this->graphBase . '/me/accounts', [
                'query' => [
                    'fields' => 'id,name,access_token,category,tasks',
                    'access_token' => $userToken,
                ],
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->logApiCall('getPages', 'me', true);

            return [
                'success' => true,
                'pages' => $data['data'] ?? [],
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('getPages', 'me', false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Delete a post
     *
     * @param string $postId Facebook post ID
     * @param string $accessToken Access token
     * @return array{success: bool, error?: string}
     */
    public function deletePost(string $postId, string $accessToken): array
    {
        if (!$this->checkRateLimit()) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $this->client->delete($this->graphBase . "/{$postId}", [
                'query' => [
                    'access_token' => $accessToken,
                ],
                'timeout' => 30,
            ]);

            $this->logApiCall('deletePost', $postId, true);

            return ['success' => true];
        } catch (GuzzleException $e) {
            $this->logApiCall('deletePost', $postId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Check rate limit before making API call
     */
    private function checkRateLimit(): bool
    {
        $key = self::RATE_LIMIT_KEY . ':' . $this->credentials->appId;

        return RateLimiter::attempt(
            $key,
            self::MAX_REQUESTS_PER_HOUR,
            function () {
                return true;
            },
            3600 // 1 hour
        );
    }

    /**
     * Extract error message from Guzzle exception
     */
    private function extractErrorMessage(GuzzleException $e): string
    {
        if (!$e->hasResponse()) {
            return $e->getMessage();
        }

        try {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);

            return $body['error']['message'] ?? $e->getMessage();
        } catch (\Throwable) {
            return $e->getMessage();
        }
    }

    /**
     * Log API call for debugging and monitoring
     */
    private function logApiCall(
        string $method,
        string $resourceId,
        bool $success,
        ?string $error = null
    ): void {
        Log::channel('social')->info('Facebook API call', [
            'method' => $method,
            'resource_id' => $resourceId,
            'success' => $success,
            'error' => $error,
            'app_id' => $this->credentials->appId,
        ]);
    }
}
