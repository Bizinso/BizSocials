<?php

declare(strict_types=1);

use App\Enums\Social\SocialPlatform;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\Workspace\Workspace;
use App\Services\Analytics\PlatformAnalyticsFetcher;
use App\Services\Social\FacebookClient;
use App\Services\Social\InstagramClient;
use App\Services\Social\LinkedInClient;
use App\Services\Social\TwitterClient;
use App\Services\Social\YouTubeClient;
use Carbon\Carbon;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $this->facebookClient = Mockery::mock(FacebookClient::class);
    $this->instagramClient = Mockery::mock(InstagramClient::class);
    $this->linkedInClient = Mockery::mock(LinkedInClient::class);
    $this->twitterClient = Mockery::mock(TwitterClient::class);
    $this->youTubeClient = Mockery::mock(YouTubeClient::class);

    $this->fetcher = new PlatformAnalyticsFetcher(
        $this->facebookClient,
        $this->instagramClient,
        $this->linkedInClient,
        $this->twitterClient,
        $this->youTubeClient
    );
});

describe('fetchAnalytics', function () {
    it('fetches Facebook analytics and normalizes data', function () {
        $account = SocialAccount::factory()->facebook()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $startDate = Carbon::yesterday()->startOfDay();
        $endDate = Carbon::yesterday()->endOfDay();

        $facebookInsights = [
            'page_impressions' => 1000,
            'page_reach' => 800,
            'page_post_engagements' => 150,
            'page_likes' => 100,
            'page_comments' => 30,
            'page_shares' => 20,
            'page_clicks' => 50,
            'page_video_views' => 200,
            'page_fans' => 5000,
        ];

        $this->facebookClient
            ->shouldReceive('getPageInsights')
            ->once()
            ->with($account->platform_account_id, $account->access_token, $startDate, $endDate)
            ->andReturn($facebookInsights);

        $result = $this->fetcher->fetchAnalytics($account, $startDate, $endDate);

        expect($result)->toBeArray();
        expect($result['impressions'])->toBe(1000);
        expect($result['reach'])->toBe(800);
        expect($result['engagements'])->toBe(150);
        expect($result['likes'])->toBe(100);
        expect($result['comments'])->toBe(30);
        expect($result['shares'])->toBe(20);
        expect($result['clicks'])->toBe(50);
        expect($result['video_views'])->toBe(200);
        expect($result['saves'])->toBe(0); // Facebook doesn't provide saves
    });

    it('fetches Instagram analytics and normalizes data', function () {
        $account = SocialAccount::factory()->instagram()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $startDate = Carbon::yesterday()->startOfDay();
        $endDate = Carbon::yesterday()->endOfDay();

        $instagramInsights = [
            'impressions' => 2000,
            'reach' => 1500,
            'engagement' => 300,
            'likes' => 200,
            'comments' => 50,
            'shares' => 30,
            'saves' => 20,
            'profile_views' => 100,
            'video_views' => 400,
            'follower_count' => 8000,
        ];

        $this->instagramClient
            ->shouldReceive('getAccountInsights')
            ->once()
            ->with($account->platform_account_id, $account->access_token, $startDate, $endDate)
            ->andReturn($instagramInsights);

        $result = $this->fetcher->fetchAnalytics($account, $startDate, $endDate);

        expect($result)->toBeArray();
        expect($result['impressions'])->toBe(2000);
        expect($result['reach'])->toBe(1500);
        expect($result['engagements'])->toBe(300);
        expect($result['saves'])->toBe(20); // Instagram provides saves
        expect($result['clicks'])->toBe(100); // Mapped from profile_views
    });

    it('fetches LinkedIn analytics and normalizes data', function () {
        $account = SocialAccount::factory()->linkedin()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $startDate = Carbon::yesterday()->startOfDay();
        $endDate = Carbon::yesterday()->endOfDay();

        $linkedInAnalytics = [
            'impressions' => 1500,
            'uniqueImpressions' => 1200,
            'engagement' => 200,
            'likes' => 150,
            'comments' => 30,
            'shares' => 20,
            'clicks' => 80,
            'videoViews' => 300,
            'followerCount' => 6000,
        ];

        $this->linkedInClient
            ->shouldReceive('getAnalytics')
            ->once()
            ->with($account->platform_account_id, $account->access_token, $startDate, $endDate)
            ->andReturn($linkedInAnalytics);

        $result = $this->fetcher->fetchAnalytics($account, $startDate, $endDate);

        expect($result)->toBeArray();
        expect($result['impressions'])->toBe(1500);
        expect($result['reach'])->toBe(1200); // Mapped from uniqueImpressions
        expect($result['engagements'])->toBe(200);
        expect($result['saves'])->toBe(0); // LinkedIn doesn't provide saves
    });

    it('fetches YouTube analytics and normalizes data', function () {
        $account = SocialAccount::factory()->youtube()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $startDate = Carbon::yesterday()->startOfDay();
        $endDate = Carbon::yesterday()->endOfDay();

        $youTubeAnalytics = [
            'impressions' => 3000,
            'views' => 2500,
            'likes' => 250,
            'comments' => 80,
            'shares' => 40,
            'clicks' => 150,
            'subscriberCount' => 10000,
        ];

        $this->youTubeClient
            ->shouldReceive('getChannelAnalytics')
            ->once()
            ->with($account->platform_account_id, $account->access_token, $startDate, $endDate)
            ->andReturn($youTubeAnalytics);

        $result = $this->fetcher->fetchAnalytics($account, $startDate, $endDate);

        expect($result)->toBeArray();
        expect($result['impressions'])->toBe(3000);
        expect($result['reach'])->toBe(2500); // Mapped from views
        expect($result['engagements'])->toBe(330); // likes + comments
        expect($result['video_views'])->toBe(2500); // Same as views
    });

    it('returns empty analytics for Twitter', function () {
        $account = SocialAccount::factory()->twitter()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $startDate = Carbon::yesterday()->startOfDay();
        $endDate = Carbon::yesterday()->endOfDay();

        $result = $this->fetcher->fetchAnalytics($account, $startDate, $endDate);

        expect($result)->toBeArray();
        expect($result['impressions'])->toBe(0);
        expect($result['reach'])->toBe(0);
        expect($result['engagements'])->toBe(0);
    });

    it('returns empty analytics on exception', function () {
        $account = SocialAccount::factory()->facebook()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $startDate = Carbon::yesterday()->startOfDay();
        $endDate = Carbon::yesterday()->endOfDay();

        $this->facebookClient
            ->shouldReceive('getPageInsights')
            ->once()
            ->andThrow(new \Exception('API error'));

        $result = $this->fetcher->fetchAnalytics($account, $startDate, $endDate);

        expect($result)->toBeArray();
        expect($result['impressions'])->toBe(0);
        expect($result['reach'])->toBe(0);
    });
});

describe('fetchFollowerCount', function () {
    it('fetches Facebook follower count', function () {
        $account = SocialAccount::factory()->facebook()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $facebookInsights = [
            'page_fans' => 5000,
        ];

        $this->facebookClient
            ->shouldReceive('getPageInsights')
            ->once()
            ->andReturn($facebookInsights);

        $result = $this->fetcher->fetchFollowerCount($account);

        expect($result)->toBe(5000);
    });

    it('fetches Instagram follower count', function () {
        $account = SocialAccount::factory()->instagram()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $instagramInsights = [
            'follower_count' => 8000,
        ];

        $this->instagramClient
            ->shouldReceive('getAccountInsights')
            ->once()
            ->andReturn($instagramInsights);

        $result = $this->fetcher->fetchFollowerCount($account);

        expect($result)->toBe(8000);
    });

    it('fetches LinkedIn follower count', function () {
        $account = SocialAccount::factory()->linkedin()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $linkedInAnalytics = [
            'followerCount' => 6000,
        ];

        $this->linkedInClient
            ->shouldReceive('getAnalytics')
            ->once()
            ->andReturn($linkedInAnalytics);

        $result = $this->fetcher->fetchFollowerCount($account);

        expect($result)->toBe(6000);
    });

    it('fetches YouTube subscriber count', function () {
        $account = SocialAccount::factory()->youtube()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $youTubeAnalytics = [
            'subscriberCount' => 10000,
        ];

        $this->youTubeClient
            ->shouldReceive('getChannelAnalytics')
            ->once()
            ->andReturn($youTubeAnalytics);

        $result = $this->fetcher->fetchFollowerCount($account);

        expect($result)->toBe(10000);
    });

    it('returns zero for Twitter', function () {
        $account = SocialAccount::factory()->twitter()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $result = $this->fetcher->fetchFollowerCount($account);

        expect($result)->toBe(0);
    });

    it('returns zero on exception', function () {
        $account = SocialAccount::factory()->facebook()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $this->facebookClient
            ->shouldReceive('getPageInsights')
            ->once()
            ->andThrow(new \Exception('API error'));

        $result = $this->fetcher->fetchFollowerCount($account);

        expect($result)->toBe(0);
    });
});

describe('data normalization', function () {
    it('handles missing fields in Facebook insights', function () {
        $account = SocialAccount::factory()->facebook()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $startDate = Carbon::yesterday()->startOfDay();
        $endDate = Carbon::yesterday()->endOfDay();

        // Partial data
        $facebookInsights = [
            'page_impressions' => 1000,
            'page_reach' => 800,
        ];

        $this->facebookClient
            ->shouldReceive('getPageInsights')
            ->once()
            ->andReturn($facebookInsights);

        $result = $this->fetcher->fetchAnalytics($account, $startDate, $endDate);

        expect($result['impressions'])->toBe(1000);
        expect($result['reach'])->toBe(800);
        expect($result['engagements'])->toBe(0); // Missing field defaults to 0
        expect($result['likes'])->toBe(0);
        expect($result['comments'])->toBe(0);
    });

    it('handles missing fields in Instagram insights', function () {
        $account = SocialAccount::factory()->instagram()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $startDate = Carbon::yesterday()->startOfDay();
        $endDate = Carbon::yesterday()->endOfDay();

        // Partial data
        $instagramInsights = [
            'impressions' => 2000,
        ];

        $this->instagramClient
            ->shouldReceive('getAccountInsights')
            ->once()
            ->andReturn($instagramInsights);

        $result = $this->fetcher->fetchAnalytics($account, $startDate, $endDate);

        expect($result['impressions'])->toBe(2000);
        expect($result['reach'])->toBe(0);
        expect($result['saves'])->toBe(0);
    });

    it('casts all values to integers', function () {
        $account = SocialAccount::factory()->facebook()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $startDate = Carbon::yesterday()->startOfDay();
        $endDate = Carbon::yesterday()->endOfDay();

        // String values that should be cast to integers
        $facebookInsights = [
            'page_impressions' => '1000',
            'page_reach' => '800',
            'page_post_engagements' => '150',
        ];

        $this->facebookClient
            ->shouldReceive('getPageInsights')
            ->once()
            ->andReturn($facebookInsights);

        $result = $this->fetcher->fetchAnalytics($account, $startDate, $endDate);

        expect($result['impressions'])->toBeInt()->toBe(1000);
        expect($result['reach'])->toBeInt()->toBe(800);
        expect($result['engagements'])->toBeInt()->toBe(150);
    });
});
