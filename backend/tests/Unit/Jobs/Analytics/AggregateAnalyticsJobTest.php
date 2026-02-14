<?php

declare(strict_types=1);

/**
 * AggregateAnalyticsJob Unit Tests
 *
 * Tests for the job that aggregates analytics data for workspaces.
 *
 * @see \App\Jobs\Analytics\AggregateAnalyticsJob
 */

use App\Enums\Analytics\PeriodType;
use App\Jobs\Analytics\AggregateAnalyticsJob;
use App\Models\Analytics\AnalyticsAggregate;
use App\Models\Content\Post;
use App\Models\Inbox\PostMetricSnapshot;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Carbon\Carbon;

describe('AggregateAnalyticsJob', function (): void {
    describe('job configuration', function (): void {
        it('is assigned to the analytics queue', function (): void {
            $job = new AggregateAnalyticsJob(
                workspaceId: fake()->uuid(),
                date: now()->toDateString()
            );

            expect($job->queue)->toBe('analytics');
        });

        it('is configured with correct number of tries', function (): void {
            $job = new AggregateAnalyticsJob(
                workspaceId: fake()->uuid(),
                date: now()->toDateString()
            );

            expect($job->tries)->toBe(3);
        });

        it('is configured with 5 minute timeout', function (): void {
            $job = new AggregateAnalyticsJob(
                workspaceId: fake()->uuid(),
                date: now()->toDateString()
            );

            expect($job->timeout)->toBe(300);
        });
    });

    describe('aggregating metrics', function (): void {
        it('creates aggregate for workspace with no accounts', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();

            // Act
            $job = new AggregateAnalyticsJob(
                workspaceId: $workspace->id,
                date: now()->toDateString()
            );
            $job->handle(app(AnalyticsAggregationService::class));

            // Assert - should create workspace-level aggregate
            $aggregate = AnalyticsAggregate::query()
                ->where('workspace_id', $workspace->id)
                ->whereNull('social_account_id')
                ->first();

            expect($aggregate)->not->toBeNull();
        });

        it('creates aggregates for each social account', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();
            SocialAccount::factory()->count(3)->forWorkspace($workspace)->create();

            // Act
            $job = new AggregateAnalyticsJob(
                workspaceId: $workspace->id,
                periodType: PeriodType::DAILY,
                date: now()
            );
            $job->handle();

            // Assert
            $aggregates = AnalyticsAggregate::query()
                ->where('workspace_id', $workspace->id)
                ->get();

            // 3 accounts + 1 workspace-level = 4 total
            expect($aggregates)->toHaveCount(4);
        });

        it('aggregates metrics from post snapshots', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();
            $account = SocialAccount::factory()->forWorkspace($workspace)->create();
            $post = Post::factory()->forWorkspace($workspace)->published()->create();
            $target = PostTarget::factory()->forPost($post)->forSocialAccount($account)->create();

            // Create metric snapshots
            PostMetricSnapshot::factory()
                ->forPostTarget($target)
                ->create([
                    'impressions_count' => 1000,
                    'reach_count' => 500,
                    'likes_count' => 50,
                    'comments_count' => 25,
                    'shares_count' => 10,
                    'clicks_count' => 15,
                    'captured_at' => now(),
                ]);

            // Act
            $job = new AggregateAnalyticsJob(
                workspaceId: $workspace->id,
                periodType: PeriodType::DAILY,
                date: now()
            );
            $job->handle();

            // Assert
            $aggregate = AnalyticsAggregate::query()
                ->where('workspace_id', $workspace->id)
                ->where('social_account_id', $account->id)
                ->first();

            expect($aggregate)->not->toBeNull()
                ->and($aggregate->impressions)->toBe(1000)
                ->and($aggregate->reach)->toBe(500)
                ->and($aggregate->engagements)->toBe(85); // 50 + 25 + 10
        });

        it('handles different period types', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();

            // Act & Assert for DAILY
            $dailyJob = new AggregateAnalyticsJob(
                workspaceId: $workspace->id,
                periodType: PeriodType::DAILY,
                date: now()
            );
            $dailyJob->handle();

            $dailyAggregate = AnalyticsAggregate::query()
                ->where('workspace_id', $workspace->id)
                ->where('period_type', PeriodType::DAILY)
                ->first();

            expect($dailyAggregate)->not->toBeNull();

            // Act & Assert for WEEKLY
            $weeklyJob = new AggregateAnalyticsJob(
                workspaceId: $workspace->id,
                periodType: PeriodType::WEEKLY,
                date: now()
            );
            $weeklyJob->handle();

            $weeklyAggregate = AnalyticsAggregate::query()
                ->where('workspace_id', $workspace->id)
                ->where('period_type', PeriodType::WEEKLY)
                ->first();

            expect($weeklyAggregate)->not->toBeNull();
        });
    });

    describe('calculating engagement rate', function (): void {
        it('calculates engagement rate from impressions and engagements', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();
            $account = SocialAccount::factory()->forWorkspace($workspace)->create();
            $post = Post::factory()->forWorkspace($workspace)->published()->create();
            $target = PostTarget::factory()->forPost($post)->forSocialAccount($account)->create();

            PostMetricSnapshot::factory()
                ->forPostTarget($target)
                ->create([
                    'impressions_count' => 1000,
                    'likes_count' => 30,
                    'comments_count' => 15,
                    'shares_count' => 5, // Total engagements = 50, 5% rate
                    'captured_at' => now(),
                ]);

            // Act
            $job = new AggregateAnalyticsJob(
                workspaceId: $workspace->id,
                periodType: PeriodType::DAILY,
                date: now()
            );
            $job->handle();

            // Assert
            $aggregate = AnalyticsAggregate::query()
                ->where('workspace_id', $workspace->id)
                ->where('social_account_id', $account->id)
                ->first();

            expect($aggregate->engagement_rate)->toBe(5.0);
        });

        it('handles zero impressions without error', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();

            // Act - no posts means no impressions
            $job = new AggregateAnalyticsJob(
                workspaceId: $workspace->id,
                periodType: PeriodType::DAILY,
                date: now()
            );
            $job->handle();

            // Assert - should complete without error
            $aggregate = AnalyticsAggregate::query()
                ->where('workspace_id', $workspace->id)
                ->first();

            expect($aggregate->engagement_rate)->toBe(0.0);
        });
    });

    describe('upserting aggregates', function (): void {
        it('updates existing aggregate instead of creating duplicate', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();
            $account = SocialAccount::factory()->forWorkspace($workspace)->create();
            $post = Post::factory()->forWorkspace($workspace)->published()->create();
            $target = PostTarget::factory()->forPost($post)->forSocialAccount($account)->create();
            $date = now()->startOfDay();

            // Create some data
            PostMetricSnapshot::factory()
                ->forPostTarget($target)
                ->create([
                    'impressions_count' => 100,
                    'likes_count' => 10,
                    'comments_count' => 5,
                    'shares_count' => 2,
                    'captured_at' => $date,
                ]);

            // Run job twice
            $job1 = new AggregateAnalyticsJob(
                workspaceId: $workspace->id,
                periodType: PeriodType::DAILY,
                date: $date
            );
            $job1->handle();

            $job2 = new AggregateAnalyticsJob(
                workspaceId: $workspace->id,
                periodType: PeriodType::DAILY,
                date: $date
            );
            $job2->handle();

            // Assert - only one aggregate per account should exist
            $aggregates = AnalyticsAggregate::query()
                ->where('workspace_id', $workspace->id)
                ->where('social_account_id', $account->id)
                ->where('period_type', PeriodType::DAILY)
                ->whereDate('date', $date)
                ->get();

            expect($aggregates)->toHaveCount(1);
        });
    });
});
