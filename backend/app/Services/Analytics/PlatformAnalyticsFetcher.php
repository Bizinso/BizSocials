<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\Social\SocialPlatform;
use App\Models\Social\SocialAccount;
use App\Services\Social\FacebookClient;
use App\Services\Social\InstagramClient;
use App\Services\Social\LinkedInClient;
use App\Services\Social\TwitterClient;
use App\Services\Social\YouTubeClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Platform Analytics Fetcher Service
 *
 * Fetches analytics data from various social media platforms.
 * Normalizes data across platforms for consistent storage.
 */
class PlatformAnalyticsFetcher
{
    public function __construct(
        private readonly FacebookClient $facebookClient,
        private readonly InstagramClient $instagramClient,
        private readonly LinkedInClient $linkedInClient,
        private readonly TwitterClient $twitterClient,
        private readonly YouTubeClient $youTubeClient,
    ) {}

    /**
     * Fetch analytics for a social account.
     *
     * @param SocialAccount $account
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array<string, mixed> Normalized analytics data
     */
    public function fetchAnalytics(
        SocialAccount $account,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        try {
            return match ($account->platform) {
                SocialPlatform::FACEBOOK => $this->fetchFacebookAnalytics($account, $startDate, $endDate),
                SocialPlatform::INSTAGRAM => $this->fetchInstagramAnalytics($account, $startDate, $endDate),
                SocialPlatform::LINKEDIN => $this->fetchLinkedInAnalytics($account, $startDate, $endDate),
                SocialPlatform::TWITTER => $this->fetchTwitterAnalytics($account, $startDate, $endDate),
                SocialPlatform::YOUTUBE => $this->fetchYouTubeAnalytics($account, $startDate, $endDate),
                default => $this->getEmptyAnalytics(),
            };
        } catch (\Exception $e) {
            Log::error('Failed to fetch analytics for social account', [
                'account_id' => $account->id,
                'platform' => $account->platform->value,
                'error' => $e->getMessage(),
            ]);

            return $this->getEmptyAnalytics();
        }
    }

    /**
     * Fetch Facebook page insights.
     *
     * @param SocialAccount $account
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array<string, mixed>
     */
    private function fetchFacebookAnalytics(
        SocialAccount $account,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $insights = $this->facebookClient->getPageInsights(
            $account->platform_account_id,
            $account->access_token,
            $startDate,
            $endDate
        );

        return $this->normalizeFacebookInsights($insights);
    }

    /**
     * Fetch Instagram insights.
     *
     * @param SocialAccount $account
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array<string, mixed>
     */
    private function fetchInstagramAnalytics(
        SocialAccount $account,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $insights = $this->instagramClient->getAccountInsights(
            $account->platform_account_id,
            $account->access_token,
            $startDate,
            $endDate
        );

        return $this->normalizeInstagramInsights($insights);
    }

    /**
     * Fetch LinkedIn analytics.
     *
     * @param SocialAccount $account
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array<string, mixed>
     */
    private function fetchLinkedInAnalytics(
        SocialAccount $account,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $analytics = $this->linkedInClient->getAnalytics(
            $account->platform_account_id,
            $account->access_token,
            $startDate,
            $endDate
        );

        return $this->normalizeLinkedInAnalytics($analytics);
    }

    /**
     * Fetch Twitter analytics.
     *
     * @param SocialAccount $account
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array<string, mixed>
     */
    private function fetchTwitterAnalytics(
        SocialAccount $account,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        // Twitter API v2 analytics implementation
        // Note: Twitter analytics require elevated access
        return $this->getEmptyAnalytics();
    }

    /**
     * Fetch YouTube analytics.
     *
     * @param SocialAccount $account
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array<string, mixed>
     */
    private function fetchYouTubeAnalytics(
        SocialAccount $account,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $analytics = $this->youTubeClient->getChannelAnalytics(
            $account->platform_account_id,
            $account->access_token,
            $startDate,
            $endDate
        );

        return $this->normalizeYouTubeAnalytics($analytics);
    }

    /**
     * Normalize Facebook insights to common format.
     *
     * @param array<string, mixed> $insights
     * @return array<string, mixed>
     */
    private function normalizeFacebookInsights(array $insights): array
    {
        return [
            'impressions' => (int) ($insights['page_impressions'] ?? 0),
            'reach' => (int) ($insights['page_reach'] ?? 0),
            'engagements' => (int) ($insights['page_post_engagements'] ?? 0),
            'likes' => (int) ($insights['page_likes'] ?? 0),
            'comments' => (int) ($insights['page_comments'] ?? 0),
            'shares' => (int) ($insights['page_shares'] ?? 0),
            'saves' => 0, // Facebook doesn't provide saves metric
            'clicks' => (int) ($insights['page_clicks'] ?? 0),
            'video_views' => (int) ($insights['page_video_views'] ?? 0),
            'followers_count' => (int) ($insights['page_fans'] ?? 0),
        ];
    }

    /**
     * Normalize Instagram insights to common format.
     *
     * @param array<string, mixed> $insights
     * @return array<string, mixed>
     */
    private function normalizeInstagramInsights(array $insights): array
    {
        return [
            'impressions' => (int) ($insights['impressions'] ?? 0),
            'reach' => (int) ($insights['reach'] ?? 0),
            'engagements' => (int) ($insights['engagement'] ?? 0),
            'likes' => (int) ($insights['likes'] ?? 0),
            'comments' => (int) ($insights['comments'] ?? 0),
            'shares' => (int) ($insights['shares'] ?? 0),
            'saves' => (int) ($insights['saves'] ?? 0),
            'clicks' => (int) ($insights['profile_views'] ?? 0),
            'video_views' => (int) ($insights['video_views'] ?? 0),
            'followers_count' => (int) ($insights['follower_count'] ?? 0),
        ];
    }

    /**
     * Normalize LinkedIn analytics to common format.
     *
     * @param array<string, mixed> $analytics
     * @return array<string, mixed>
     */
    private function normalizeLinkedInAnalytics(array $analytics): array
    {
        return [
            'impressions' => (int) ($analytics['impressions'] ?? 0),
            'reach' => (int) ($analytics['uniqueImpressions'] ?? 0),
            'engagements' => (int) ($analytics['engagement'] ?? 0),
            'likes' => (int) ($analytics['likes'] ?? 0),
            'comments' => (int) ($analytics['comments'] ?? 0),
            'shares' => (int) ($analytics['shares'] ?? 0),
            'saves' => 0, // LinkedIn doesn't provide saves metric
            'clicks' => (int) ($analytics['clicks'] ?? 0),
            'video_views' => (int) ($analytics['videoViews'] ?? 0),
            'followers_count' => (int) ($analytics['followerCount'] ?? 0),
        ];
    }

    /**
     * Normalize YouTube analytics to common format.
     *
     * @param array<string, mixed> $analytics
     * @return array<string, mixed>
     */
    private function normalizeYouTubeAnalytics(array $analytics): array
    {
        return [
            'impressions' => (int) ($analytics['impressions'] ?? 0),
            'reach' => (int) ($analytics['views'] ?? 0),
            'engagements' => (int) ($analytics['likes'] ?? 0) + (int) ($analytics['comments'] ?? 0),
            'likes' => (int) ($analytics['likes'] ?? 0),
            'comments' => (int) ($analytics['comments'] ?? 0),
            'shares' => (int) ($analytics['shares'] ?? 0),
            'saves' => 0, // YouTube doesn't provide saves metric
            'clicks' => (int) ($analytics['clicks'] ?? 0),
            'video_views' => (int) ($analytics['views'] ?? 0),
            'followers_count' => (int) ($analytics['subscriberCount'] ?? 0),
        ];
    }

    /**
     * Get empty analytics structure.
     *
     * @return array<string, int>
     */
    private function getEmptyAnalytics(): array
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
            'followers_count' => 0,
        ];
    }

    /**
     * Fetch follower count for a social account.
     *
     * @param SocialAccount $account
     * @return int
     */
    public function fetchFollowerCount(SocialAccount $account): int
    {
        try {
            return match ($account->platform) {
                SocialPlatform::FACEBOOK => $this->fetchFacebookFollowerCount($account),
                SocialPlatform::INSTAGRAM => $this->fetchInstagramFollowerCount($account),
                SocialPlatform::LINKEDIN => $this->fetchLinkedInFollowerCount($account),
                SocialPlatform::TWITTER => $this->fetchTwitterFollowerCount($account),
                SocialPlatform::YOUTUBE => $this->fetchYouTubeFollowerCount($account),
                default => 0,
            };
        } catch (\Exception $e) {
            Log::error('Failed to fetch follower count', [
                'account_id' => $account->id,
                'platform' => $account->platform->value,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Fetch Facebook page follower count.
     */
    private function fetchFacebookFollowerCount(SocialAccount $account): int
    {
        $insights = $this->facebookClient->getPageInsights(
            $account->platform_account_id,
            $account->access_token,
            now()->subDay(),
            now()
        );

        return (int) ($insights['page_fans'] ?? 0);
    }

    /**
     * Fetch Instagram follower count.
     */
    private function fetchInstagramFollowerCount(SocialAccount $account): int
    {
        $insights = $this->instagramClient->getAccountInsights(
            $account->platform_account_id,
            $account->access_token,
            now()->subDay(),
            now()
        );

        return (int) ($insights['follower_count'] ?? 0);
    }

    /**
     * Fetch LinkedIn follower count.
     */
    private function fetchLinkedInFollowerCount(SocialAccount $account): int
    {
        $analytics = $this->linkedInClient->getAnalytics(
            $account->platform_account_id,
            $account->access_token,
            now()->subDay(),
            now()
        );

        return (int) ($analytics['followerCount'] ?? 0);
    }

    /**
     * Fetch Twitter follower count.
     */
    private function fetchTwitterFollowerCount(SocialAccount $account): int
    {
        // Twitter API v2 implementation
        return 0;
    }

    /**
     * Fetch YouTube subscriber count.
     */
    private function fetchYouTubeFollowerCount(SocialAccount $account): int
    {
        $analytics = $this->youTubeClient->getChannelAnalytics(
            $account->platform_account_id,
            $account->access_token,
            now()->subDay(),
            now()
        );

        return (int) ($analytics['subscriberCount'] ?? 0);
    }
}
