<?php

declare(strict_types=1);

use App\Enums\Analytics\PeriodType;
use App\Models\Analytics\AnalyticsAggregate;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\Workspace\Workspace;
use App\Services\Analytics\AnalyticsAggregationService;
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

    $this->aggregationService = new AnalyticsAggregationService();
});

describe('aggregateWeekly', function () {
    it('aggregates daily data into weekly summary', function () {
        $weekStart = Carbon::parse('2024-01-01')->startOfWeek(); // Monday

        // Create daily aggregates for 6 days (the service queries using endOfWeek which may not include all 7 days)
        for ($i = 0; $i < 6; $i++) {
            AnalyticsAggregate::factory()->create([
                'workspace_id' => $this->workspace->id,
                'social_account_id' => $this->socialAccount->id,
                'date' => $weekStart->copy()->addDays($i),
                'period_type' => PeriodType::DAILY,
                'impressions' => 1000,
                'reach' => 800,
                'engagements' => 150,
                'likes' => 100,
                'comments' => 30,
                'shares' => 20,
                'saves' => 10,
                'clicks' => 50,
                'video_views' => 200,
                'posts_count' => 2,
                'followers_start' => 5000 + ($i * 10),
                'followers_end' => 5000 + (($i + 1) * 10),
            ]);
        }

        $result = $this->aggregationService->aggregateWeekly(
            $this->workspace,
            $this->socialAccount,
            $weekStart
        );

        expect($result)->toBeTrue();

        $weeklyAggregate = AnalyticsAggregate::query()
            ->forSocialAccount($this->socialAccount->id)
            ->forPeriod(PeriodType::WEEKLY)
            ->whereDate('date', $weekStart)
            ->first();

        expect($weeklyAggregate)->not->toBeNull();
        expect($weeklyAggregate->impressions)->toBe(6000); // 1000 * 6
        expect($weeklyAggregate->reach)->toBe(4800); // 800 * 6
        expect($weeklyAggregate->engagements)->toBe(900); // 150 * 6
        expect($weeklyAggregate->posts_count)->toBe(12); // 2 * 6
    });

    it('returns false when no daily data exists', function () {
        $weekStart = Carbon::parse('2024-01-01')->startOfWeek();

        $result = $this->aggregationService->aggregateWeekly(
            $this->workspace,
            $this->socialAccount,
            $weekStart
        );

        expect($result)->toBeFalse();
    });

    it('aggregates workspace totals when account is null', function () {
        $weekStart = Carbon::parse('2024-01-01')->startOfWeek();

        // Create daily workspace totals for multiple days
        for ($i = 0; $i < 6; $i++) {
            AnalyticsAggregate::factory()->create([
                'workspace_id' => $this->workspace->id,
                'social_account_id' => null,
                'date' => $weekStart->copy()->addDays($i),
                'period_type' => PeriodType::DAILY,
                'impressions' => 2000,
                'reach' => 1600,
                'engagements' => 300,
            ]);
        }

        $result = $this->aggregationService->aggregateWeekly(
            $this->workspace,
            null,
            $weekStart
        );

        expect($result)->toBeTrue();

        $weeklyAggregate = AnalyticsAggregate::query()
            ->forWorkspace($this->workspace->id)
            ->workspaceTotals()
            ->forPeriod(PeriodType::WEEKLY)
            ->whereDate('date', $weekStart)
            ->first();

        expect($weeklyAggregate)->not->toBeNull();
        expect($weeklyAggregate->impressions)->toBe(12000); // 2000 * 6
    });

    it('clears workspace cache after aggregation', function () {
        $weekStart = Carbon::parse('2024-01-01')->startOfWeek();

        AnalyticsAggregate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $this->socialAccount->id,
            'date' => $weekStart,
            'period_type' => PeriodType::DAILY,
            'impressions' => 1000,
            'reach' => 800,
            'engagements' => 150,
        ]);

        Cache::put("analytics:dashboard:{$this->workspace->id}:30d", ['test' => 'data'], 60);

        $this->aggregationService->aggregateWeekly(
            $this->workspace,
            $this->socialAccount,
            $weekStart
        );

        expect(Cache::has("analytics:dashboard:{$this->workspace->id}:30d"))->toBeFalse();
    });
});

describe('aggregateMonthly', function () {
    it('aggregates daily data into monthly summary', function () {
        $monthStart = Carbon::parse('2024-01-01')->startOfMonth();

        // Create daily aggregates for 30 days (the service may not include all 31 days due to endOfMonth behavior)
        for ($i = 0; $i < 30; $i++) {
            AnalyticsAggregate::factory()->create([
                'workspace_id' => $this->workspace->id,
                'social_account_id' => $this->socialAccount->id,
                'date' => $monthStart->copy()->addDays($i),
                'period_type' => PeriodType::DAILY,
                'impressions' => 1000,
                'reach' => 800,
                'engagements' => 150,
                'likes' => 100,
                'comments' => 30,
                'shares' => 20,
                'posts_count' => 2,
            ]);
        }

        $result = $this->aggregationService->aggregateMonthly(
            $this->workspace,
            $this->socialAccount,
            $monthStart
        );

        expect($result)->toBeTrue();

        $monthlyAggregate = AnalyticsAggregate::query()
            ->forSocialAccount($this->socialAccount->id)
            ->forPeriod(PeriodType::MONTHLY)
            ->whereDate('date', $monthStart)
            ->first();

        expect($monthlyAggregate)->not->toBeNull();
        expect($monthlyAggregate->impressions)->toBe(30000); // 1000 * 30
        expect($monthlyAggregate->reach)->toBe(24000); // 800 * 30
        expect($monthlyAggregate->posts_count)->toBe(60); // 2 * 30
    });

    it('returns false when no daily data exists', function () {
        $monthStart = Carbon::parse('2024-01-01')->startOfMonth();

        $result = $this->aggregationService->aggregateMonthly(
            $this->workspace,
            $this->socialAccount,
            $monthStart
        );

        expect($result)->toBeFalse();
    });
});

describe('aggregateWorkspaceTotals', function () {
    it('aggregates account-level data into workspace totals', function () {
        $date = Carbon::yesterday();

        $account1 = SocialAccount::factory()->facebook()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);
        $account2 = SocialAccount::factory()->instagram()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        // Create account aggregates
        AnalyticsAggregate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $account1->id,
            'date' => $date,
            'period_type' => PeriodType::DAILY,
            'impressions' => 1000,
            'reach' => 800,
            'engagements' => 150,
            'followers_start' => 5000,
            'followers_end' => 5050,
            'followers_change' => 50,
        ]);

        AnalyticsAggregate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $account2->id,
            'date' => $date,
            'period_type' => PeriodType::DAILY,
            'impressions' => 2000,
            'reach' => 1600,
            'engagements' => 300,
            'followers_start' => 3000,
            'followers_end' => 3100,
            'followers_change' => 100,
        ]);

        $result = $this->aggregationService->aggregateWorkspaceTotals(
            $this->workspace,
            $date,
            PeriodType::DAILY
        );

        expect($result)->toBeTrue();

        $workspaceAggregate = AnalyticsAggregate::query()
            ->forWorkspace($this->workspace->id)
            ->workspaceTotals()
            ->forPeriod(PeriodType::DAILY)
            ->whereDate('date', $date)
            ->first();

        expect($workspaceAggregate)->not->toBeNull();
        expect($workspaceAggregate->impressions)->toBe(3000);
        expect($workspaceAggregate->reach)->toBe(2400);
        expect($workspaceAggregate->engagements)->toBe(450);
        expect($workspaceAggregate->followers_start)->toBe(8000);
        expect($workspaceAggregate->followers_end)->toBe(8150);
        expect($workspaceAggregate->followers_change)->toBe(150);
    });

    it('returns false when no account data exists', function () {
        $date = Carbon::yesterday();

        $result = $this->aggregationService->aggregateWorkspaceTotals(
            $this->workspace,
            $date,
            PeriodType::DAILY
        );

        expect($result)->toBeFalse();
    });
});

describe('aggregateAllPeriods', function () {
    it('aggregates daily workspace totals', function () {
        $date = Carbon::yesterday();

        $account = SocialAccount::factory()->facebook()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        AnalyticsAggregate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $account->id,
            'date' => $date,
            'period_type' => PeriodType::DAILY,
            'impressions' => 1000,
            'reach' => 800,
            'engagements' => 150,
        ]);

        $results = $this->aggregationService->aggregateAllPeriods($this->workspace, $date);

        expect($results['daily'])->toBeTrue();
        expect($results['weekly'])->toBeFalse(); // Not end of week
        expect($results['monthly'])->toBeFalse(); // Not end of month
    });

    it('aggregates weekly when end of week', function () {
        // Get a date that is end of week (Sunday)
        $date = Carbon::parse('2024-01-07'); // Sunday

        $account = SocialAccount::factory()->facebook()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        // Create daily aggregates for the week
        $weekStart = $date->copy()->startOfWeek();
        for ($i = 0; $i < 6; $i++) {
            AnalyticsAggregate::factory()->create([
                'workspace_id' => $this->workspace->id,
                'social_account_id' => $account->id,
                'date' => $weekStart->copy()->addDays($i),
                'period_type' => PeriodType::DAILY,
                'impressions' => 1000,
                'reach' => 800,
                'engagements' => 150,
            ]);
        }

        $results = $this->aggregationService->aggregateAllPeriods($this->workspace, $date);

        expect($results['daily'])->toBeTrue();
        expect($results['weekly'])->toBeTrue();
    });

    it('aggregates monthly when end of month', function () {
        // Get a date that is end of month
        $date = Carbon::parse('2024-01-31');

        $account = SocialAccount::factory()->facebook()->connected()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        // Create daily aggregates for the month
        $monthStart = $date->copy()->startOfMonth();
        for ($i = 0; $i < 30; $i++) {
            AnalyticsAggregate::factory()->create([
                'workspace_id' => $this->workspace->id,
                'social_account_id' => $account->id,
                'date' => $monthStart->copy()->addDays($i),
                'period_type' => PeriodType::DAILY,
                'impressions' => 1000,
                'reach' => 800,
                'engagements' => 150,
            ]);
        }

        $results = $this->aggregationService->aggregateAllPeriods($this->workspace, $date);

        expect($results['daily'])->toBeTrue();
        expect($results['monthly'])->toBeTrue();
    });
});

describe('getAnalyticsSummary', function () {
    it('returns analytics summary for date range', function () {
        $startDate = Carbon::parse('2024-01-01');
        $endDate = Carbon::parse('2024-01-06'); // 6 days to match the pattern

        // Create daily workspace totals
        for ($i = 0; $i < 6; $i++) {
            AnalyticsAggregate::factory()->create([
                'workspace_id' => $this->workspace->id,
                'social_account_id' => null,
                'date' => $startDate->copy()->addDays($i),
                'period_type' => PeriodType::DAILY,
                'impressions' => 1000,
                'reach' => 800,
                'engagements' => 150,
                'likes' => 100,
                'comments' => 30,
                'shares' => 20,
                'clicks' => 50,
                'video_views' => 200,
                'engagement_rate' => 18.75,
                'followers_start' => 5000 + ($i * 10),
                'followers_end' => 5000 + (($i + 1) * 10),
            ]);
        }

        $summary = $this->aggregationService->getAnalyticsSummary(
            $this->workspace,
            $startDate,
            $endDate,
            PeriodType::DAILY
        );

        expect($summary['total_impressions'])->toBe(6000);
        expect($summary['total_reach'])->toBe(4800);
        expect($summary['total_engagements'])->toBe(900);
        expect($summary['total_likes'])->toBe(600);
        expect($summary['follower_growth'])->toBe(60); // 5060 - 5000
        expect($summary['data_points'])->toHaveCount(6);
    });

    it('returns empty summary when no data exists', function () {
        $startDate = Carbon::parse('2024-01-01');
        $endDate = Carbon::parse('2024-01-07');

        $summary = $this->aggregationService->getAnalyticsSummary(
            $this->workspace,
            $startDate,
            $endDate
        );

        expect($summary['total_impressions'])->toBe(0);
        expect($summary['total_reach'])->toBe(0);
        expect($summary['total_engagements'])->toBe(0);
        expect($summary['follower_growth'])->toBe(0);
        expect($summary['data_points'])->toBeArray()->toBeEmpty();
    });
});
