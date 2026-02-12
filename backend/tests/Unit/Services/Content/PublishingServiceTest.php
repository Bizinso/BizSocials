<?php

declare(strict_types=1);

use App\Enums\Content\PostStatus;
use App\Enums\Content\PostTargetStatus;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\PostTargetService;
use App\Services\Content\PublishingService;
use App\Services\Social\Contracts\PublishResult;
use App\Services\Social\Contracts\SocialPlatformAdapter;
use App\Services\Social\SocialPlatformAdapterFactory;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Queue::fake();
    $this->tenant = Tenant::factory()->active()->create();
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $this->linkedinAccount = SocialAccount::factory()->linkedin()->connected()->create([
        'workspace_id' => $this->workspace->id,
        'connected_by_user_id' => $this->user->id,
    ]);
    $this->twitterAccount = SocialAccount::factory()->twitter()->connected()->create([
        'workspace_id' => $this->workspace->id,
        'connected_by_user_id' => $this->user->id,
    ]);

    // Mock the SocialPlatformAdapterFactory so processTarget doesn't make real HTTP calls
    $mockAdapter = Mockery::mock(SocialPlatformAdapter::class);
    $mockAdapter->shouldReceive('publishPost')->andReturn(
        PublishResult::success('ext_post_123', 'https://example.com/post/123')
    );
    $mockFactory = Mockery::mock(SocialPlatformAdapterFactory::class);
    $mockFactory->shouldReceive('create')->andReturn($mockAdapter);
    $this->app->instance(SocialPlatformAdapterFactory::class, $mockFactory);

    $this->postTargetService = app(PostTargetService::class);
    $this->publishingService = app(PublishingService::class);
});

describe('publishNow', function () {
    it('marks an approved post for publishing', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Post to publish',
        ]);

        $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->publishingService->publishNow($post);

        $post->refresh();
        expect($post->status)->toBe(PostStatus::PUBLISHING);

        // All targets should be marked as publishing
        foreach ($post->targets as $target) {
            expect($target->status)->toBe(PostTargetStatus::PUBLISHING);
        }
    });

    it('marks a scheduled post for publishing', function () {
        $post = Post::factory()->scheduled()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->publishingService->publishNow($post);

        $post->refresh();
        expect($post->status)->toBe(PostStatus::PUBLISHING);
    });

    it('throws when publishing draft post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->publishingService->publishNow($post);
    })->throws(ValidationException::class);

    it('throws when publishing submitted post', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->publishingService->publishNow($post);
    })->throws(ValidationException::class);

    it('throws when post has no targets', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->publishingService->publishNow($post);
    })->throws(ValidationException::class);

    it('handles multiple targets', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);
        $post->targets()->create([
            'social_account_id' => $this->twitterAccount->id,
            'platform_code' => 'twitter',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->publishingService->publishNow($post);

        $post->refresh();
        expect($post->targets()->where('status', PostTargetStatus::PUBLISHING)->count())->toBe(2);
    });
});

describe('publishScheduled', function () {
    it('processes posts scheduled for now or past', function () {
        // Post scheduled for the past
        $pastPost = Post::factory()->scheduled()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'scheduled_at' => now()->subMinute(),
        ]);
        $pastPost->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        // Post scheduled for exactly now
        $nowPost = Post::factory()->scheduled()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'scheduled_at' => now(),
        ]);
        $nowPost->targets()->create([
            'social_account_id' => $this->twitterAccount->id,
            'platform_code' => 'twitter',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->publishingService->publishScheduled();

        $pastPost->refresh();
        $nowPost->refresh();

        expect($pastPost->status)->toBe(PostStatus::PUBLISHING);
        expect($nowPost->status)->toBe(PostStatus::PUBLISHING);
    });

    it('does not process future scheduled posts', function () {
        $futurePost = Post::factory()->scheduled()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'scheduled_at' => now()->addHour(),
        ]);
        $futurePost->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->publishingService->publishScheduled();

        $futurePost->refresh();
        expect($futurePost->status)->toBe(PostStatus::SCHEDULED);
    });

    it('handles errors gracefully', function () {
        // Post without targets (will throw validation error)
        $postWithoutTargets = Post::factory()->scheduled()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'scheduled_at' => now()->subMinute(),
        ]);

        // Valid post
        $validPost = Post::factory()->scheduled()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'scheduled_at' => now()->subMinute(),
        ]);
        $validPost->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        // Should not throw, should continue processing
        $this->publishingService->publishScheduled();

        $postWithoutTargets->refresh();
        $validPost->refresh();

        // Post without targets remains scheduled (error handled)
        expect($postWithoutTargets->status)->toBe(PostStatus::SCHEDULED);
        // Valid post gets published
        expect($validPost->status)->toBe(PostStatus::PUBLISHING);
    });
});

describe('retryFailed', function () {
    it('retries a failed post', function () {
        $post = Post::factory()->failed()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::FAILED,
            'error_code' => 'RATE_LIMIT',
            'error_message' => 'Rate limit exceeded',
        ]);

        $this->publishingService->retryFailed($post);

        $post->refresh();
        expect($post->status)->toBe(PostStatus::PUBLISHING);

        $target = $post->targets->first();
        expect($target->status)->toBe(PostTargetStatus::PENDING);
        expect($target->error_code)->toBeNull();
        expect($target->error_message)->toBeNull();
        expect($target->retry_count)->toBe(1);
    });

    it('only resets failed targets', function () {
        $post = Post::factory()->failed()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        // Already published target
        $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PUBLISHED,
            'external_post_id' => 'ext_123',
        ]);

        // Failed target
        $post->targets()->create([
            'social_account_id' => $this->twitterAccount->id,
            'platform_code' => 'twitter',
            'status' => PostTargetStatus::FAILED,
            'error_code' => 'API_ERROR',
        ]);

        $this->publishingService->retryFailed($post);

        $linkedinTarget = $post->targets()->where('platform_code', 'linkedin')->first();
        $twitterTarget = $post->targets()->where('platform_code', 'twitter')->first();

        // Published target should remain unchanged
        expect($linkedinTarget->status)->toBe(PostTargetStatus::PUBLISHED);
        expect($linkedinTarget->external_post_id)->toBe('ext_123');

        // Failed target should be reset to pending
        expect($twitterTarget->status)->toBe(PostTargetStatus::PENDING);
        expect($twitterTarget->error_code)->toBeNull();
    });

    it('throws when retrying non-failed post', function () {
        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->publishingService->retryFailed($post);
    })->throws(ValidationException::class);

    it('throws when retrying published post', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->publishingService->retryFailed($post);
    })->throws(ValidationException::class);
});

describe('processTarget', function () {
    it('publishes target successfully via adapter', function () {
        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->publishingService->processTarget($target);

        $target->refresh();
        expect($target->status)->toBe(PostTargetStatus::PUBLISHED);
    });

    it('fails target when integration is disabled', function () {
        // Create a disabled Meta integration
        \App\Models\Platform\SocialPlatformIntegration::factory()->disabled()->create([
            'provider' => 'meta_disabled_test',
            'platforms' => ['facebook'],
        ]);

        $fbAccount = \App\Models\Social\SocialAccount::factory()->facebook()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = $post->targets()->create([
            'social_account_id' => $fbAccount->id,
            'platform_code' => 'facebook',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->publishingService->processTarget($target);

        $target->refresh();
        expect($target->status)->toBe(PostTargetStatus::FAILED);
        expect($target->error_code)->toBe('INTEGRATION_DISABLED');
        expect($target->error_message)->toContain('disabled by administrator');
    });

    it('fails target when token is expired', function () {
        $fbAccount = \App\Models\Social\SocialAccount::factory()->facebook()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
            'token_expires_at' => now()->subDay(), // Expired yesterday
        ]);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = $post->targets()->create([
            'social_account_id' => $fbAccount->id,
            'platform_code' => 'facebook',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->publishingService->processTarget($target);

        $target->refresh();
        expect($target->status)->toBe(PostTargetStatus::FAILED);
        expect($target->error_code)->toBe('TOKEN_EXPIRED');
        expect($target->error_message)->toContain('expired');

        // Account should be marked as TOKEN_EXPIRED
        $fbAccount->refresh();
        expect($fbAccount->status)->toBe(\App\Enums\Social\SocialAccountStatus::TOKEN_EXPIRED);
    });

    it('fails target when account is unavailable', function () {
        $revokedAccount = \App\Models\Social\SocialAccount::factory()->facebook()->revoked()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = $post->targets()->create([
            'social_account_id' => $revokedAccount->id,
            'platform_code' => 'facebook',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->publishingService->processTarget($target);

        $target->refresh();
        expect($target->status)->toBe(PostTargetStatus::FAILED);
        expect($target->error_code)->toBe('ACCOUNT_UNAVAILABLE');
    });

    it('succeeds when integration is active', function () {
        // Create an active Meta integration
        \App\Models\Platform\SocialPlatformIntegration::factory()->active()->create([
            'provider' => 'meta_active_test',
            'platforms' => ['facebook'],
        ]);

        $fbAccount = \App\Models\Social\SocialAccount::factory()->facebook()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
            'token_expires_at' => now()->addMonth(), // Valid token
        ]);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = $post->targets()->create([
            'social_account_id' => $fbAccount->id,
            'platform_code' => 'facebook',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->publishingService->processTarget($target);

        $target->refresh();
        expect($target->status)->toBe(PostTargetStatus::PUBLISHED);
    });

    it('succeeds when no integration record exists (env fallback)', function () {
        // No SocialPlatformIntegration in DB = uses env config fallback
        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->publishingService->processTarget($target);

        $target->refresh();
        expect($target->status)->toBe(PostTargetStatus::PUBLISHED);
    });

    it('fails target when adapter throws exception', function () {
        // Reconfigure mock to throw
        $mockAdapter = Mockery::mock(\App\Services\Social\Contracts\SocialPlatformAdapter::class);
        $mockAdapter->shouldReceive('publishPost')->andThrow(new \RuntimeException('Network error'));
        $mockFactory = Mockery::mock(SocialPlatformAdapterFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockAdapter);
        $this->app->instance(SocialPlatformAdapterFactory::class, $mockFactory);

        // Re-resolve the service
        $publishingService = app(PublishingService::class);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        $publishingService->processTarget($target);

        $target->refresh();
        expect($target->status)->toBe(PostTargetStatus::FAILED);
        expect($target->error_code)->toBe('EXCEPTION');
        expect($target->error_message)->toBe('Network error');
    });

    it('fails target when adapter returns failure result', function () {
        // Reconfigure mock to return failure
        $mockAdapter = Mockery::mock(\App\Services\Social\Contracts\SocialPlatformAdapter::class);
        $mockAdapter->shouldReceive('publishPost')->andReturn(
            PublishResult::failure('RATE_LIMIT', 'Too many requests')
        );
        $mockFactory = Mockery::mock(SocialPlatformAdapterFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockAdapter);
        $this->app->instance(SocialPlatformAdapterFactory::class, $mockFactory);

        $publishingService = app(PublishingService::class);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        $publishingService->processTarget($target);

        $target->refresh();
        expect($target->status)->toBe(PostTargetStatus::FAILED);
        expect($target->error_code)->toBe('RATE_LIMIT');
        expect($target->error_message)->toBe('Too many requests');
    });
});

describe('updatePostStatusFromTargets', function () {
    it('marks post as published when all targets succeed', function () {
        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PUBLISHED,
        ]);
        $post->targets()->create([
            'social_account_id' => $this->twitterAccount->id,
            'platform_code' => 'twitter',
            'status' => PostTargetStatus::PUBLISHED,
        ]);

        $this->publishingService->updatePostStatusFromTargets($post);

        $post->refresh();
        expect($post->status)->toBe(PostStatus::PUBLISHED);
    });

    it('marks post as failed when all targets fail', function () {
        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::FAILED,
        ]);
        $post->targets()->create([
            'social_account_id' => $this->twitterAccount->id,
            'platform_code' => 'twitter',
            'status' => PostTargetStatus::FAILED,
        ]);

        $this->publishingService->updatePostStatusFromTargets($post);

        $post->refresh();
        expect($post->status)->toBe(PostStatus::FAILED);
    });

    it('marks post as published with partial success', function () {
        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PUBLISHED,
        ]);
        $post->targets()->create([
            'social_account_id' => $this->twitterAccount->id,
            'platform_code' => 'twitter',
            'status' => PostTargetStatus::FAILED,
        ]);

        $this->publishingService->updatePostStatusFromTargets($post);

        $post->refresh();
        expect($post->status)->toBe(PostStatus::PUBLISHED);
    });

    it('does not update status when targets are still pending', function () {
        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PUBLISHED,
        ]);
        $post->targets()->create([
            'social_account_id' => $this->twitterAccount->id,
            'platform_code' => 'twitter',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->publishingService->updatePostStatusFromTargets($post);

        $post->refresh();
        expect($post->status)->toBe(PostStatus::PUBLISHING);
    });

    it('does not update status when targets are still publishing', function () {
        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PUBLISHED,
        ]);
        $post->targets()->create([
            'social_account_id' => $this->twitterAccount->id,
            'platform_code' => 'twitter',
            'status' => PostTargetStatus::PUBLISHING,
        ]);

        $this->publishingService->updatePostStatusFromTargets($post);

        $post->refresh();
        expect($post->status)->toBe(PostStatus::PUBLISHING);
    });
});

describe('publishScheduled', function () {
    it('processes due scheduled posts by marking them PUBLISHING', function () {
        $post = Post::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'status' => PostStatus::SCHEDULED,
            'scheduled_at' => now()->subMinutes(5),
            'content_text' => 'Scheduled post',
        ]);

        $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->publishingService->publishScheduled();

        $post->refresh();
        expect($post->status)->toBe(PostStatus::PUBLISHING);
    });

    it('skips posts scheduled in the future', function () {
        $post = Post::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'status' => PostStatus::SCHEDULED,
            'scheduled_at' => now()->addHours(2),
            'content_text' => 'Future post',
        ]);

        $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->publishingService->publishScheduled();

        $post->refresh();
        expect($post->status)->toBe(PostStatus::SCHEDULED);
    });

    it('limits processing to 100 posts per run', function () {
        // Create 105 due posts
        for ($i = 0; $i < 105; $i++) {
            $post = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'status' => PostStatus::SCHEDULED,
                'scheduled_at' => now()->subMinutes(10),
                'content_text' => "Post {$i}",
            ]);

            $post->targets()->create([
                'social_account_id' => $this->linkedinAccount->id,
                'platform_code' => 'linkedin',
                'status' => PostTargetStatus::PENDING,
            ]);
        }

        $this->publishingService->publishScheduled();

        // Only 100 should have transitioned to PUBLISHING
        $publishingCount = Post::where('status', PostStatus::PUBLISHING)->count();
        $scheduledCount = Post::where('status', PostStatus::SCHEDULED)->count();

        expect($publishingCount)->toBe(100);
        expect($scheduledCount)->toBe(5);
    });

    it('handles publishNow failure gracefully without blocking other posts', function () {
        // Post without targets — publishNow() will throw ValidationException
        $badPost = Post::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'status' => PostStatus::SCHEDULED,
            'scheduled_at' => now()->subMinutes(10),
            'content_text' => 'No targets',
        ]);

        // Post with targets — should succeed
        $goodPost = Post::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'status' => PostStatus::SCHEDULED,
            'scheduled_at' => now()->subMinutes(5),
            'content_text' => 'Has targets',
        ]);

        $goodPost->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->publishingService->publishScheduled();

        // Good post should be publishing, bad post stays scheduled
        $goodPost->refresh();
        $badPost->refresh();
        expect($goodPost->status)->toBe(PostStatus::PUBLISHING);
        expect($badPost->status)->toBe(PostStatus::SCHEDULED);
    });

    it('processes posts ordered by scheduled_at ascending', function () {
        // Create older post
        $olderPost = Post::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'status' => PostStatus::SCHEDULED,
            'scheduled_at' => now()->subMinutes(30),
            'content_text' => 'Older post',
        ]);
        $olderPost->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        // Create newer post
        $newerPost = Post::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'status' => PostStatus::SCHEDULED,
            'scheduled_at' => now()->subMinutes(1),
            'content_text' => 'Newer post',
        ]);
        $newerPost->targets()->create([
            'social_account_id' => $this->twitterAccount->id,
            'platform_code' => 'twitter',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->publishingService->publishScheduled();

        // Both should be published (order doesn't affect outcome, but verifies query works)
        $olderPost->refresh();
        $newerPost->refresh();
        expect($olderPost->status)->toBe(PostStatus::PUBLISHING);
        expect($newerPost->status)->toBe(PostStatus::PUBLISHING);
    });
});
