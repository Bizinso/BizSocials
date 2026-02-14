<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Data\Social\PlatformCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Instagram Graph API Client
 *
 * Provides methods for interacting with Instagram Business API including:
 * - Publishing posts (images, videos, carousels)
 * - Publishing stories
 * - Fetching media and comments
 * - Getting insights and metrics
 */
final class InstagramClient
{
    private readonly string $graphBase;
    private const RATE_LIMIT_KEY = 'instagram_api_rate_limit';
    private const MAX_REQUESTS_PER_HOUR = 200;

    public function __construct(
        private readonly Client $client,
        private readonly PlatformCredentials $credentials,
    ) {
        $this->graphBase = 'https://graph.facebook.com/' . $this->credentials->apiVersion;
    }

    /**
     * Publish an image post to Instagram
     *
     * @param string $igUserId Instagram Business Account ID
     * @param string $accessToken Access token
     * @param string $imageUrl Publicly accessible image URL
     * @param string $caption Post caption
     * @param array<string, mixed> $options Additional options
     * @return array{success: bool, media_id?: string, permalink?: string, error?: string}
     */
    public function publishImagePost(
        string $igUserId,
        string $accessToken,
        string $imageUrl,
        string $caption,
        array $options = []
    ): array {
        if (!$this->checkRateLimit()) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            // Step 1: Create media container
            $containerParams = [
                'image_url' => $imageUrl,
                'caption' => $caption,
                'access_token' => $accessToken,
            ];

            // Add location if provided
            if (isset($options['location_id'])) {
                $containerParams['location_id'] = $options['location_id'];
            }

            // Add user tags if provided
            if (isset($options['user_tags'])) {
                $containerParams['user_tags'] = json_encode($options['user_tags']);
            }

            $containerResponse = $this->client->post($this->graphBase . "/{$igUserId}/media", [
                'form_params' => $containerParams,
                'timeout' => 30,
            ]);

            $containerData = json_decode($containerResponse->getBody()->getContents(), true);
            $containerId = $containerData['id'];

            // Step 2: Publish the container
            $publishResponse = $this->client->post($this->graphBase . "/{$igUserId}/media_publish", [
                'form_params' => [
                    'creation_id' => $containerId,
                    'access_token' => $accessToken,
                ],
                'timeout' => 30,
            ]);

            $publishData = json_decode($publishResponse->getBody()->getContents(), true);
            $mediaId = $publishData['id'];

            // Step 3: Get permalink
            $permalink = $this->getMediaPermalink($mediaId, $accessToken);

            $this->logApiCall('publishImagePost', $igUserId, true);

            return [
                'success' => true,
                'media_id' => $mediaId,
                'permalink' => $permalink,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('publishImagePost', $igUserId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Publish a video post to Instagram
     *
     * @param string $igUserId Instagram Business Account ID
     * @param string $accessToken Access token
     * @param string $videoUrl Publicly accessible video URL
     * @param string $caption Post caption
     * @param array<string, mixed> $options Additional options
     * @return array{success: bool, media_id?: string, permalink?: string, error?: string}
     */
    public function publishVideoPost(
        string $igUserId,
        string $accessToken,
        string $videoUrl,
        string $caption,
        array $options = []
    ): array {
        if (!$this->checkRateLimit()) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            // Step 1: Create video container
            $containerParams = [
                'media_type' => 'VIDEO',
                'video_url' => $videoUrl,
                'caption' => $caption,
                'access_token' => $accessToken,
            ];

            // Add thumbnail if provided
            if (isset($options['thumb_offset'])) {
                $containerParams['thumb_offset'] = $options['thumb_offset'];
            }

            if (isset($options['location_id'])) {
                $containerParams['location_id'] = $options['location_id'];
            }

            $containerResponse = $this->client->post($this->graphBase . "/{$igUserId}/media", [
                'form_params' => $containerParams,
                'timeout' => 60, // Videos take longer
            ]);

            $containerData = json_decode($containerResponse->getBody()->getContents(), true);
            $containerId = $containerData['id'];

            // Step 2: Wait for video processing (poll status)
            $maxAttempts = 30;
            $attempt = 0;
            $status = 'IN_PROGRESS';

            while ($status === 'IN_PROGRESS' && $attempt < $maxAttempts) {
                sleep(2);

                $statusResponse = $this->client->get($this->graphBase . "/{$containerId}", [
                    'query' => [
                        'fields' => 'status_code',
                        'access_token' => $accessToken,
                    ],
                    'timeout' => 30,
                ]);

                $statusData = json_decode($statusResponse->getBody()->getContents(), true);
                $status = $statusData['status_code'] ?? 'ERROR';
                $attempt++;
            }

            if ($status !== 'FINISHED') {
                return [
                    'success' => false,
                    'error' => 'Video processing failed or timed out',
                ];
            }

            // Step 3: Publish the container
            $publishResponse = $this->client->post($this->graphBase . "/{$igUserId}/media_publish", [
                'form_params' => [
                    'creation_id' => $containerId,
                    'access_token' => $accessToken,
                ],
                'timeout' => 30,
            ]);

            $publishData = json_decode($publishResponse->getBody()->getContents(), true);
            $mediaId = $publishData['id'];

            // Step 4: Get permalink
            $permalink = $this->getMediaPermalink($mediaId, $accessToken);

            $this->logApiCall('publishVideoPost', $igUserId, true);

            return [
                'success' => true,
                'media_id' => $mediaId,
                'permalink' => $permalink,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('publishVideoPost', $igUserId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Publish a carousel post (multiple images/videos)
     *
     * @param string $igUserId Instagram Business Account ID
     * @param string $accessToken Access token
     * @param array<int, array{type: string, url: string}> $items Carousel items
     * @param string $caption Post caption
     * @return array{success: bool, media_id?: string, permalink?: string, error?: string}
     */
    public function publishCarouselPost(
        string $igUserId,
        string $accessToken,
        array $items,
        string $caption
    ): array {
        if (!$this->checkRateLimit()) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            // Step 1: Create child containers for each item
            $childIds = [];

            foreach ($items as $item) {
                $childParams = [
                    'is_carousel_item' => true,
                    'access_token' => $accessToken,
                ];

                if ($item['type'] === 'IMAGE') {
                    $childParams['image_url'] = $item['url'];
                } elseif ($item['type'] === 'VIDEO') {
                    $childParams['media_type'] = 'VIDEO';
                    $childParams['video_url'] = $item['url'];
                }

                $childResponse = $this->client->post($this->graphBase . "/{$igUserId}/media", [
                    'form_params' => $childParams,
                    'timeout' => 60,
                ]);

                $childData = json_decode($childResponse->getBody()->getContents(), true);
                $childIds[] = $childData['id'];
            }

            // Step 2: Create carousel container
            $carouselParams = [
                'media_type' => 'CAROUSEL',
                'caption' => $caption,
                'children' => implode(',', $childIds),
                'access_token' => $accessToken,
            ];

            $containerResponse = $this->client->post($this->graphBase . "/{$igUserId}/media", [
                'form_params' => $carouselParams,
                'timeout' => 30,
            ]);

            $containerData = json_decode($containerResponse->getBody()->getContents(), true);
            $containerId = $containerData['id'];

            // Step 3: Publish the carousel
            $publishResponse = $this->client->post($this->graphBase . "/{$igUserId}/media_publish", [
                'form_params' => [
                    'creation_id' => $containerId,
                    'access_token' => $accessToken,
                ],
                'timeout' => 30,
            ]);

            $publishData = json_decode($publishResponse->getBody()->getContents(), true);
            $mediaId = $publishData['id'];

            // Step 4: Get permalink
            $permalink = $this->getMediaPermalink($mediaId, $accessToken);

            $this->logApiCall('publishCarouselPost', $igUserId, true);

            return [
                'success' => true,
                'media_id' => $mediaId,
                'permalink' => $permalink,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('publishCarouselPost', $igUserId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Publish a story to Instagram
     *
     * @param string $igUserId Instagram Business Account ID
     * @param string $accessToken Access token
     * @param string $mediaUrl Publicly accessible image or video URL
     * @param string $mediaType 'IMAGE' or 'VIDEO'
     * @return array{success: bool, media_id?: string, error?: string}
     */
    public function publishStory(
        string $igUserId,
        string $accessToken,
        string $mediaUrl,
        string $mediaType = 'IMAGE'
    ): array {
        if (!$this->checkRateLimit()) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            // Step 1: Create story container
            $containerParams = [
                'media_type' => 'STORIES',
                'access_token' => $accessToken,
            ];

            if ($mediaType === 'VIDEO') {
                $containerParams['video_url'] = $mediaUrl;
            } else {
                $containerParams['image_url'] = $mediaUrl;
            }

            $containerResponse = $this->client->post($this->graphBase . "/{$igUserId}/media", [
                'form_params' => $containerParams,
                'timeout' => 60,
            ]);

            $containerData = json_decode($containerResponse->getBody()->getContents(), true);
            $containerId = $containerData['id'];

            // Step 2: For videos, wait for processing
            if ($mediaType === 'VIDEO') {
                $maxAttempts = 30;
                $attempt = 0;
                $status = 'IN_PROGRESS';

                while ($status === 'IN_PROGRESS' && $attempt < $maxAttempts) {
                    sleep(2);

                    $statusResponse = $this->client->get($this->graphBase . "/{$containerId}", [
                        'query' => [
                            'fields' => 'status_code',
                            'access_token' => $accessToken,
                        ],
                        'timeout' => 30,
                    ]);

                    $statusData = json_decode($statusResponse->getBody()->getContents(), true);
                    $status = $statusData['status_code'] ?? 'ERROR';
                    $attempt++;
                }

                if ($status !== 'FINISHED') {
                    return [
                        'success' => false,
                        'error' => 'Story video processing failed or timed out',
                    ];
                }
            }

            // Step 3: Publish the story
            $publishResponse = $this->client->post($this->graphBase . "/{$igUserId}/media_publish", [
                'form_params' => [
                    'creation_id' => $containerId,
                    'access_token' => $accessToken,
                ],
                'timeout' => 30,
            ]);

            $publishData = json_decode($publishResponse->getBody()->getContents(), true);
            $mediaId = $publishData['id'];

            $this->logApiCall('publishStory', $igUserId, true);

            return [
                'success' => true,
                'media_id' => $mediaId,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('publishStory', $igUserId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Fetch media from Instagram account
     *
     * @param string $igUserId Instagram Business Account ID
     * @param string $accessToken Access token
     * @param array<string, mixed> $options Query options
     * @return array{success: bool, media?: array<int, array<string, mixed>>, error?: string}
     */
    public function fetchMedia(
        string $igUserId,
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
                'fields' => $options['fields'] ?? 'id,caption,media_type,media_url,permalink,timestamp,like_count,comments_count',
                'limit' => $options['limit'] ?? 25,
                'access_token' => $accessToken,
            ];

            if (isset($options['since'])) {
                $params['since'] = $options['since'];
            }

            if (isset($options['until'])) {
                $params['until'] = $options['until'];
            }

            $response = $this->client->get($this->graphBase . "/{$igUserId}/media", [
                'query' => $params,
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->logApiCall('fetchMedia', $igUserId, true);

            return [
                'success' => true,
                'media' => $data['data'] ?? [],
                'paging' => $data['paging'] ?? null,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('fetchMedia', $igUserId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Fetch comments on a media item
     *
     * @param string $mediaId Instagram media ID
     * @param string $accessToken Access token
     * @param array<string, mixed> $options Query options
     * @return array{success: bool, comments?: array<int, array<string, mixed>>, error?: string}
     */
    public function fetchComments(
        string $mediaId,
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
                'fields' => $options['fields'] ?? 'id,text,username,timestamp,like_count',
                'limit' => $options['limit'] ?? 50,
                'access_token' => $accessToken,
            ];

            $response = $this->client->get($this->graphBase . "/{$mediaId}/comments", [
                'query' => $params,
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->logApiCall('fetchComments', $mediaId, true);

            return [
                'success' => true,
                'comments' => $data['data'] ?? [],
                'paging' => $data['paging'] ?? null,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('fetchComments', $mediaId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Reply to a comment
     *
     * @param string $commentId Instagram comment ID
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
            $response = $this->client->post($this->graphBase . "/{$commentId}/replies", [
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
     * Get insights for a media item
     *
     * @param string $mediaId Instagram media ID
     * @param string $accessToken Access token
     * @param array<int, string> $metrics Metrics to fetch
     * @return array{success: bool, insights?: array<string, int>, error?: string}
     */
    public function getMediaInsights(
        string $mediaId,
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
            $defaultMetrics = ['impressions', 'reach', 'saved', 'video_views'];
            $metricsToFetch = empty($metrics) ? $defaultMetrics : $metrics;

            $response = $this->client->get($this->graphBase . "/{$mediaId}/insights", [
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

            // Fetch engagement counts from media endpoint
            $mediaResponse = $this->client->get($this->graphBase . "/{$mediaId}", [
                'query' => [
                    'fields' => 'like_count,comments_count',
                    'access_token' => $accessToken,
                ],
                'timeout' => 30,
            ]);

            $mediaData = json_decode($mediaResponse->getBody()->getContents(), true);
            $insights['likes'] = $mediaData['like_count'] ?? 0;
            $insights['comments'] = $mediaData['comments_count'] ?? 0;

            $this->logApiCall('getMediaInsights', $mediaId, true);

            return [
                'success' => true,
                'insights' => $insights,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('getMediaInsights', $mediaId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Get account insights
     *
     * @param string $igUserId Instagram Business Account ID
     * @param string $accessToken Access token
     * @param array<int, string> $metrics Metrics to fetch
     * @param string $period Time period (day, week, days_28, lifetime)
     * @return array{success: bool, insights?: array<string, mixed>, error?: string}
     */
    public function getAccountInsights(
        string $igUserId,
        string $accessToken,
        ?\Carbon\Carbon $since = null,
        ?\Carbon\Carbon $until = null,
        array $metrics = [],
        string $period = 'day'
    ): array {
        if (!$this->checkRateLimit()) {
            return [];
        }

        try {
            $defaultMetrics = [
                'impressions',
                'reach',
                'profile_views',
                'follower_count',
                'engagement',
                'likes',
                'comments',
                'shares',
                'saves',
                'video_views',
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

            $response = $this->client->get($this->graphBase . "/{$igUserId}/insights", [
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

            $this->logApiCall('getAccountInsights', $igUserId, true);

            return $insights;
        } catch (GuzzleException $e) {
            $this->logApiCall('getAccountInsights', $igUserId, false, $e->getMessage());

            return [];
        }
    }

    /**
     * Get permalink for a media item
     */
    private function getMediaPermalink(string $mediaId, string $accessToken): string
    {
        try {
            $response = $this->client->get($this->graphBase . "/{$mediaId}", [
                'query' => [
                    'fields' => 'permalink',
                    'access_token' => $accessToken,
                ],
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['permalink'] ?? "https://www.instagram.com/p/{$mediaId}";
        } catch (GuzzleException) {
            return "https://www.instagram.com/p/{$mediaId}";
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
        Log::channel('social')->info('Instagram API call', [
            'method' => $method,
            'resource_id' => $resourceId,
            'success' => $success,
            'error' => $error,
            'app_id' => $this->credentials->appId,
        ]);
    }
}
