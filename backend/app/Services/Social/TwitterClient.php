<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Data\Social\PlatformCredentials;
use Carbon\Carbon;
use GuzzleHttp\Client;

/**
 * Twitter API Client (Stub Implementation)
 *
 * This is a stub implementation for Twitter API integration.
 * Full implementation pending Twitter API v2 elevated access.
 */
final class TwitterClient
{
    public function __construct(
        private readonly Client $httpClient,
        private readonly PlatformCredentials $credentials,
    ) {}

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
        // Stub implementation - Twitter API v2 requires elevated access
        return [];
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
        // Stub implementation - Twitter API v2 requires elevated access
        return 0;
    }
}
