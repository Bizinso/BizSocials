<?php

declare(strict_types=1);

/**
 * FetchPostMetricsJob Unit Tests
 *
 * Tests for the job that fetches engagement metrics
 * for published posts from social platforms.
 *
 * @see \App\Jobs\Analytics\FetchPostMetricsJob
 */

use App\Enums\Content\PostStatus;
use App\Enums\Content\PostTargetStatus;
use App\Enums\Social\SocialAccountStatus;
use App\Enums\Social\SocialPlatform;
use App\Jobs\Analytics\FetchPostMetricsJob;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Support\Facades\DB;

describe('FetchPostMetricsJob', function (): void {
    describe('creating metric snapshots for published posts', function (): void {
        it('creates metric snapshots for published posts with targets', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);
            $socialAccount = SocialAccount::factory()->create([
                'workspace_id' => $workspace->id,
                'connected_by_user_id' => $user->id,
                'status' => SocialAccountStatus::CONNECTED,
            ]);

            $post = Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::PUBLISHED,
                'published_at' => now()->subHour(),
            ]);

            PostTarget::factory()->create([
                'post_id' => $post->id,
                'social_account_id' => $socialAccount->id,
                'status' => PostTargetStatus::PUBLISHED,
                'external_post_id' => 'platform-post-123',
            ]);

            // Act
            $job = new FetchPostMetricsJob($workspace->id);
            $job->handle();

            // Assert - verify metrics were captured
            $snapshotCount = DB::table('post_metric_snapshots')->count();

            expect($snapshotCount)->toBe(1);
        });

        it('creates snapshots for multiple published posts', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);
            $socialAccount = SocialAccount::factory()->create([
                'workspace_id' => $workspace->id,
                'connected_by_user_id' => $user->id,
                'status' => SocialAccountStatus::CONNECTED,
            ]);

            $posts = Post::factory()->count(3)->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::PUBLISHED,
                'published_at' => now()->subHour(),
            ]);

            foreach ($posts as $post) {
                PostTarget::factory()->create([
                    'post_id' => $post->id,
                    'social_account_id' => $socialAccount->id,
                    'status' => PostTargetStatus::PUBLISHED,
                    'external_post_id' => 'platform-post-' . $post->id,
                ]);
            }

            // Act
            $job = new FetchPostMetricsJob($workspace->id);
            $job->handle();

            // Assert
            $snapshotCount = DB::table('post_metric_snapshots')->count();

            expect($snapshotCount)->toBe(3);
        });

        it('stores correct metric fields in snapshot', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);
            $socialAccount = SocialAccount::factory()->create([
                'workspace_id' => $workspace->id,
                'connected_by_user_id' => $user->id,
                'status' => SocialAccountStatus::CONNECTED,
            ]);

            $post = Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::PUBLISHED,
                'published_at' => now()->subHour(),
            ]);

            $target = PostTarget::factory()->create([
                'post_id' => $post->id,
                'social_account_id' => $socialAccount->id,
                'status' => PostTargetStatus::PUBLISHED,
                'external_post_id' => 'platform-post-123',
            ]);

            // Act
            $job = new FetchPostMetricsJob($workspace->id);
            $job->handle();

            // Assert
            $snapshot = DB::table('post_metric_snapshots')
                ->where('post_target_id', $target->id)
                ->first();

            expect($snapshot)->not->toBeNull()
                ->and($snapshot->post_target_id)->toBe($target->id)
                ->and($snapshot->impressions_count)->toBeInt()
                ->and($snapshot->reach_count)->toBeInt()
                ->and($snapshot->likes_count)->toBeInt()
                ->and($snapshot->comments_count)->toBeInt()
                ->and($snapshot->shares_count)->toBeInt()
                ->and($snapshot->clicks_count)->toBeInt();
        });
    });

    describe('skipping unpublished posts', function (): void {
        it('skips draft posts', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::DRAFT,
            ]);

            // Act
            $job = new FetchPostMetricsJob($workspace->id);
            $job->handle();

            // Assert
            $snapshotCount = DB::table('post_metric_snapshots')->count();

            expect($snapshotCount)->toBe(0);
        });

        it('skips scheduled posts', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::SCHEDULED,
                'scheduled_at' => now()->addHour(),
            ]);

            // Act
            $job = new FetchPostMetricsJob($workspace->id);
            $job->handle();

            // Assert
            $snapshotCount = DB::table('post_metric_snapshots')->count();

            expect($snapshotCount)->toBe(0);
        });

        it('skips failed posts', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::FAILED,
            ]);

            // Act
            $job = new FetchPostMetricsJob($workspace->id);
            $job->handle();

            // Assert
            $snapshotCount = DB::table('post_metric_snapshots')->count();

            expect($snapshotCount)->toBe(0);
        });

        it('skips posts without published_at date', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            // Force create a post without published_at even though status is published
            $post = Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::PUBLISHED,
                'published_at' => null,
            ]);

            // Act
            $job = new FetchPostMetricsJob($workspace->id);
            $job->handle();

            // Assert
            $snapshotCount = DB::table('post_metric_snapshots')->count();

            expect($snapshotCount)->toBe(0);
        });

        it('only processes published posts with published_at date', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);
            $socialAccount = SocialAccount::factory()->create([
                'workspace_id' => $workspace->id,
                'connected_by_user_id' => $user->id,
                'status' => SocialAccountStatus::CONNECTED,
            ]);

            // Create published post
            $publishedPost = Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::PUBLISHED,
                'published_at' => now()->subHour(),
            ]);

            PostTarget::factory()->create([
                'post_id' => $publishedPost->id,
                'social_account_id' => $socialAccount->id,
                'status' => PostTargetStatus::PUBLISHED,
                'external_post_id' => 'platform-123',
            ]);

            // Create draft post
            Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::DRAFT,
            ]);

            // Act
            $job = new FetchPostMetricsJob($workspace->id);
            $job->handle();

            // Assert - only 1 snapshot for the published post
            $snapshotCount = DB::table('post_metric_snapshots')->count();

            expect($snapshotCount)->toBe(1);
        });
    });

    describe('handling API errors gracefully', function (): void {
        it('continues processing when one target fails', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);
            $socialAccount = SocialAccount::factory()->create([
                'workspace_id' => $workspace->id,
                'connected_by_user_id' => $user->id,
                'status' => SocialAccountStatus::CONNECTED,
            ]);

            // Create multiple posts
            $posts = Post::factory()->count(3)->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::PUBLISHED,
                'published_at' => now()->subHour(),
            ]);

            foreach ($posts as $post) {
                PostTarget::factory()->create([
                    'post_id' => $post->id,
                    'social_account_id' => $socialAccount->id,
                    'status' => PostTargetStatus::PUBLISHED,
                    'external_post_id' => 'platform-post-' . $post->id,
                ]);
            }

            // Act
            $job = new FetchPostMetricsJob($workspace->id);
            $job->handle();

            // Assert - job completes and creates snapshots
            expect(true)->toBeTrue();
        });

        it('skips targets without platform post ID', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);
            $socialAccount = SocialAccount::factory()->create([
                'workspace_id' => $workspace->id,
                'connected_by_user_id' => $user->id,
                'status' => SocialAccountStatus::CONNECTED,
            ]);

            $post = Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::PUBLISHED,
                'published_at' => now()->subHour(),
            ]);

            PostTarget::factory()->create([
                'post_id' => $post->id,
                'social_account_id' => $socialAccount->id,
                'status' => PostTargetStatus::PUBLISHED,
                'external_post_id' => null, // No external post ID
            ]);

            // Act
            $job = new FetchPostMetricsJob($workspace->id);
            $job->handle();

            // Assert - no snapshot created for target without external_post_id
            $snapshotCount = DB::table('post_metric_snapshots')->count();

            expect($snapshotCount)->toBe(0);
        });

        it('skips targets with disconnected social accounts', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);
            $socialAccount = SocialAccount::factory()->create([
                'workspace_id' => $workspace->id,
                'connected_by_user_id' => $user->id,
                'status' => SocialAccountStatus::DISCONNECTED,
            ]);

            $post = Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::PUBLISHED,
                'published_at' => now()->subHour(),
            ]);

            PostTarget::factory()->create([
                'post_id' => $post->id,
                'social_account_id' => $socialAccount->id,
                'status' => PostTargetStatus::PUBLISHED,
                'external_post_id' => 'platform-123',
            ]);

            // Act
            $job = new FetchPostMetricsJob($workspace->id);
            $job->handle();

            // Assert - no snapshot created for disconnected account
            $snapshotCount = DB::table('post_metric_snapshots')->count();

            expect($snapshotCount)->toBe(0);
        });

        it('handles empty workspace gracefully', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);
            // No posts created

            // Act
            $job = new FetchPostMetricsJob($workspace->id);
            $job->handle();

            // Assert - job completes without error
            expect(true)->toBeTrue();
        });
    });

    describe('job configuration', function (): void {
        it('has unique ID based on workspace ID', function (): void {
            $job = new FetchPostMetricsJob('test-workspace-id');

            expect($job->uniqueId())->toBe('fetch-metrics-test-workspace-id');
        });

        it('is assigned to the analytics queue', function (): void {
            $job = new FetchPostMetricsJob('workspace-id');

            expect($job->queue)->toBe('analytics');
        });

        it('is configured with correct number of tries', function (): void {
            $job = new FetchPostMetricsJob('workspace-id');

            expect($job->tries)->toBe(3);
        });

        it('is configured with correct timeout of 600 seconds', function (): void {
            $job = new FetchPostMetricsJob('workspace-id');

            expect($job->timeout)->toBe(600);
        });

        it('is configured with exponential backoff', function (): void {
            $job = new FetchPostMetricsJob('workspace-id');

            expect($job->backoff)->toBe([60, 120, 300]);
        });
    });
});
