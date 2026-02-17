<?php

declare(strict_types=1);

namespace App\Services\Social;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * YouTube Data API v3 Client
 *
 * Provides methods for interacting with YouTube API including:
 * - Video upload with metadata
 * - Playlist management
 * - Analytics fetching
 * - Channel management
 */
final class YouTubeClient
{
    private const API_BASE = 'https://www.googleapis.com/youtube/v3';
    private const UPLOAD_BASE = 'https://www.googleapis.com/upload/youtube/v3';
    private const RATE_LIMIT_KEY = 'youtube_api_rate_limit';
    private const MAX_REQUESTS_PER_HOUR = 100;

    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Upload a video to YouTube with metadata
     *
     * @param string $accessToken Access token
     * @param string $videoFilePath Path to video file or video URL
     * @param array<string, mixed> $metadata Video metadata (title, description, tags, etc.)
     * @return array{success: bool, video_id?: string, video_url?: string, error?: string}
     */
    public function uploadVideo(
        string $accessToken,
        string $videoFilePath,
        array $metadata = []
    ): array {
        if (!$this->checkRateLimit('upload')) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $videoMetadata = [
                'snippet' => [
                    'title' => $metadata['title'] ?? 'Untitled Video',
                    'description' => $metadata['description'] ?? '',
                    'tags' => $metadata['tags'] ?? [],
                    'categoryId' => $metadata['category_id'] ?? '22', // People & Blogs
                ],
                'status' => [
                    'privacyStatus' => $metadata['privacy'] ?? 'public',
                    'selfDeclaredMadeForKids' => $metadata['made_for_kids'] ?? false,
                ],
            ];

            // Add playlist if specified
            if (isset($metadata['playlist_id'])) {
                $videoMetadata['status']['playlistId'] = $metadata['playlist_id'];
            }

            // Initialize resumable upload
            $response = $this->client->post(self::UPLOAD_BASE . '/videos', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                    'X-Upload-Content-Type' => $metadata['mime_type'] ?? 'video/*',
                ],
                'query' => [
                    'part' => 'snippet,status',
                    'uploadType' => 'resumable',
                ],
                'json' => $videoMetadata,
            ]);

            $uploadUrl = $response->getHeader('Location')[0] ?? null;

            if (!$uploadUrl) {
                return [
                    'success' => false,
                    'error' => 'Failed to initialize video upload',
                ];
            }

            // For actual implementation, we would upload the video file here
            // This is a simplified version that returns the upload URL
            $this->logApiCall('uploadVideo', 'new', true);

            return [
                'success' => true,
                'upload_url' => $uploadUrl,
                'message' => 'Video upload initialized. Use the upload_url to complete the upload.',
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('uploadVideo', 'new', false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Update video metadata
     *
     * @param string $accessToken Access token
     * @param string $videoId YouTube video ID
     * @param array<string, mixed> $metadata Updated metadata
     * @return array{success: bool, error?: string}
     */
    public function updateVideo(
        string $accessToken,
        string $videoId,
        array $metadata
    ): array {
        if (!$this->checkRateLimit($videoId)) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $updateData = [
                'id' => $videoId,
            ];

            if (isset($metadata['title']) || isset($metadata['description']) || isset($metadata['tags'])) {
                $updateData['snippet'] = [];
                if (isset($metadata['title'])) {
                    $updateData['snippet']['title'] = $metadata['title'];
                }
                if (isset($metadata['description'])) {
                    $updateData['snippet']['description'] = $metadata['description'];
                }
                if (isset($metadata['tags'])) {
                    $updateData['snippet']['tags'] = $metadata['tags'];
                }
                if (isset($metadata['category_id'])) {
                    $updateData['snippet']['categoryId'] = $metadata['category_id'];
                }
            }

            if (isset($metadata['privacy'])) {
                $updateData['status'] = [
                    'privacyStatus' => $metadata['privacy'],
                ];
            }

            $parts = [];
            if (isset($updateData['snippet'])) {
                $parts[] = 'snippet';
            }
            if (isset($updateData['status'])) {
                $parts[] = 'status';
            }

            $response = $this->client->put(self::API_BASE . '/videos', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'part' => implode(',', $parts),
                ],
                'json' => $updateData,
            ]);

            $this->logApiCall('updateVideo', $videoId, true);

            return ['success' => true];
        } catch (GuzzleException $e) {
            $this->logApiCall('updateVideo', $videoId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Delete a video
     *
     * @param string $accessToken Access token
     * @param string $videoId YouTube video ID
     * @return array{success: bool, error?: string}
     */
    public function deleteVideo(string $accessToken, string $videoId): array
    {
        if (!$this->checkRateLimit($videoId)) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $this->client->delete(self::API_BASE . '/videos', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => [
                    'id' => $videoId,
                ],
            ]);

            $this->logApiCall('deleteVideo', $videoId, true);

            return ['success' => true];
        } catch (GuzzleException $e) {
            $this->logApiCall('deleteVideo', $videoId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Get video details
     *
     * @param string $accessToken Access token
     * @param string $videoId YouTube video ID
     * @return array{success: bool, video?: array<string, mixed>, error?: string}
     */
    public function getVideo(string $accessToken, string $videoId): array
    {
        if (!$this->checkRateLimit($videoId)) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $response = $this->client->get(self::API_BASE . '/videos', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => [
                    'part' => 'snippet,contentDetails,statistics,status',
                    'id' => $videoId,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->logApiCall('getVideo', $videoId, true);

            return [
                'success' => true,
                'video' => $data['items'][0] ?? null,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('getVideo', $videoId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * List videos from a channel
     *
     * @param string $accessToken Access token
     * @param string $channelId YouTube channel ID
     * @param array<string, mixed> $options Query options
     * @return array{success: bool, videos?: array<int, array<string, mixed>>, error?: string}
     */
    public function listVideos(
        string $accessToken,
        string $channelId,
        array $options = []
    ): array {
        if (!$this->checkRateLimit($channelId)) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $params = [
                'part' => 'snippet,contentDetails,statistics',
                'channelId' => $channelId,
                'maxResults' => $options['max_results'] ?? 25,
                'order' => $options['order'] ?? 'date',
            ];

            if (isset($options['page_token'])) {
                $params['pageToken'] = $options['page_token'];
            }

            $response = $this->client->get(self::API_BASE . '/search', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => $params,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->logApiCall('listVideos', $channelId, true);

            return [
                'success' => true,
                'videos' => $data['items'] ?? [],
                'next_page_token' => $data['nextPageToken'] ?? null,
                'total_results' => $data['pageInfo']['totalResults'] ?? 0,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('listVideos', $channelId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Create a playlist
     *
     * @param string $accessToken Access token
     * @param string $title Playlist title
     * @param array<string, mixed> $options Additional options
     * @return array{success: bool, playlist_id?: string, error?: string}
     */
    public function createPlaylist(
        string $accessToken,
        string $title,
        array $options = []
    ): array {
        if (!$this->checkRateLimit('playlist')) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $playlistData = [
                'snippet' => [
                    'title' => $title,
                    'description' => $options['description'] ?? '',
                ],
                'status' => [
                    'privacyStatus' => $options['privacy'] ?? 'public',
                ],
            ];

            $response = $this->client->post(self::API_BASE . '/playlists', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'part' => 'snippet,status',
                ],
                'json' => $playlistData,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->logApiCall('createPlaylist', 'new', true);

            return [
                'success' => true,
                'playlist_id' => $data['id'] ?? '',
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('createPlaylist', 'new', false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Add video to playlist
     *
     * @param string $accessToken Access token
     * @param string $playlistId Playlist ID
     * @param string $videoId Video ID
     * @return array{success: bool, error?: string}
     */
    public function addVideoToPlaylist(
        string $accessToken,
        string $playlistId,
        string $videoId
    ): array {
        if (!$this->checkRateLimit($playlistId)) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $itemData = [
                'snippet' => [
                    'playlistId' => $playlistId,
                    'resourceId' => [
                        'kind' => 'youtube#video',
                        'videoId' => $videoId,
                    ],
                ],
            ];

            $this->client->post(self::API_BASE . '/playlistItems', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'part' => 'snippet',
                ],
                'json' => $itemData,
            ]);

            $this->logApiCall('addVideoToPlaylist', $playlistId, true);

            return ['success' => true];
        } catch (GuzzleException $e) {
            $this->logApiCall('addVideoToPlaylist', $playlistId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * List playlists for a channel
     *
     * @param string $accessToken Access token
     * @param string $channelId Channel ID
     * @param array<string, mixed> $options Query options
     * @return array{success: bool, playlists?: array<int, array<string, mixed>>, error?: string}
     */
    public function listPlaylists(
        string $accessToken,
        string $channelId,
        array $options = []
    ): array {
        if (!$this->checkRateLimit($channelId)) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $params = [
                'part' => 'snippet,contentDetails',
                'channelId' => $channelId,
                'maxResults' => $options['max_results'] ?? 25,
            ];

            if (isset($options['page_token'])) {
                $params['pageToken'] = $options['page_token'];
            }

            $response = $this->client->get(self::API_BASE . '/playlists', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => $params,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->logApiCall('listPlaylists', $channelId, true);

            return [
                'success' => true,
                'playlists' => $data['items'] ?? [],
                'next_page_token' => $data['nextPageToken'] ?? null,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('listPlaylists', $channelId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Get analytics for a video
     *
     * @param string $accessToken Access token
     * @param string $videoId Video ID
     * @return array{success: bool, analytics?: array<string, int>, error?: string}
     */
    public function getVideoAnalytics(
        string $accessToken,
        string $videoId
    ): array {
        if (!$this->checkRateLimit($videoId)) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $response = $this->client->get(self::API_BASE . '/videos', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => [
                    'part' => 'statistics',
                    'id' => $videoId,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $stats = $data['items'][0]['statistics'] ?? [];

            $this->logApiCall('getVideoAnalytics', $videoId, true);

            return [
                'success' => true,
                'analytics' => [
                    'views' => (int) ($stats['viewCount'] ?? 0),
                    'likes' => (int) ($stats['likeCount'] ?? 0),
                    'dislikes' => (int) ($stats['dislikeCount'] ?? 0),
                    'comments' => (int) ($stats['commentCount'] ?? 0),
                    'favorites' => (int) ($stats['favoriteCount'] ?? 0),
                ],
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('getVideoAnalytics', $videoId, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Get channel analytics
     *
     * @param string $accessToken Access token
     * @param string $channelId Channel ID
     * @return array{success: bool, analytics?: array<string, mixed>, error?: string}
     */
    public function getChannelAnalytics(
        string $channelId,
        string $accessToken,
        ?\Carbon\Carbon $startDate = null,
        ?\Carbon\Carbon $endDate = null
    ): array {
        if (!$this->checkRateLimit($channelId)) {
            return [];
        }

        try {
            // Get channel statistics
            $response = $this->client->get(self::API_BASE . '/channels', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => [
                    'part' => 'statistics',
                    'id' => $channelId,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $stats = $data['items'][0]['statistics'] ?? [];

            $this->logApiCall('getChannelAnalytics', $channelId, true);

            // YouTube Analytics API would be used for detailed metrics with date ranges
            // For now, return basic channel statistics
            return [
                'subscriberCount' => (int) ($stats['subscriberCount'] ?? 0),
                'views' => (int) ($stats['viewCount'] ?? 0),
                'likes' => 0, // Would need YouTube Analytics API
                'comments' => 0, // Would need YouTube Analytics API
                'shares' => 0, // Would need YouTube Analytics API
                'impressions' => 0, // Would need YouTube Analytics API
                'clicks' => 0, // Would need YouTube Analytics API
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('getChannelAnalytics', $channelId, false, $e->getMessage());

            return [];
        }
    }

    /**
     * Get channel information
     *
     * @param string $accessToken Access token
     * @return array{success: bool, channel?: array<string, mixed>, error?: string}
     */
    public function getChannel(string $accessToken): array
    {
        if (!$this->checkRateLimit('channel')) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

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

            $this->logApiCall('getChannel', 'me', true);

            return [
                'success' => true,
                'channel' => $data['items'][0] ?? null,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('getChannel', 'me', false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Check rate limit before making API call
     */
    private function checkRateLimit(string $identifier): bool
    {
        $key = self::RATE_LIMIT_KEY . ':' . $identifier;

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
        Log::channel('social')->info('YouTube API call', [
            'method' => $method,
            'resource_id' => $resourceId,
            'success' => $success,
            'error' => $error,
        ]);
    }
}
