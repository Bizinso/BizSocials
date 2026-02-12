<?php

declare(strict_types=1);

/**
 * PublishScheduledPostsJob Unit Tests
 *
 * Tests for the coordinator job that delegates to PublishingService::publishScheduled().
 *
 * @see \App\Jobs\Content\PublishScheduledPostsJob
 */

use App\Enums\Content\PostStatus;
use App\Enums\Content\PostTargetStatus;
use App\Jobs\Content\PublishPostJob;
use App\Jobs\Content\PublishScheduledPostsJob;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\PublishingService;
use App\Services\Social\SocialPlatformAdapterFactory;
use Illuminate\Support\Facades\Queue;
use Tests\Stubs\Services\FakeSocialPlatformAdapterFactory;

beforeEach(function (): void {
    Queue::fake();
    app()->instance(SocialPlatformAdapterFactory::class, new FakeSocialPlatformAdapterFactory());
});

describe('PublishScheduledPostsJob', function (): void {
    describe('delegating to PublishingService', function (): void {
        it('dispatches PublishPostJob for scheduled posts that are due', function (): void {
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);
            $socialAccount = SocialAccount::factory()->connected()->create(['workspace_id' => $workspace->id]);

            $post = Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::SCHEDULED,
                'scheduled_at' => now()->subMinutes(5),
                'content_text' => 'Test post',
            ]);

            PostTarget::factory()->create([
                'post_id' => $post->id,
                'social_account_id' => $socialAccount->id,
                'status' => PostTargetStatus::PENDING,
            ]);

            $job = new PublishScheduledPostsJob();
            $job->handle(app(PublishingService::class));

            Queue::assertPushed(PublishPostJob::class, function (PublishPostJob $job) use ($post): bool {
                return $job->postId === $post->id
                    && $job->workspaceId === $post->workspace_id;
            });
        });

        it('marks post as PUBLISHING before dispatching', function (): void {
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);
            $socialAccount = SocialAccount::factory()->connected()->create(['workspace_id' => $workspace->id]);

            $post = Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::SCHEDULED,
                'scheduled_at' => now()->subMinutes(5),
                'content_text' => 'Test post',
            ]);

            PostTarget::factory()->create([
                'post_id' => $post->id,
                'social_account_id' => $socialAccount->id,
                'status' => PostTargetStatus::PENDING,
            ]);

            $job = new PublishScheduledPostsJob();
            $job->handle(app(PublishingService::class));

            $post->refresh();
            expect($post->status)->toBe(PostStatus::PUBLISHING);
        });

        it('dispatches PublishPostJob for multiple scheduled posts', function (): void {
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);
            $socialAccount = SocialAccount::factory()->connected()->create(['workspace_id' => $workspace->id]);

            $posts = Post::factory()->count(3)->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::SCHEDULED,
                'scheduled_at' => now()->subMinutes(5),
                'content_text' => 'Test post',
            ]);

            foreach ($posts as $post) {
                PostTarget::factory()->create([
                    'post_id' => $post->id,
                    'social_account_id' => $socialAccount->id,
                    'status' => PostTargetStatus::PENDING,
                ]);
            }

            $job = new PublishScheduledPostsJob();
            $job->handle(app(PublishingService::class));

            Queue::assertPushed(PublishPostJob::class, 3);
        });
    });

    describe('not dispatching for non-scheduled posts', function (): void {
        it('does not dispatch for draft posts', function (): void {
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::DRAFT,
                'scheduled_at' => now()->subMinutes(5),
            ]);

            $job = new PublishScheduledPostsJob();
            $job->handle(app(PublishingService::class));

            Queue::assertNotPushed(PublishPostJob::class);
        });

        it('does not dispatch for published posts', function (): void {
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::PUBLISHED,
                'published_at' => now()->subHour(),
            ]);

            $job = new PublishScheduledPostsJob();
            $job->handle(app(PublishingService::class));

            Queue::assertNotPushed(PublishPostJob::class);
        });

        it('does not dispatch for approved posts without schedule', function (): void {
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::APPROVED,
                'scheduled_at' => null,
            ]);

            $job = new PublishScheduledPostsJob();
            $job->handle(app(PublishingService::class));

            Queue::assertNotPushed(PublishPostJob::class);
        });
    });

    describe('not dispatching for future scheduled posts', function (): void {
        it('does not dispatch for posts scheduled in the future', function (): void {
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::SCHEDULED,
                'scheduled_at' => now()->addHours(2),
            ]);

            $job = new PublishScheduledPostsJob();
            $job->handle(app(PublishingService::class));

            Queue::assertNotPushed(PublishPostJob::class);
        });

        it('dispatches only for posts scheduled at or before now', function (): void {
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);
            $socialAccount = SocialAccount::factory()->connected()->create(['workspace_id' => $workspace->id]);

            $pastPost = Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::SCHEDULED,
                'scheduled_at' => now()->subMinutes(10),
                'content_text' => 'Past post',
            ]);

            PostTarget::factory()->create([
                'post_id' => $pastPost->id,
                'social_account_id' => $socialAccount->id,
                'status' => PostTargetStatus::PENDING,
            ]);

            Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::SCHEDULED,
                'scheduled_at' => now()->addHours(1),
            ]);

            $job = new PublishScheduledPostsJob();
            $job->handle(app(PublishingService::class));

            Queue::assertPushed(PublishPostJob::class, 1);
            Queue::assertPushed(PublishPostJob::class, function (PublishPostJob $job) use ($pastPost): bool {
                return $job->postId === $pastPost->id;
            });
        });
    });

    describe('handling empty results', function (): void {
        it('handles empty results gracefully when no posts exist', function (): void {
            $job = new PublishScheduledPostsJob();
            $job->handle(app(PublishingService::class));

            Queue::assertNotPushed(PublishPostJob::class);
        });

        it('handles empty results when all posts are in wrong status', function (): void {
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            Post::factory()->count(5)->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::DRAFT,
            ]);

            $job = new PublishScheduledPostsJob();
            $job->handle(app(PublishingService::class));

            Queue::assertNotPushed(PublishPostJob::class);
        });
    });

    describe('job configuration', function (): void {
        it('is configured with correct number of tries', function (): void {
            $job = new PublishScheduledPostsJob();

            expect($job->tries)->toBe(3);
        });

        it('is configured with correct timeout', function (): void {
            $job = new PublishScheduledPostsJob();

            expect($job->timeout)->toBe(120);
        });

        it('is configured with correct backoff', function (): void {
            $job = new PublishScheduledPostsJob();

            expect($job->backoff)->toBe(30);
        });

        it('is assigned to the content queue', function (): void {
            $job = new PublishScheduledPostsJob();

            expect($job->queue)->toBe('content');
        });
    });
});
