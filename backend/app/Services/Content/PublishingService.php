<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\Content\PostStatus;
use App\Enums\Content\PostTargetStatus;
use App\Enums\Social\SocialPlatform;
use App\Events\Content\PostFailed;
use App\Events\Content\PostPublished;
use App\Jobs\Content\PublishPostJob;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Platform\SocialPlatformIntegration;
use App\Services\BaseService;
use App\Services\Social\SocialPlatformAdapterFactory;
use Illuminate\Validation\ValidationException;

final class PublishingService extends BaseService
{
    public function __construct(
        private readonly PostTargetService $postTargetService,
        private readonly SocialPlatformAdapterFactory $adapterFactory,
    ) {}

    /**
     * Mark a post for immediate publishing.
     *
     * @throws ValidationException
     */
    public function publishNow(Post $post): void
    {
        if (!$post->canPublish()) {
            throw ValidationException::withMessages([
                'post' => ['Post cannot be published from its current status.'],
            ]);
        }

        // Validate post has targets
        if ($post->targets()->count() === 0) {
            throw ValidationException::withMessages([
                'targets' => ['Post must have at least one target account.'],
            ]);
        }

        $this->transaction(function () use ($post) {
            // Mark the post as publishing
            $post->markPublishing();

            // Mark all targets as publishing
            $post->targets()->update(['status' => PostTargetStatus::PUBLISHING]);

            $this->log('Post marked for immediate publishing', [
                'post_id' => $post->id,
                'target_count' => $post->targets()->count(),
            ]);

            // Dispatch job to process each target
            PublishPostJob::dispatch($post->id, $post->workspace_id);
        });
    }

    /**
     * Process scheduled posts that are due for publishing.
     *
     * Called by PublishScheduledPostsJob every minute. Fetches up to 100 due
     * posts (oldest first) and calls publishNow() on each, which marks them
     * PUBLISHING and dispatches PublishPostJob. Any overflow is caught by the
     * next minute's cron run.
     */
    public function publishScheduled(): void
    {
        $posts = Post::withStatus(PostStatus::SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at', 'asc')
            ->limit(100)
            ->get();

        $processed = 0;

        foreach ($posts as $post) {
            try {
                $this->publishNow($post);
                $processed++;
            } catch (\Throwable $e) {
                $this->log('Failed to publish scheduled post', [
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                ], 'error');
            }
        }

        $this->log('Processed scheduled posts', [
            'total_due' => $posts->count(),
            'processed' => $processed,
        ]);
    }

    /**
     * Retry publishing a failed post.
     *
     * @throws ValidationException
     */
    public function retryFailed(Post $post): void
    {
        if ($post->status !== PostStatus::FAILED) {
            throw ValidationException::withMessages([
                'post' => ['Only failed posts can be retried.'],
            ]);
        }

        $this->transaction(function () use ($post) {
            // Reset failed targets to pending
            $post->targets()
                ->where('status', PostTargetStatus::FAILED)
                ->update([
                    'status' => PostTargetStatus::PENDING,
                    'error_code' => null,
                    'error_message' => null,
                ]);

            // Increment retry count on targets
            $post->targets()
                ->where('status', PostTargetStatus::PENDING)
                ->increment('retry_count');

            // Mark post as publishing again
            $post->markPublishing();

            $this->log('Retrying failed post publishing', [
                'post_id' => $post->id,
            ]);

            // Dispatch job to retry targets
            PublishPostJob::dispatch($post->id, $post->workspace_id);
        });
    }

    /**
     * Process a single target for publishing.
     */
    public function processTarget(PostTarget $target): void
    {
        $target->markPublishing();

        $target->loadMissing(['post.media', 'socialAccount']);
        $post = $target->post;
        $media = $post->media ?? collect();

        $platform = SocialPlatform::tryFrom($target->platform_code);

        if ($platform === null) {
            $target->markFailed('UNKNOWN_PLATFORM', "Unknown platform: {$target->platform_code}");
            return;
        }

        $account = $target->socialAccount;

        if ($account === null || !$account->canPublish()) {
            $target->markFailed('ACCOUNT_UNAVAILABLE', 'Social account is not available for publishing.');
            $this->logPublishFailure($target, $post);
            return;
        }

        // GAP-1: Block publishing if platform integration is disabled
        $integration = SocialPlatformIntegration::forPlatform($platform)->first();
        if ($integration !== null && !$integration->isActive()) {
            $target->markFailed('INTEGRATION_DISABLED', 'Platform integration is disabled by administrator.');
            $this->logPublishFailure($target, $post);
            return;
        }

        // GAP-2: Fail fast if token is expired
        if ($account->isTokenExpired()) {
            $account->markTokenExpired();
            $target->markFailed('TOKEN_EXPIRED', 'Access token has expired. Please reconnect the account.');
            $this->logPublishFailure($target, $post);
            return;
        }

        $this->log('Processing target for publishing', [
            'target_id' => $target->id,
            'post_id' => $target->post_id,
            'platform' => $target->platform_code,
        ]);

        try {
            $adapter = $this->adapterFactory->create($platform);
            $result = $adapter->publishPost($target, $post, $media);

            if ($result->success) {
                $target->markPublished($result->externalPostId, $result->externalPostUrl);
            } else {
                $target->markFailed($result->errorCode ?? 'PUBLISH_ERROR', $result->errorMessage ?? 'Unknown error');
                $this->logPublishFailure($target, $post);
            }
        } catch (\Throwable $e) {
            $target->markFailed('EXCEPTION', $e->getMessage());
            $this->logPublishFailure($target, $post);
        }
    }

    /**
     * Check and update post status based on target statuses.
     *
     * Call this after all targets have been processed.
     */
    public function updatePostStatusFromTargets(Post $post): void
    {
        $post->load('targets');

        $totalTargets = $post->targets->count();
        $publishedTargets = $post->targets->where('status', PostTargetStatus::PUBLISHED)->count();
        $failedTargets = $post->targets->where('status', PostTargetStatus::FAILED)->count();
        $pendingOrPublishing = $totalTargets - $publishedTargets - $failedTargets;

        // If there are still pending/publishing targets, don't update yet
        if ($pendingOrPublishing > 0) {
            return;
        }

        // All targets processed
        if ($failedTargets === 0) {
            // All succeeded
            $post->markPublished();
            $this->log('Post published successfully', ['post_id' => $post->id]);
            event(new PostPublished($post));
        } elseif ($publishedTargets === 0) {
            // All failed
            $post->markFailed();
            $this->log('Post publishing failed', ['post_id' => $post->id]);
            event(new PostFailed($post, 'All targets failed'));
        } else {
            // Partial success - mark as published but log the failures
            $post->markPublished();
            $this->log('Post published with some failures', [
                'post_id' => $post->id,
                'published_count' => $publishedTargets,
                'failed_count' => $failedTargets,
            ], 'warning');
            event(new PostPublished($post));
        }
    }

    /**
     * Log a structured publish failure for audit purposes.
     */
    private function logPublishFailure(PostTarget $target, Post $post): void
    {
        $this->log('Publishing failed for target', [
            'post_id' => $post->id,
            'target_id' => $target->id,
            'workspace_id' => $post->workspace_id,
            'platform' => $target->platform_code,
            'account_id' => $target->social_account_id,
            'error_code' => $target->error_code,
            'error_message' => $target->error_message,
            'retry_count' => $target->retry_count,
        ], 'error');
    }
}
