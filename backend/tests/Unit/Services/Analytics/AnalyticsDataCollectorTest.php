<?php

declare(strict_types=1);

use App\Enums\Analytics\PeriodType;
use App\Enums\Social\SocialPlatform;
use App\Models\Analytics\AnalyticsAggregate;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\Workspace\Workspace;
use App\Services\Analytics\AnalyticsDataCollector;
use App\Services\Analytics\PlatformAnalyticsFetcher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->socialAccount = SocialAccount::factory()->facebook()->connected()->create([
        'workspace_id' => $this->workspace->id,
    ]);

    $this->platformFetcher = Mockery::mock(PlatformAnalyticsFetcher::class);
    $this->collector = new AnalyticsDataCollector($this->platformFetcher);
});

describe('collectDailyAnalytics', function () {
    it('collects and stores analytics data successfully', function () {
        $date = Carbon::yesterday();
        $analyticsData = [
            'impressions' => 1000,
            'reach' => 800,
            'engagements' => 150,
            'likes' => 100,
            'comments' => 30,
            'shares' => 20,
            'saves' => 10,
            'clicks' => 50,
            'video_views' => 200,
        ];

        $this->platformFetcher
            ->shouldReceive('fetchAnalytics')
            ->once()
            ->with($this->socialAccount, Mockery::type(Carbon::class), Mockery::type(Carbon::class))
            ->andReturn($analyticsData);

        $this->platformFetcher
            ->shouldReceive('fetchFollowerCount')
            ->once()
            ->with($this->socialAccount)
            ->andReturn(5000);

        $result = $this->collector->collectDailyAnalytics($this->socialAccount, $date);

        expect($result)->toBeTrue();

        $aggregate = AnalyticsAggregate::query()
            ->forSocialAccount($this->socialAccount->id)
            ->daily()
            ->whereDate('date', $date)
            ->first();

        expect($aggregate)->not->toBeNull();
        expect($aggregate->impressions)->toBe(1000);
        expect($aggregate->reach)->toBe(800);
        expect($aggregate->engagements)->toBe(150);
        expect($aggregate->followers_end)->toBe(5000);
    });

    it('calculates engagement rate correctly', function () {
        $date = Carbon::yesterday();
        $analyticsData = [
            'impressions' => 1000,
            'reach' => 1000,
            'engagements' => 250,
            'likes' => 200,
            'comments' => 30,
            'shares' => 20,
            'saves' => 0,
            'clicks' => 0,
            'video_views' => 0,
        ];

        $this->platformFetcher
            ->shouldReceive('fetchAnalytics')
            ->andReturn($analyticsData);

        $this->platformFetcher
            ->shouldReceive('fetchFollowerCount')
            ->andReturn(5000);

        $this->collector->collectDailyAnalytics($this->socialAccount, $date);

        $aggregate = AnalyticsAggregate::query()
            ->forSocialAccount($this->socialAccount->id)
            ->daily()
            ->whereDate('date', $date)
            ->first();

        // Engagement rate = (250 / 1000) * 100 = 25%
        expect($aggregate->engagement_rate)->toBe(25.0);
    });

    it('calculates follower change from previous day', function () {
        $yesterday = Carbon::yesterday();
        $today = Carbon::today();

        // Create previous day's aggregate
        AnalyticsAggregate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $this->socialAccount->id,
            'date' => $yesterday->subDay(),
            'period_type' => PeriodType::DAILY,
            'followers_start' => 4800,
            'followers_end' => 4900,
        ]);

        $analyticsData = [
            'impressions' => 1000,
            'reach' => 800,
            'engagements' => 150,
            'likes' => 100,
            'comments' => 30,
            'shares' => 20,
            'saves' => 10,
            'clicks' => 50,
            'video_views' => 200,
        ];

        $this->platformFetcher
            ->shouldReceive('fetchAnalytics')
            ->andReturn($analyticsData);

        $this->platformFetcher
            ->shouldReceive('fetchFollowerCount')
            ->andReturn(5000);

        $this->collector->collectDailyAnalytics($this->socialAccount, $yesterday);

        $aggregate = AnalyticsAggregate::query()
            ->forSocialAccount($this->socialAccount->id)
            ->daily()
            ->whereDate('date', $yesterday)
            ->first();

        expect($aggregate->followers_start)->toBe(4900);
        expect($aggregate->followers_end)->toBe(5000);
        expect($aggregate->followers_change)->toBe(100);
    });

    it('returns false when no analytics data is fetched', function () {
        $date = Carbon::yesterday();

        $this->platformFetcher
            ->shouldReceive('fetchAnalytics')
            ->once()
            ->andReturn([]);

        $result = $this->collector->collectDailyAnalytics($this->socialAccount, $date);

        expect($result)->toBeFalse();
    });

    it('returns false and logs error on exception', function () {
        $date = Carbon::yesterday();

        $this->platformFetcher
            ->shouldReceive('fetchAnalytics')
            ->once()
            ->andThrow(new \Exception('API error'));

        $result = $this->collector->collectDailyAnalytics($this->socialAccount, $date);

        expect($result)->toBeFalse();
    });

    it('clears workspace cache after collection', function () {
        $date = Carbon::yesterday();
        $analyticsData = [
            'impressions' => 1000,
            'reach' => 800,
            'engagements' => 150,
            'likes' => 100,
            'comments' => 30,
            'shares' => 20,
            'saves' => 10,
            'clicks' => 50,
            'video_views' => 200,
        ];

        // Set cache values
        Cache::put("analytics:dashboard:{$this->workspace->id}:30d", ['test' => 'data'], 60);

        $this->platformFetcher
            ->shouldReceive('fetchAnalytics')
            ->andReturn($analyticsData);

        $this->platformFetcher
            ->shouldReceive('fetchFollowerCount')
            ->andReturn(5000);

        $this->collector->collectDailyAnalytics($this->socialAccount, $date);

        // Cache should be cleared
        expect(Cache::has("analytics:dashboard:{$this->workspace->id}:30d"))->toBeFalse();
    });
});

describe('collectWorkspaceAnalytics', function () {
    it('collects analytics for all connected accounts', function () {
        $account1 = SocialAccount::factory()->facebook()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        $account2 = SocialAccount::factory()->instagram()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $date = Carbon::yesterday();
        $analyticsData = [
            'impressions' => 1000,
            'reach' => 800,
            'engagements' => 150,
            'likes' => 100,
            'comments' => 30,
            'shares' => 20,
            'saves' => 10,
            'clicks' => 50,
            'video_views' => 200,
        ];

        $this->platformFetcher
            ->shouldReceive('fetchAnalytics')
            ->twice()
            ->andReturn($analyticsData);

        $this->platformFetcher
            ->shouldReceive('fetchFollowerCount')
            ->twice()
            ->andReturn(5000);

        $result = $this->collector->collectWorkspaceAnalytics($this->workspace, $date);

        expect($result['success'])->toBe(2);
        expect($result['failed'])->toBe(0);
    });

    it('tracks failed collections', function () {
        $account1 = SocialAccount::factory()->facebook()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        $account2 = SocialAccount::factory()->instagram()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $date = Carbon::yesterday();

        $this->platformFetcher
            ->shouldReceive('fetchAnalytics')
            ->twice()
            ->andReturn([]);

        $result = $this->collector->collectWorkspaceAnalytics($this->workspace, $date);

        expect($result['success'])->toBe(0);
        expect($result['failed'])->toBe(2);
    });
});

describe('collectAnalyticsRange', function () {
    it('collects analytics for date range', function () {
        $startDate = Carbon::parse('2024-01-01');
        $endDate = Carbon::parse('2024-01-03');

        $analyticsData = [
            'impressions' => 1000,
            'reach' => 800,
            'engagements' => 150,
            'likes' => 100,
            'comments' => 30,
            'shares' => 20,
            'saves' => 10,
            'clicks' => 50,
            'video_views' => 200,
        ];

        $this->platformFetcher
            ->shouldReceive('fetchAnalytics')
            ->times(3)
            ->andReturn($analyticsData);

        $this->platformFetcher
            ->shouldReceive('fetchFollowerCount')
            ->times(3)
            ->andReturn(5000);

        $collected = $this->collector->collectAnalyticsRange(
            $this->socialAccount,
            $startDate,
            $endDate
        );

        expect($collected)->toBe(3);
    });
});

describe('backfillAnalytics', function () {
    it('backfills missing dates', function () {
        $endDate = Carbon::yesterday();
        $startDate = $endDate->copy()->subDays(4);

        // Create analytics for some dates (leaving gaps)
        AnalyticsAggregate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $this->socialAccount->id,
            'date' => $startDate->copy()->addDay(),
            'period_type' => PeriodType::DAILY,
        ]);

        AnalyticsAggregate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $this->socialAccount->id,
            'date' => $startDate->copy()->addDays(3),
            'period_type' => PeriodType::DAILY,
        ]);

        $analyticsData = [
            'impressions' => 1000,
            'reach' => 800,
            'engagements' => 150,
            'likes' => 100,
            'comments' => 30,
            'shares' => 20,
            'saves' => 10,
            'clicks' => 50,
            'video_views' => 200,
        ];

        $this->platformFetcher
            ->shouldReceive('fetchAnalytics')
            ->times(3) // Should only fetch for missing dates
            ->andReturn($analyticsData);

        $this->platformFetcher
            ->shouldReceive('fetchFollowerCount')
            ->times(3)
            ->andReturn(5000);

        $backfilled = $this->collector->backfillAnalytics($this->socialAccount, 5);

        expect($backfilled)->toBe(3);
    });
});

describe('getLastCollectionDate', function () {
    it('returns last collection date', function () {
        $lastDate = Carbon::parse('2024-01-15');

        AnalyticsAggregate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $this->socialAccount->id,
            'date' => Carbon::parse('2024-01-10'),
            'period_type' => PeriodType::DAILY,
        ]);

        AnalyticsAggregate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $this->socialAccount->id,
            'date' => $lastDate,
            'period_type' => PeriodType::DAILY,
        ]);

        $result = $this->collector->getLastCollectionDate($this->socialAccount);

        expect($result->toDateString())->toBe($lastDate->toDateString());
    });

    it('returns null when no collections exist', function () {
        $result = $this->collector->getLastCollectionDate($this->socialAccount);

        expect($result)->toBeNull();
    });
});

describe('needsCollection', function () {
    it('returns true when no previous collection exists', function () {
        $result = $this->collector->needsCollection($this->socialAccount);

        expect($result)->toBeTrue();
    });

    it('returns true when last collection is old', function () {
        AnalyticsAggregate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $this->socialAccount->id,
            'date' => Carbon::now()->subDays(3),
            'period_type' => PeriodType::DAILY,
        ]);

        $result = $this->collector->needsCollection($this->socialAccount);

        expect($result)->toBeTrue();
    });

    it('returns false when collection is recent', function () {
        AnalyticsAggregate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $this->socialAccount->id,
            'date' => Carbon::yesterday(),
            'period_type' => PeriodType::DAILY,
        ]);

        $result = $this->collector->needsCollection($this->socialAccount);

        expect($result)->toBeFalse();
    });
});
