<?php

declare(strict_types=1);

namespace Tests\Properties;

use App\Enums\Content\PostStatus;
use App\Jobs\Content\PublishPostJob;
use App\Models\Content\Post;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\PropertyGenerators;
use Tests\Helpers\PropertyTestTrait;
use Tests\TestCase;

/**
 * Queue Job Creation Property Test
 *
 * Tests that scheduled posts create queue jobs for publishing.
 *
 * Feature: platform-audit-and-testing
 */
class QueueJobCreationPropertyTest extends TestCase
{
    use PropertyTestTrait;
    use RefreshDatabase;

    /**
     * Override the default iteration count to reduce memory usage.
     */
    protected function getPropertyTestIterations(): int
    {
        return 5; // Minimal iterations for testing
    }

    /**
     * Property 8: Queue Job Creation
     *
     * For any valid post that is scheduled for future publication, a corresponding
     * Laravel queue job should be created when the scheduled time arrives and the
     * post is processed for publishing.
     *
     * Feature: platform-audit-and-testing, Property 8: Queue Job Creation
     * Validates: Requirements 3.2
     */
    public function test_scheduled_posts_create_queue_jobs_when_published(): void
    {
        Queue::fake();

        $this->forAll(
            PropertyGenerators::integer(1, 5)
        )
            ->then(function ($hoursInFuture) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                // Create a draft post
                $post = Post::factory()->create([
                    'workspace_id' => $workspace->id,
                    'created_by_user_id' => $user->id,
                    'status' => PostStatus::APPROVED, // Start with APPROVED status so it can be scheduled
                ]);

                // Schedule the post for the future
                $scheduledTime = now()->addHours($hoursInFuture);
                $post->schedule($scheduledTime);

                // Verify the post is scheduled
                $this->assertEquals(PostStatus::SCHEDULED, $post->fresh()->status);
                $this->assertNotNull($post->fresh()->scheduled_at);

                // Simulate the scheduled time arriving by updating the scheduled_at to the past
                $post->update(['scheduled_at' => now()->subMinute()]);

                // Add a target to avoid validation errors
                \App\Models\Content\PostTarget::factory()->create([
                    'post_id' => $post->id,
                    'platform_code' => 'facebook',
                    'social_account_id' => \App\Models\Social\SocialAccount::factory()->facebook()->create([
                        'workspace_id' => $workspace->id,
                    ])->id,
                ]);

                // Call the publishing service to process scheduled posts
                $publishingService = app(\App\Services\Content\PublishingService::class);
                $publishingService->publishScheduled();

                // Assert that a PublishPostJob was dispatched for this post
                Queue::assertPushed(PublishPostJob::class, function (PublishPostJob $job) use ($post) {
                    return $job->postId === $post->id
                        && $job->workspaceId === $post->workspace_id;
                });
            });
    }

    /**
     * Property 8: Queue Job Creation - Multiple Posts
     *
     * For any set of scheduled posts that are due for publication, a queue job
     * should be created for each post.
     *
     * Feature: platform-audit-and-testing, Property 8: Queue Job Creation
     * Validates: Requirements 3.2
     */
    public function test_multiple_scheduled_posts_create_multiple_queue_jobs(): void
    {
        $this->forAll(
            PropertyGenerators::integer(2, 3)
        )
            ->then(function ($postCount) {
                Queue::fake(); // Reset queue fake for each iteration

                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                $posts = [];

                // Create multiple scheduled posts
                for ($i = 0; $i < $postCount; $i++) {
                    $post = Post::factory()->create([
                        'workspace_id' => $workspace->id,
                        'created_by_user_id' => $user->id,
                        'status' => PostStatus::APPROVED, // Start with APPROVED status so it can be scheduled
                    ]);

                    // Schedule the post
                    $post->schedule(now()->addHours($i + 1));

                    // Make the post due by updating scheduled_at to the past
                    $post->update(['scheduled_at' => now()->subMinutes($i + 1)]);

                    // Add a target to avoid validation errors
                    \App\Models\Content\PostTarget::factory()->create([
                        'post_id' => $post->id,
                        'platform_code' => 'facebook',
                        'social_account_id' => \App\Models\Social\SocialAccount::factory()->facebook()->create([
                            'workspace_id' => $workspace->id,
                        ])->id,
                    ]);

                    $posts[] = $post;
                }

                // Process scheduled posts
                $publishingService = app(\App\Services\Content\PublishingService::class);
                $publishingService->publishScheduled();

                // Assert that a queue job was created for each post
                Queue::assertPushed(PublishPostJob::class, $postCount);

                // Verify each post has its own job
                foreach ($posts as $post) {
                    Queue::assertPushed(PublishPostJob::class, function (PublishPostJob $job) use ($post) {
                        return $job->postId === $post->id;
                    });
                }
            });
    }

    /**
     * Property 8: Queue Job Creation - Not Due Posts
     *
     * For any scheduled post that is not yet due for publication, no queue job
     * should be created.
     *
     * Feature: platform-audit-and-testing, Property 8: Queue Job Creation
     * Validates: Requirements 3.2
     */
    public function test_future_scheduled_posts_do_not_create_queue_jobs(): void
    {
        Queue::fake();

        $this->forAll(
            PropertyGenerators::integer(1, 12)
        )
            ->then(function ($hoursInFuture) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                // Create a scheduled post that is NOT due yet
                $post = Post::factory()->create([
                    'workspace_id' => $workspace->id,
                    'created_by_user_id' => $user->id,
                    'status' => PostStatus::APPROVED, // Start with APPROVED status so it can be scheduled
                ]);

                // Schedule the post for the future
                $scheduledTime = now()->addHours($hoursInFuture);
                $post->schedule($scheduledTime);

                // Add a target
                \App\Models\Content\PostTarget::factory()->create([
                    'post_id' => $post->id,
                    'platform_code' => 'facebook',
                    'social_account_id' => \App\Models\Social\SocialAccount::factory()->facebook()->create([
                        'workspace_id' => $workspace->id,
                    ])->id,
                ]);

                // Process scheduled posts
                $publishingService = app(\App\Services\Content\PublishingService::class);
                $publishingService->publishScheduled();

                // Assert that NO queue job was created for this future post
                Queue::assertNotPushed(PublishPostJob::class, function (PublishPostJob $job) use ($post) {
                    return $job->postId === $post->id;
                });
            });
    }

    /**
     * Property 8: Queue Job Creation - Draft Posts
     *
     * For any post that is in draft status (not scheduled), no queue job should
     * be created even if it has a scheduled_at time.
     *
     * Feature: platform-audit-and-testing, Property 8: Queue Job Creation
     * Validates: Requirements 3.2
     */
    public function test_draft_posts_do_not_create_queue_jobs(): void
    {
        Queue::fake();

        $this->forAll(
            PropertyGenerators::integer(1, 5)
        )
            ->then(function ($id) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                // Create a draft post with a scheduled_at time but DRAFT status
                $post = Post::factory()->create([
                    'workspace_id' => $workspace->id,
                    'created_by_user_id' => $user->id,
                    'status' => PostStatus::DRAFT,
                    'scheduled_at' => now()->subMinute(), // Past time
                ]);

                // Add a target
                \App\Models\Content\PostTarget::factory()->create([
                    'post_id' => $post->id,
                    'platform_code' => 'facebook',
                    'social_account_id' => \App\Models\Social\SocialAccount::factory()->facebook()->create([
                        'workspace_id' => $workspace->id,
                    ])->id,
                ]);

                // Process scheduled posts
                $publishingService = app(\App\Services\Content\PublishingService::class);
                $publishingService->publishScheduled();

                // Assert that NO queue job was created for this draft post
                Queue::assertNotPushed(PublishPostJob::class, function (PublishPostJob $job) use ($post) {
                    return $job->postId === $post->id;
                });
            });
    }

    /**
     * Property 8: Queue Job Creation - Job Contains Correct Data
     *
     * For any scheduled post that creates a queue job, the job should contain
     * the correct post ID and workspace ID.
     *
     * Feature: platform-audit-and-testing, Property 8: Queue Job Creation
     * Validates: Requirements 3.2
     */
    public function test_queue_jobs_contain_correct_post_and_workspace_ids(): void
    {
        Queue::fake();

        $this->forAll(
            PropertyGenerators::integer(1, 5)
        )
            ->then(function ($id) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                // Create and schedule a post
                $post = Post::factory()->create([
                    'workspace_id' => $workspace->id,
                    'created_by_user_id' => $user->id,
                    'status' => PostStatus::APPROVED, // Start with APPROVED status so it can be scheduled
                ]);

                $post->schedule(now()->addHour());
                $post->update(['scheduled_at' => now()->subMinute()]);

                // Add a target
                \App\Models\Content\PostTarget::factory()->create([
                    'post_id' => $post->id,
                    'platform_code' => 'facebook',
                    'social_account_id' => \App\Models\Social\SocialAccount::factory()->facebook()->create([
                        'workspace_id' => $workspace->id,
                    ])->id,
                ]);

                // Process scheduled posts
                $publishingService = app(\App\Services\Content\PublishingService::class);
                $publishingService->publishScheduled();

                // Assert the job contains the correct IDs
                Queue::assertPushed(PublishPostJob::class, function (PublishPostJob $job) use ($post, $workspace) {
                    return $job->postId === $post->id
                        && $job->workspaceId === $workspace->id;
                });
            });
    }

    /**
     * Property 8: Queue Job Creation - Published Posts
     *
     * For any post that is already published, no queue job should be created
     * even if it has a scheduled_at time in the past.
     *
     * Feature: platform-audit-and-testing, Property 8: Queue Job Creation
     * Validates: Requirements 3.2
     */
    public function test_already_published_posts_do_not_create_queue_jobs(): void
    {
        Queue::fake();

        $this->forAll(
            PropertyGenerators::integer(1, 5)
        )
            ->then(function ($id) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                // Create a published post
                $post = Post::factory()->create([
                    'workspace_id' => $workspace->id,
                    'created_by_user_id' => $user->id,
                    'status' => PostStatus::PUBLISHED,
                    'scheduled_at' => now()->subHour(),
                    'published_at' => now()->subMinutes(30),
                ]);

                // Add a target
                \App\Models\Content\PostTarget::factory()->create([
                    'post_id' => $post->id,
                    'platform_code' => 'facebook',
                    'social_account_id' => \App\Models\Social\SocialAccount::factory()->facebook()->create([
                        'workspace_id' => $workspace->id,
                    ])->id,
                ]);

                // Process scheduled posts
                $publishingService = app(\App\Services\Content\PublishingService::class);
                $publishingService->publishScheduled();

                // Assert that NO queue job was created for this already published post
                Queue::assertNotPushed(PublishPostJob::class, function (PublishPostJob $job) use ($post) {
                    return $job->postId === $post->id;
                });
            });
    }
}
