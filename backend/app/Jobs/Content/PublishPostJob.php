<?php

declare(strict_types=1);

namespace App\Jobs\Content;

use App\Enums\Content\PostStatus;
use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationType;
use App\Models\Content\Post;
use App\Models\Notification\Notification;
use App\Services\Content\PublishingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * PublishPostJob
 *
 * Handles the publishing of a single post to its target social platforms.
 * This job is dispatched by PublishScheduledPostsJob or directly when
 * a user requests immediate publishing.
 *
 * Features:
 * - Retries up to 3 times with 60-second exponential backoff
 * - Sends success/failure notifications to the post author
 * - Updates post status based on publishing outcome
 * - Carries workspace_id for multi-tenant isolation
 */
final class PublishPostJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     * 2 = 1 initial attempt + 1 immediate retry.
     *
     * @var int
     */
    public int $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 180;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [10];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $postId,
        public readonly string $workspaceId,
    ) {
        $this->onQueue('content');
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "publish-post-{$this->postId}";
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(5);
    }

    /**
     * Execute the job.
     */
    public function handle(PublishingService $publishingService): void
    {
        Log::info('[PublishPostJob] Starting post publishing', [
            'post_id' => $this->postId,
            'workspace_id' => $this->workspaceId,
            'attempt' => $this->attempts(),
        ]);

        $post = Post::query()
            ->where('id', $this->postId)
            ->where('workspace_id', $this->workspaceId)
            ->with('targets')
            ->first();

        if ($post === null) {
            Log::warning('[PublishPostJob] Post not found', [
                'post_id' => $this->postId,
                'workspace_id' => $this->workspaceId,
            ]);
            return;
        }

        // Verify post is in PUBLISHING state (set by PublishingService before dispatch)
        if ($post->status !== PostStatus::PUBLISHING) {
            Log::warning('[PublishPostJob] Post is not in PUBLISHING state', [
                'post_id' => $this->postId,
                'current_status' => $post->status->value,
            ]);
            return;
        }

        try {
            // Process each target for publishing
            foreach ($post->targets as $target) {
                $publishingService->processTarget($target);
            }

            // Update post status from targets after publishing
            $publishingService->updatePostStatusFromTargets($post);

            // Reload to get updated status
            $post->refresh();

            if ($post->status === PostStatus::PUBLISHED) {
                $this->sendSuccessNotification($post);

                Log::info('[PublishPostJob] Post published successfully', [
                    'post_id' => $this->postId,
                    'workspace_id' => $this->workspaceId,
                ]);
            } elseif ($post->status === PostStatus::FAILED) {
                $this->sendFailureNotification($post, 'Publishing failed for all targets');
            }
        } catch (\Throwable $e) {
            Log::error('[PublishPostJob] Publishing failed with exception', [
                'post_id' => $this->postId,
                'workspace_id' => $this->workspaceId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Mark post as failed if this is the last attempt
            if ($this->attempts() >= $this->tries) {
                $post->markFailed();
                $this->sendFailureNotification($post, $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Send a success notification to the post author.
     */
    private function sendSuccessNotification(Post $post): void
    {
        $post->loadMissing('author');

        if ($post->author === null) {
            return;
        }

        try {
            Notification::createForUser(
                user: $post->author,
                type: NotificationType::POST_PUBLISHED,
                title: 'Post Published Successfully',
                message: sprintf(
                    'Your post "%s" has been published successfully.',
                    $this->getPostExcerpt($post)
                ),
                channel: NotificationChannel::IN_APP,
                data: [
                    'post_id' => $post->id,
                    'workspace_id' => $post->workspace_id,
                    'published_at' => $post->published_at?->toIso8601String(),
                ],
                actionUrl: "/workspaces/{$post->workspace_id}/posts/{$post->id}",
            );

            Log::debug('[PublishPostJob] Success notification sent', [
                'post_id' => $post->id,
                'user_id' => $post->author->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[PublishPostJob] Failed to send success notification', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send a failure notification to the post author.
     */
    private function sendFailureNotification(Post $post, string $reason): void
    {
        $post->loadMissing('author');

        if ($post->author === null) {
            return;
        }

        try {
            Notification::createForUser(
                user: $post->author,
                type: NotificationType::POST_FAILED,
                title: 'Post Publishing Failed',
                message: sprintf(
                    'Your post "%s" failed to publish. Reason: %s',
                    $this->getPostExcerpt($post),
                    $reason
                ),
                channel: NotificationChannel::IN_APP,
                data: [
                    'post_id' => $post->id,
                    'workspace_id' => $post->workspace_id,
                    'failure_reason' => $reason,
                ],
                actionUrl: "/workspaces/{$post->workspace_id}/posts/{$post->id}",
            );

            Log::debug('[PublishPostJob] Failure notification sent', [
                'post_id' => $post->id,
                'user_id' => $post->author->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[PublishPostJob] Failed to send failure notification', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get a short excerpt from the post content for notifications.
     */
    private function getPostExcerpt(Post $post): string
    {
        $content = $post->content_text ?? '';

        if (mb_strlen($content) <= 50) {
            return $content ?: 'Untitled post';
        }

        return mb_substr($content, 0, 47) . '...';
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('[PublishPostJob] Job failed permanently', [
            'post_id' => $this->postId,
            'workspace_id' => $this->workspaceId,
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);

        // Attempt to mark the post as failed
        try {
            $post = Post::find($this->postId);
            if ($post !== null && $post->status !== PostStatus::PUBLISHED) {
                $post->markFailed();
                $this->sendFailureNotification($post, $exception?->getMessage() ?? 'Unknown error');
            }
        } catch (\Throwable $e) {
            Log::error('[PublishPostJob] Failed to update post status on failure', [
                'post_id' => $this->postId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
