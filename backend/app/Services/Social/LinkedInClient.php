<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Data\Social\PlatformCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * LinkedIn API Client
 *
 * Provides methods for interacting with LinkedIn API including:
 * - Publishing posts to personal profiles and company pages
 * - Fetching posts and engagement metrics
 * - Getting analytics and insights
 * - Managing organization pages
 */
final class LinkedInClient
{
    private const API_BASE = 'https://api.linkedin.com/v2';
    private const REST_BASE = 'https://api.linkedin.com/rest';
    private const RATE_LIMIT_KEY = 'linkedin_api_rate_limit';
    private const MAX_REQUESTS_PER_HOUR = 100;

    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Publish a post to LinkedIn (personal profile or organization page)
     *
     * @param string $accessToken Access token
     * @param string $authorUrn Author URN (person or organization)
     * @param string $text Post content
     * @param array<string, mixed> $options Additional options (media, article link, etc.)
     * @return array{success: bool, post_id?: string, post_url?: string, error?: string}
     */
    public function publishPost(
        string $accessToken,
        string $authorUrn,
        string $text,
        array $options = []
    ): array {
        if (!$this->checkRateLimit($authorUrn)) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $postBody = [
                'author' => $authorUrn,
                'lifecycleState' => 'PUBLISHED',
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $text,
                        ],
                        'shareMediaCategory' => 'NONE',
                    ],
                ],
            ];

            // Add media if present
            if (isset($options['media']) && !empty($options['media'])) {
                $mediaEntries = [];
                foreach ($options['media'] as $mediaItem) {
                    $mediaEntries[] = [
                        'status' => 'READY',
                        'originalUrl' => $mediaItem['url'],
                        'title' => [
                            'text' => $mediaItem['title'] ?? '',
                        ],
                        'description' => [
                            'text' => $mediaItem['description'] ?? '',
                        ],
                    ];
                }
                $postBody['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'IMAGE';
                $postBody['specificContent']['com.linkedin.ugc.ShareContent']['media'] = $mediaEntries;
            }

            // Add article link if present
            if (isset($options['article_url'])) {
                $postBody['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'ARTICLE';
                $postBody['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [
                    [
                        'status' => 'READY',
                        'originalUrl' => $options['article_url'],
                        'title' => [
                            'text' => $options['article_title'] ?? '',
                        ],
                        'description' => [
                            'text' => $options['article_description'] ?? '',
                        ],
                    ],
                ];
            }

            $response = $this->client->post(self::API_BASE . '/ugcPosts', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                    'X-Restli-Protocol-Version' => '2.0.0',
                ],
                'json' => $postBody,
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $postId = $data['id'] ?? '';

            $this->logApiCall('publishPost', $authorUrn, true);

            return [
                'success' => true,
                'post_id' => $postId,
                'post_url' => 'https://www.linkedin.com/feed/update/' . $postId,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('publishPost', $authorUrn, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Fetch posts from a LinkedIn profile or organization
     *
     * @param string $accessToken Access token
     * @param string $authorUrn Author URN (person or organization)
     * @param array<string, mixed> $options Query options (count, start)
     * @return array{success: bool, posts?: array<int, array<string, mixed>>, error?: string}
     */
    public function fetchPosts(
        string $accessToken,
        string $authorUrn,
        array $options = []
    ): array {
        if (!$this->checkRateLimit($authorUrn)) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $params = [
                'q' => 'author',
                'author' => $authorUrn,
                'count' => $options['count'] ?? 50,
                'start' => $options['start'] ?? 0,
            ];

            $response = $this->client->get(self::API_BASE . '/ugcPosts', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'X-Restli-Protocol-Version' => '2.0.0',
                ],
                'query' => $params,
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->logApiCall('fetchPosts', $authorUrn, true);

            return [
                'success' => true,
                'posts' => $data['elements'] ?? [],
                'paging' => $data['paging'] ?? null,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('fetchPosts', $authorUrn, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Get analytics for a specific post
     *
     * @param string $accessToken Access token
     * @param string $postUrn Post URN
     * @return array{success: bool, analytics?: array<string, int>, error?: string}
     */
    public function getPostAnalytics(
        string $accessToken,
        string $postUrn
    ): array {
        if (!$this->checkRateLimit($postUrn)) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $encodedUrn = urlencode($postUrn);

            $response = $this->client->get(
                self::API_BASE . "/organizationalEntityShareStatistics?q=organizationalEntity&shares=List({$encodedUrn})",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'X-Restli-Protocol-Version' => '2.0.0',
                    ],
                    'timeout' => 30,
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            $stats = $data['elements'][0]['totalShareStatistics'] ?? [];

            $this->logApiCall('getPostAnalytics', $postUrn, true);

            return [
                'success' => true,
                'analytics' => [
                    'impressions' => $stats['impressionCount'] ?? 0,
                    'unique_impressions' => $stats['uniqueImpressionsCount'] ?? 0,
                    'clicks' => $stats['clickCount'] ?? 0,
                    'likes' => $stats['likeCount'] ?? 0,
                    'comments' => $stats['commentCount'] ?? 0,
                    'shares' => $stats['shareCount'] ?? 0,
                    'engagement' => $stats['engagement'] ?? 0,
                ],
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('getPostAnalytics', $postUrn, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Get organization page analytics
     *
     * @param string $accessToken Access token
     * @param string $organizationUrn Organization URN
     * @param array<string, mixed> $options Time range and metrics options
     * @return array{success: bool, analytics?: array<string, mixed>, error?: string}
     */
    /**
     * Get analytics for an organization with date range support
     *
     * @param string $organizationUrn Organization URN
     * @param string $accessToken Access token
     * @param \Carbon\Carbon $startDate Start date
     * @param \Carbon\Carbon $endDate End date
     * @return array<string, mixed> Normalized analytics data
     */
    public function getAnalytics(
        string $organizationUrn,
        string $accessToken,
        \Carbon\Carbon $startDate,
        \Carbon\Carbon $endDate
    ): array {
        $timeRange = [
            'timeRange' => [
                'start' => $startDate->timestamp * 1000, // LinkedIn uses milliseconds
                'end' => $endDate->timestamp * 1000,
            ],
        ];

        $analyticsResult = $this->getOrganizationAnalytics($accessToken, $organizationUrn, ['time_range' => $timeRange]);
        $followersResult = $this->getFollowerStatistics($accessToken, $organizationUrn);

        if (!($analyticsResult['success'] ?? false)) {
            return [];
        }

        $analytics = $analyticsResult['analytics'][0] ?? [];
        $totalFollowers = $followersResult['followers']['total'] ?? 0;

        return [
            'impressions' => $analytics['totalShareStatistics']['impressionCount'] ?? 0,
            'uniqueImpressions' => $analytics['totalShareStatistics']['uniqueImpressionsCount'] ?? 0,
            'engagement' => $analytics['totalShareStatistics']['engagement'] ?? 0,
            'likes' => $analytics['totalShareStatistics']['likeCount'] ?? 0,
            'comments' => $analytics['totalShareStatistics']['commentCount'] ?? 0,
            'shares' => $analytics['totalShareStatistics']['shareCount'] ?? 0,
            'clicks' => $analytics['totalShareStatistics']['clickCount'] ?? 0,
            'videoViews' => $analytics['totalShareStatistics']['videoViews'] ?? 0,
            'followerCount' => $totalFollowers,
        ];
    }

    public function getOrganizationAnalytics(
        string $accessToken,
        string $organizationUrn,
        array $options = []
    ): array {
        if (!$this->checkRateLimit($organizationUrn)) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $params = [
                'q' => 'organization',
                'organization' => $organizationUrn,
            ];

            if (isset($options['time_range'])) {
                $params['timeIntervals'] = $options['time_range'];
            }

            $response = $this->client->get(self::API_BASE . '/organizationPageStatistics', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'X-Restli-Protocol-Version' => '2.0.0',
                ],
                'query' => $params,
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->logApiCall('getOrganizationAnalytics', $organizationUrn, true);

            return [
                'success' => true,
                'analytics' => $data['elements'] ?? [],
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('getOrganizationAnalytics', $organizationUrn, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Get follower statistics for an organization
     *
     * @param string $accessToken Access token
     * @param string $organizationUrn Organization URN
     * @return array{success: bool, followers?: array<string, mixed>, error?: string}
     */
    public function getFollowerStatistics(
        string $accessToken,
        string $organizationUrn
    ): array {
        if (!$this->checkRateLimit($organizationUrn)) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $params = [
                'q' => 'organization',
                'organization' => $organizationUrn,
            ];

            $response = $this->client->get(self::API_BASE . '/networkSizes', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'X-Restli-Protocol-Version' => '2.0.0',
                ],
                'query' => $params,
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->logApiCall('getFollowerStatistics', $organizationUrn, true);

            return [
                'success' => true,
                'followers' => [
                    'total' => $data['elements'][0]['firstDegreeSize'] ?? 0,
                ],
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('getFollowerStatistics', $organizationUrn, false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Get user profile information
     *
     * @param string $accessToken Access token
     * @return array{success: bool, profile?: array<string, mixed>, error?: string}
     */
    public function getProfile(string $accessToken): array
    {
        if (!$this->checkRateLimit('profile')) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $response = $this->client->get('https://api.linkedin.com/v2/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $this->logApiCall('getProfile', 'me', true);

            return [
                'success' => true,
                'profile' => [
                    'id' => $data['sub'] ?? '',
                    'name' => $data['name'] ?? '',
                    'email' => $data['email'] ?? '',
                    'picture' => $data['picture'] ?? null,
                    'locale' => $data['locale'] ?? null,
                ],
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('getProfile', 'me', false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Get organizations (company pages) the user can manage
     *
     * @param string $accessToken Access token
     * @return array{success: bool, organizations?: array<int, array<string, mixed>>, error?: string}
     */
    public function getOrganizations(string $accessToken): array
    {
        if (!$this->checkRateLimit('organizations')) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $response = $this->client->get(self::API_BASE . '/organizationAcls', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'X-Restli-Protocol-Version' => '2.0.0',
                ],
                'query' => [
                    'q' => 'roleAssignee',
                    'projection' => '(elements*(organizationalTarget~(localizedName,logoV2)))',
                ],
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $organizations = [];
            foreach ($data['elements'] ?? [] as $element) {
                $org = $element['organizationalTarget~'] ?? [];
                if (!empty($org)) {
                    $organizations[] = [
                        'id' => $element['organizationalTarget'] ?? '',
                        'name' => $org['localizedName'] ?? 'Unknown Organization',
                        'logo' => $org['logoV2']['original'] ?? null,
                    ];
                }
            }

            $this->logApiCall('getOrganizations', 'me', true);

            return [
                'success' => true,
                'organizations' => $organizations,
            ];
        } catch (GuzzleException $e) {
            $this->logApiCall('getOrganizations', 'me', false, $e->getMessage());

            return [
                'success' => false,
                'error' => $this->extractErrorMessage($e),
            ];
        }
    }

    /**
     * Delete a post
     *
     * @param string $accessToken Access token
     * @param string $postUrn Post URN
     * @return array{success: bool, error?: string}
     */
    public function deletePost(string $accessToken, string $postUrn): array
    {
        if (!$this->checkRateLimit($postUrn)) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $this->client->delete(self::API_BASE . '/ugcPosts/' . urlencode($postUrn), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'X-Restli-Protocol-Version' => '2.0.0',
                ],
                'timeout' => 30,
            ]);

            $this->logApiCall('deletePost', $postUrn, true);

            return ['success' => true];
        } catch (GuzzleException $e) {
            $this->logApiCall('deletePost', $postUrn, false, $e->getMessage());

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

            return $body['message'] ?? $body['error'] ?? $e->getMessage();
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
        Log::channel('social')->info('LinkedIn API call', [
            'method' => $method,
            'resource_id' => $resourceId,
            'success' => $success,
            'error' => $error,
        ]);
    }
}
