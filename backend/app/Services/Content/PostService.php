<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Data\Content\CreatePostData;
use App\Data\Content\UpdatePostData;
use App\Enums\Content\PostStatus;
use App\Enums\Content\PostTargetStatus;
use App\Events\Content\PostSubmittedForApproval;
use App\Models\Content\Post;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class PostService extends BaseService
{
    public function __construct(
        private readonly PostTargetService $postTargetService,
    ) {}

    /**
     * List posts for a workspace with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function list(Workspace $workspace, array $filters = []): LengthAwarePaginator
    {
        $query = Post::forWorkspace($workspace->id)
            ->with(['author', 'targets', 'media']);

        // Filter by status
        if (!empty($filters['status'])) {
            $status = PostStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->withStatus($status);
            }
        }

        // Filter by author
        if (!empty($filters['author_id'])) {
            $query->where('created_by_user_id', $filters['author_id']);
        }

        // Filter by post type
        if (!empty($filters['post_type'])) {
            $query->where('post_type', $filters['post_type']);
        }

        // Filter by date range
        if (!empty($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        // Search in content
        if (!empty($filters['search'])) {
            $query->where('content_text', 'like', '%' . $filters['search'] . '%');
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = min($perPage, 100);

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    /**
     * Create a new post.
     */
    public function create(Workspace $workspace, User $author, CreatePostData $data): Post
    {
        return $this->transaction(function () use ($workspace, $author, $data) {
            $post = Post::create([
                'workspace_id' => $workspace->id,
                'created_by_user_id' => $author->id,
                'content_text' => $data->content_text,
                'content_variations' => $data->content_variations,
                'post_type' => $data->post_type,
                'status' => PostStatus::DRAFT,
                'hashtags' => $data->hashtags,
                'mentions' => $data->mentions,
                'link_url' => $data->link_url,
                'first_comment' => $data->first_comment,
            ]);

            // Add targets if provided
            if (!empty($data->social_account_ids)) {
                $this->postTargetService->setTargets($post, $data->social_account_ids);
            }

            $this->log('Post created', [
                'post_id' => $post->id,
                'workspace_id' => $workspace->id,
                'author_id' => $author->id,
            ]);

            return $post->fresh(['author', 'targets.socialAccount', 'media']);
        });
    }

    /**
     * Get a post by ID.
     *
     * @throws ModelNotFoundException
     */
    public function get(string $postId): Post
    {
        $post = Post::with(['author', 'targets.socialAccount', 'media'])->find($postId);

        if ($post === null) {
            throw new ModelNotFoundException('Post not found.');
        }

        return $post;
    }

    /**
     * Get a post by ID within a workspace.
     *
     * @throws ValidationException
     */
    public function getByWorkspace(Workspace $workspace, string $postId): Post
    {
        $post = Post::forWorkspace($workspace->id)
            ->with(['author', 'targets.socialAccount', 'media'])
            ->where('id', $postId)
            ->first();

        if ($post === null) {
            throw ValidationException::withMessages([
                'post' => ['Post not found.'],
            ]);
        }

        return $post;
    }

    /**
     * Update a post.
     *
     * @throws ValidationException
     */
    public function update(Post $post, UpdatePostData $data): Post
    {
        if (!$post->canEdit()) {
            throw ValidationException::withMessages([
                'post' => ['Post cannot be edited in its current status.'],
            ]);
        }

        return $this->transaction(function () use ($post, $data) {
            $updateData = array_filter([
                'content_text' => $data->content_text,
                'content_variations' => $data->content_variations,
                'hashtags' => $data->hashtags,
                'mentions' => $data->mentions,
                'link_url' => $data->link_url,
                'first_comment' => $data->first_comment,
            ], fn ($value) => $value !== null);

            // If post was rejected, move it back to draft when editing
            if ($post->status === PostStatus::REJECTED) {
                $updateData['status'] = PostStatus::DRAFT;
                $updateData['rejection_reason'] = null;
            }

            $post->update($updateData);

            $this->log('Post updated', [
                'post_id' => $post->id,
            ]);

            return $post->fresh(['author', 'targets.socialAccount', 'media']);
        });
    }

    /**
     * Delete a post.
     *
     * @throws ValidationException
     */
    public function delete(Post $post): void
    {
        if (!$post->canDelete()) {
            throw ValidationException::withMessages([
                'post' => ['Post cannot be deleted in its current status.'],
            ]);
        }

        $this->transaction(function () use ($post) {
            $postId = $post->id;

            // Soft delete the post (cascade will handle targets and media)
            $post->delete();

            $this->log('Post deleted', [
                'post_id' => $postId,
            ]);
        });
    }

    /**
     * Submit a post for approval.
     *
     * @throws ValidationException
     */
    public function submit(Post $post): Post
    {
        $this->validatePostHasContent($post);
        $this->validatePostHasTargets($post);

        if (!$post->status->canTransitionTo(PostStatus::SUBMITTED)) {
            throw ValidationException::withMessages([
                'post' => ['Post cannot be submitted from its current status.'],
            ]);
        }

        $post->submit();

        $this->log('Post submitted for approval', [
            'post_id' => $post->id,
        ]);

        $post->loadMissing('author');
        event(new PostSubmittedForApproval($post, $post->author));

        return $post->fresh(['author', 'targets.socialAccount', 'media']);
    }

    /**
     * Schedule a post for publishing.
     *
     * @throws ValidationException
     */
    public function schedule(Post $post, \DateTimeInterface $scheduledAt, ?string $timezone = null): Post
    {
        if (!$post->status->canTransitionTo(PostStatus::SCHEDULED)) {
            throw ValidationException::withMessages([
                'post' => ['Post cannot be scheduled from its current status.'],
            ]);
        }

        $this->validatePostHasContent($post);
        $this->validatePostHasTargets($post);

        // Ensure scheduled time is in the future
        if ($scheduledAt <= now()) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['Scheduled time must be in the future.'],
            ]);
        }

        $post->schedule($scheduledAt, $timezone);

        $this->log('Post scheduled', [
            'post_id' => $post->id,
            'scheduled_at' => $scheduledAt->format('c'),
            'timezone' => $timezone,
        ]);

        return $post->fresh(['author', 'targets.socialAccount', 'media']);
    }

    /**
     * Reschedule a post that is already scheduled.
     *
     * Updates the scheduled_at time without changing the post status.
     *
     * @throws ValidationException
     */
    public function reschedule(Post $post, \DateTimeInterface $scheduledAt, ?string $timezone = null): Post
    {
        if ($post->status !== PostStatus::SCHEDULED) {
            throw ValidationException::withMessages([
                'post' => ['Only scheduled posts can be rescheduled.'],
            ]);
        }

        if ($scheduledAt <= now()) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['Scheduled time must be in the future.'],
            ]);
        }

        $post->scheduled_at = $scheduledAt;
        if ($timezone !== null) {
            $post->scheduled_timezone = $timezone;
        }
        $post->save();

        $this->log('Post rescheduled', [
            'post_id' => $post->id,
            'scheduled_at' => $scheduledAt->format('c'),
            'timezone' => $timezone,
        ]);

        return $post->fresh(['author', 'targets.socialAccount', 'media']);
    }

    /**
     * Cancel a post.
     *
     * @throws ValidationException
     */
    public function cancel(Post $post): Post
    {
        if (!$post->status->canTransitionTo(PostStatus::CANCELLED)) {
            throw ValidationException::withMessages([
                'post' => ['Post cannot be cancelled from its current status.'],
            ]);
        }

        $post->cancel();

        $this->log('Post cancelled', [
            'post_id' => $post->id,
        ]);

        return $post->fresh(['author', 'targets.socialAccount', 'media']);
    }

    /**
     * Duplicate a post.
     */
    public function duplicate(Post $post, User $user): Post
    {
        return $this->transaction(function () use ($post, $user) {
            $newPost = Post::create([
                'workspace_id' => $post->workspace_id,
                'created_by_user_id' => $user->id,
                'content_text' => $post->content_text,
                'content_variations' => $post->content_variations,
                'post_type' => $post->post_type,
                'status' => PostStatus::DRAFT,
                'hashtags' => $post->hashtags,
                'mentions' => $post->mentions,
                'link_url' => $post->link_url,
                'first_comment' => $post->first_comment,
                'metadata' => array_merge($post->metadata ?? [], [
                    'duplicated_from' => $post->id,
                ]),
            ]);

            // Duplicate targets
            foreach ($post->targets as $target) {
                $newPost->targets()->create([
                    'social_account_id' => $target->social_account_id,
                    'platform_code' => $target->platform_code,
                    'content_override' => $target->content_override,
                    'status' => PostTargetStatus::PENDING,
                ]);
            }

            // Duplicate media
            foreach ($post->media as $media) {
                $newPost->media()->create([
                    'type' => $media->type,
                    'file_name' => $media->file_name,
                    'file_size' => $media->file_size,
                    'mime_type' => $media->mime_type,
                    'storage_path' => $media->storage_path,
                    'cdn_url' => $media->cdn_url,
                    'thumbnail_url' => $media->thumbnail_url,
                    'dimensions' => $media->dimensions,
                    'duration_seconds' => $media->duration_seconds,
                    'alt_text' => $media->alt_text,
                    'sort_order' => $media->sort_order,
                    'processing_status' => $media->processing_status,
                    'metadata' => $media->metadata,
                ]);
            }

            $this->log('Post duplicated', [
                'original_post_id' => $post->id,
                'new_post_id' => $newPost->id,
                'user_id' => $user->id,
            ]);

            return $newPost->fresh(['author', 'targets.socialAccount', 'media']);
        });
    }

    /**
     * Validate that post has content (text or media).
     *
     * @throws ValidationException
     */
    private function validatePostHasContent(Post $post): void
    {
        if (empty($post->content_text) && $post->media()->count() === 0) {
            throw ValidationException::withMessages([
                'content' => ['Post must have either content text or media.'],
            ]);
        }
    }

    /**
     * Validate that post has at least one target.
     *
     * @throws ValidationException
     */
    private function validatePostHasTargets(Post $post): void
    {
        if ($post->targets()->count() === 0) {
            throw ValidationException::withMessages([
                'targets' => ['Post must have at least one target account.'],
            ]);
        }
    }
}
