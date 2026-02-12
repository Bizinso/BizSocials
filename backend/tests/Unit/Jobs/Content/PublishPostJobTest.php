<?php

declare(strict_types=1);

/**
 * PublishPostJob Unit Tests
 *
 * Tests for the job that handles publishing a single post
 * to its target social platforms.
 *
 * @see \App\Jobs\Content\PublishPostJob
 */

use App\Enums\Content\PostStatus;
use App\Enums\Content\PostTargetStatus;
use App\Enums\Notification\NotificationType;
use App\Jobs\Content\PublishPostJob;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Notification\Notification;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\PublishingService;
use App\Services\Social\SocialPlatformAdapterFactory;
use Illuminate\Support\Facades\Queue;
use Tests\Stubs\Services\FakeSocialPlatformAdapterFactory;

beforeEach(function (): void {
    Queue::fake();
    // Use fake adapter factory to avoid real HTTP calls during target processing
    app()->instance(SocialPlatformAdapterFactory::class, new FakeSocialPlatformAdapterFactory());
});

describe('PublishPostJob', function (): void {
    describe('publishing post to all targets', function (): void {
        it('publishes post to all targets successfully', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);
            $socialAccount = SocialAccount::factory()->connected()->create(['workspace_id' => $workspace->id]);

            $post = Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::PUBLISHING,
            ]);

            PostTarget::factory()->create([
                'post_id' => $post->id,
                'social_account_id' => $socialAccount->id,
                'status' => PostTargetStatus::PUBLISHING,
            ]);

            // Act
            $job = new PublishPostJob($post->id, $workspace->id);
            $job->handle(app(PublishingService::class));

            // Assert - post is marked as published (fake adapter completes successfully)
            $post->refresh();
            expect($post->status)->toBe(PostStatus::PUBLISHED);
        });

        it('marks targets as published', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);
            $socialAccount = SocialAccount::factory()->connected()->create(['workspace_id' => $workspace->id]);

            $post = Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::PUBLISHING,
            ]);

            $target = PostTarget::factory()->create([
                'post_id' => $post->id,
                'social_account_id' => $socialAccount->id,
                'status' => PostTargetStatus::PUBLISHING,
            ]);

            // Act
            $job = new PublishPostJob($post->id, $workspace->id);
            $job->handle(app(PublishingService::class));

            // Assert - target is marked as published (fake adapter completes successfully)
            $target->refresh();
            expect($target->status)->toBe(PostTargetStatus::PUBLISHED);
        });
    });

    describe('handling post not found', function (): void {
        it('returns early when post is not found', function (): void {
            // Act
            $job = new PublishPostJob('non-existent-id', 'workspace-id');
            $job->handle(app(PublishingService::class));

            // Assert - no exception thrown, job completes gracefully
            expect(true)->toBeTrue();
        });

        it('returns early when post is not in publishable state', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            $post = Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::DRAFT, // Not publishable
            ]);

            // Act
            $job = new PublishPostJob($post->id, $workspace->id);
            $job->handle(app(PublishingService::class));

            // Assert - no exception thrown, job completes gracefully
            $post->refresh();
            expect($post->status)->toBe(PostStatus::DRAFT);
        });
    });

    describe('sending failure notification on error', function (): void {
        it('sends failure notification when publishing fails', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            $post = Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::SCHEDULED,
                'scheduled_at' => now()->subMinutes(5),
                'content_text' => 'Test post content',
            ]);

            // Act - call the failed method directly
            $job = new PublishPostJob($post->id, $workspace->id);
            $job->failed(new \RuntimeException('Test error message'));

            // Assert
            $notification = Notification::query()
                ->where('user_id', $user->id)
                ->where('type', NotificationType::POST_FAILED)
                ->first();

            expect($notification)->not->toBeNull()
                ->and($notification->title)->toBe('Post Publishing Failed');
        });

        it('marks post as failed when job fails', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $user->tenant_id]);

            $post = Post::factory()->create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $user->id,
                'status' => PostStatus::SCHEDULED,
                'scheduled_at' => now()->subMinutes(5),
            ]);

            // Act - call the failed method directly
            $job = new PublishPostJob($post->id, $workspace->id);
            $job->failed(new \RuntimeException('Publishing failed'));

            // Assert
            $post->refresh();
            expect($post->status)->toBe(PostStatus::FAILED);
        });
    });

    describe('retrying on temporary failures', function (): void {
        it('is configured with single immediate retry', function (): void {
            $job = new PublishPostJob('post-id', 'workspace-id');

            expect($job->backoff)->toBe([10])
                ->and($job->tries)->toBe(2);
        });

        it('has retry until set to 5 minutes', function (): void {
            $job = new PublishPostJob('post-id', 'workspace-id');
            $retryUntil = $job->retryUntil();

            expect($retryUntil)->toBeInstanceOf(\DateTime::class);
            // Should be approximately 5 minutes from now
            $diff = $retryUntil->getTimestamp() - time();
            expect($diff)->toBeGreaterThan(4 * 60)
                ->and($diff)->toBeLessThanOrEqual(5 * 60 + 1);
        });
    });

    describe('job configuration', function (): void {
        it('has unique ID based on post ID', function (): void {
            $job = new PublishPostJob('test-post-id', 'workspace-id');

            expect($job->uniqueId())->toBe('publish-post-test-post-id');
        });

        it('is assigned to the content queue', function (): void {
            $job = new PublishPostJob('post-id', 'workspace-id');

            expect($job->queue)->toBe('content');
        });

        it('has correct timeout of 180 seconds', function (): void {
            $job = new PublishPostJob('post-id', 'workspace-id');

            expect($job->timeout)->toBe(180);
        });
    });
});
