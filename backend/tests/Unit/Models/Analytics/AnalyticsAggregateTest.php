<?php

declare(strict_types=1);

/**
 * AnalyticsAggregate Model Unit Tests
 *
 * Tests for the AnalyticsAggregate model which stores aggregated analytics metrics.
 *
 * @see \App\Models\Analytics\AnalyticsAggregate
 */

use App\Enums\Analytics\PeriodType;
use App\Models\Analytics\AnalyticsAggregate;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('has correct table name', function (): void {
    $aggregate = new AnalyticsAggregate();

    expect($aggregate->getTable())->toBe('analytics_aggregates');
});

test('uses uuid primary key', function (): void {
    $aggregate = AnalyticsAggregate::factory()->create();

    expect($aggregate->id)->not->toBeNull()
        ->and(strlen($aggregate->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $aggregate = new AnalyticsAggregate();
    $fillable = $aggregate->getFillable();

    expect($fillable)->toContain('workspace_id')
        ->and($fillable)->toContain('social_account_id')
        ->and($fillable)->toContain('date')
        ->and($fillable)->toContain('period_type')
        ->and($fillable)->toContain('impressions')
        ->and($fillable)->toContain('reach')
        ->and($fillable)->toContain('engagements')
        ->and($fillable)->toContain('likes')
        ->and($fillable)->toContain('comments')
        ->and($fillable)->toContain('shares')
        ->and($fillable)->toContain('engagement_rate')
        ->and($fillable)->toContain('followers_change');
});

test('period_type casts to PeriodType enum', function (): void {
    $aggregate = AnalyticsAggregate::factory()->daily()->create();

    expect($aggregate->period_type)->toBeInstanceOf(PeriodType::class)
        ->and($aggregate->period_type)->toBe(PeriodType::DAILY);
});

test('date casts to date', function (): void {
    $aggregate = AnalyticsAggregate::factory()->create();

    expect($aggregate->date)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('engagement_rate casts to decimal', function (): void {
    $aggregate = AnalyticsAggregate::factory()->create([
        'engagement_rate' => 3.5678,
    ]);

    expect($aggregate->engagement_rate)->toBeFloat();
});

test('workspace relationship returns belongs to', function (): void {
    $aggregate = new AnalyticsAggregate();

    expect($aggregate->workspace())->toBeInstanceOf(BelongsTo::class);
});

test('workspace relationship works correctly', function (): void {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forTenant($user->tenant)->create();
    $aggregate = AnalyticsAggregate::factory()
        ->forWorkspace($workspace)
        ->create();

    expect($aggregate->workspace)->toBeInstanceOf(Workspace::class)
        ->and($aggregate->workspace->id)->toBe($workspace->id);
});

test('socialAccount relationship returns belongs to', function (): void {
    $aggregate = new AnalyticsAggregate();

    expect($aggregate->socialAccount())->toBeInstanceOf(BelongsTo::class);
});

test('socialAccount relationship works correctly', function (): void {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forTenant($user->tenant)->create();
    $account = SocialAccount::factory()->forWorkspace($workspace)->create();
    $aggregate = AnalyticsAggregate::factory()
        ->forWorkspace($workspace)
        ->forSocialAccount($account)
        ->create();

    expect($aggregate->socialAccount)->toBeInstanceOf(SocialAccount::class)
        ->and($aggregate->socialAccount->id)->toBe($account->id);
});

test('socialAccount can be null for workspace-level aggregates', function (): void {
    $aggregate = AnalyticsAggregate::factory()->workspaceTotals()->create();

    expect($aggregate->social_account_id)->toBeNull()
        ->and($aggregate->socialAccount)->toBeNull();
});

describe('scopes', function () {
    test('forWorkspace scope filters by workspace id', function (): void {
        $user = User::factory()->create();
        $workspace1 = Workspace::factory()->forTenant($user->tenant)->create();
        $workspace2 = Workspace::factory()->forTenant($user->tenant)->create();

        AnalyticsAggregate::factory()->count(3)->forWorkspace($workspace1)->create();
        AnalyticsAggregate::factory()->count(2)->forWorkspace($workspace2)->create();

        $results = AnalyticsAggregate::forWorkspace($workspace1->id)->get();

        expect($results)->toHaveCount(3)
            ->and($results->every(fn ($a) => $a->workspace_id === $workspace1->id))->toBeTrue();
    });

    test('forSocialAccount scope filters by social account id', function (): void {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->forTenant($user->tenant)->create();
        $account1 = SocialAccount::factory()->forWorkspace($workspace)->create();
        $account2 = SocialAccount::factory()->forWorkspace($workspace)->create();

        AnalyticsAggregate::factory()->count(3)->forWorkspace($workspace)->forSocialAccount($account1)->create();
        AnalyticsAggregate::factory()->count(2)->forWorkspace($workspace)->forSocialAccount($account2)->create();

        $results = AnalyticsAggregate::forSocialAccount($account1->id)->get();

        expect($results)->toHaveCount(3)
            ->and($results->every(fn ($a) => $a->social_account_id === $account1->id))->toBeTrue();
    });

    test('forPeriod scope filters by period type', function (): void {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->forTenant($user->tenant)->create();

        AnalyticsAggregate::factory()->count(3)->forWorkspace($workspace)->daily()->create();
        AnalyticsAggregate::factory()->count(2)->forWorkspace($workspace)->weekly()->create();

        $results = AnalyticsAggregate::forPeriod(PeriodType::DAILY)->get();

        expect($results)->toHaveCount(3)
            ->and($results->every(fn ($a) => $a->period_type === PeriodType::DAILY))->toBeTrue();
    });

    test('inDateRange scope filters by date range', function (): void {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->forTenant($user->tenant)->create();

        AnalyticsAggregate::factory()->forWorkspace($workspace)->forDate(now()->subDays(5))->create();
        AnalyticsAggregate::factory()->forWorkspace($workspace)->forDate(now()->subDays(15))->create();
        AnalyticsAggregate::factory()->forWorkspace($workspace)->forDate(now()->subDays(25))->create();

        $results = AnalyticsAggregate::inDateRange(now()->subDays(20), now())->get();

        expect($results)->toHaveCount(2);
    });
});

describe('upsertAggregate method', function () {
    test('creates new aggregate when none exists', function (): void {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->forTenant($user->tenant)->create();

        $aggregate = AnalyticsAggregate::upsertAggregate(
            workspaceId: $workspace->id,
            date: now(),
            periodType: PeriodType::DAILY,
            socialAccountId: null,
            metrics: [
                'impressions' => 1000,
                'reach' => 500,
                'engagements' => 100,
            ]
        );

        expect($aggregate)->toBeInstanceOf(AnalyticsAggregate::class)
            ->and($aggregate->impressions)->toBe(1000)
            ->and($aggregate->reach)->toBe(500)
            ->and($aggregate->engagements)->toBe(100);
    });

    test('updates existing aggregate', function (): void {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->forTenant($user->tenant)->create();
        $date = now()->startOfDay();

        // Create initial
        $initial = AnalyticsAggregate::upsertAggregate(
            workspaceId: $workspace->id,
            date: $date,
            periodType: PeriodType::DAILY,
            socialAccountId: null,
            metrics: ['impressions' => 1000]
        );

        // Upsert with new values
        $updated = AnalyticsAggregate::upsertAggregate(
            workspaceId: $workspace->id,
            date: $date,
            periodType: PeriodType::DAILY,
            socialAccountId: null,
            metrics: ['impressions' => 2000]
        );

        expect($updated->id)->toBe($initial->id)
            ->and($updated->impressions)->toBe(2000);
    });
});

describe('helper methods', function () {
    test('getTotalEngagements returns sum of engagement metrics', function (): void {
        $aggregate = AnalyticsAggregate::factory()->create([
            'likes' => 100,
            'comments' => 50,
            'shares' => 25,
            'saves' => 10,
        ]);

        expect($aggregate->getTotalEngagements())->toBe(185);
    });

    test('getFollowerGrowthRate calculates percentage', function (): void {
        $aggregate = AnalyticsAggregate::factory()->create([
            'followers_start' => 1000,
            'followers_end' => 1100,
            'followers_change' => 100,
        ]);

        expect($aggregate->getFollowerGrowthRate())->toBe(10.0);
    });

    test('getFollowerGrowthRate returns 0 when no start followers', function (): void {
        $aggregate = AnalyticsAggregate::factory()->create([
            'followers_start' => 0,
            'followers_end' => 100,
        ]);

        expect($aggregate->getFollowerGrowthRate())->toBe(0.0);
    });
});

test('factory creates valid model', function (): void {
    $aggregate = AnalyticsAggregate::factory()->create();

    expect($aggregate)->toBeInstanceOf(AnalyticsAggregate::class)
        ->and($aggregate->id)->not->toBeNull()
        ->and($aggregate->workspace_id)->not->toBeNull()
        ->and($aggregate->date)->not->toBeNull()
        ->and($aggregate->period_type)->toBeInstanceOf(PeriodType::class);
});
