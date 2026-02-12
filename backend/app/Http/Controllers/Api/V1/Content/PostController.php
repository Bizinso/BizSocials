<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Data\Content\CreatePostData;
use App\Data\Content\PostData;
use App\Data\Content\PostDetailData;
use App\Data\Content\SchedulePostData;
use App\Data\Content\UpdatePostData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Content\CreatePostRequest;
use App\Http\Requests\Content\ReschedulePostRequest;
use App\Http\Requests\Content\SchedulePostRequest;
use App\Http\Requests\Content\UpdatePostRequest;
use App\Models\Content\Post;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\PostService;
use App\Services\Content\PublishingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PostController extends Controller
{
    public function __construct(
        private readonly PostService $postService,
        private readonly PublishingService $publishingService,
    ) {}

    /**
     * List posts for a workspace.
     */
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Check if user has access to this workspace
        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $filters = [
            'status' => $request->query('status'),
            'author_id' => $request->query('author_id'),
            'post_type' => $request->query('post_type'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 15),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
        ];

        $posts = $this->postService->list($workspace, $filters);

        // Transform paginated data
        $transformedItems = collect($posts->items())->map(
            fn (Post $post) => PostData::fromModel($post)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Posts retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'from' => $posts->firstItem(),
                'to' => $posts->lastItem(),
            ],
            'links' => [
                'first' => $posts->url(1),
                'last' => $posts->url($posts->lastPage()),
                'prev' => $posts->previousPageUrl(),
                'next' => $posts->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Create a new post.
     */
    public function store(CreatePostRequest $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = CreatePostData::from($request->validated());

        $post = $this->postService->create($workspace, $user, $data);

        return $this->created(
            PostDetailData::fromModel($post)->toArray(),
            'Post created successfully'
        );
    }

    /**
     * Get a single post with details.
     */
    public function show(Request $request, Workspace $workspace, Post $post): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify post belongs to this workspace
        if ($post->workspace_id !== $workspace->id) {
            return $this->notFound('Post not found');
        }

        // Check if user has access to this workspace
        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        return $this->success(
            PostDetailData::fromModel($post)->toArray(),
            'Post retrieved successfully'
        );
    }

    /**
     * Update a post.
     */
    public function update(UpdatePostRequest $request, Workspace $workspace, Post $post): JsonResponse
    {
        $data = UpdatePostData::from($request->validated());

        try {
            $updatedPost = $this->postService->update($post, $data);

            return $this->success(
                PostDetailData::fromModel($updatedPost)->toArray(),
                'Post updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Delete a post.
     */
    public function destroy(Request $request, Workspace $workspace, Post $post): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify post belongs to this workspace
        if ($post->workspace_id !== $workspace->id) {
            return $this->notFound('Post not found');
        }

        // Check permissions
        $role = $workspace->getMemberRole($user->id);
        $canDelete = $user->isAdmin() ||
            ($role !== null && $role->canApproveContent()) ||
            $post->created_by_user_id === $user->id;

        if (!$canDelete) {
            return $this->forbidden('You do not have permission to delete this post');
        }

        try {
            $this->postService->delete($post);

            return $this->success(null, 'Post deleted successfully');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Submit a post for approval.
     */
    public function submit(Request $request, Workspace $workspace, Post $post): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify post belongs to this workspace
        if ($post->workspace_id !== $workspace->id) {
            return $this->notFound('Post not found');
        }

        // Check permissions - author or admins can submit
        $role = $workspace->getMemberRole($user->id);
        $canSubmit = $user->isAdmin() ||
            ($role !== null && $role->canCreateContent() && $post->created_by_user_id === $user->id) ||
            ($role !== null && $role->canApproveContent());

        if (!$canSubmit) {
            return $this->forbidden('You do not have permission to submit this post');
        }

        try {
            $submittedPost = $this->postService->submit($post);

            return $this->success(
                PostDetailData::fromModel($submittedPost)->toArray(),
                'Post submitted for approval'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Schedule a post.
     */
    public function schedule(SchedulePostRequest $request, Workspace $workspace, Post $post): JsonResponse
    {
        $data = SchedulePostData::from($request->validated());

        try {
            $scheduledAt = $data->timezone
                ? Carbon::parse($data->scheduled_at, $data->timezone)->utc()
                : Carbon::parse($data->scheduled_at);
            $scheduledPost = $this->postService->schedule($post, $scheduledAt, $data->timezone);

            return $this->success(
                PostDetailData::fromModel($scheduledPost)->toArray(),
                'Post scheduled successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Reschedule a post that is already scheduled.
     */
    public function reschedule(ReschedulePostRequest $request, Workspace $workspace, Post $post): JsonResponse
    {
        $data = SchedulePostData::from($request->validated());

        try {
            $scheduledAt = $data->timezone
                ? Carbon::parse($data->scheduled_at, $data->timezone)->utc()
                : Carbon::parse($data->scheduled_at);
            $rescheduledPost = $this->postService->reschedule($post, $scheduledAt, $data->timezone);

            return $this->success(
                PostDetailData::fromModel($rescheduledPost)->toArray(),
                'Post rescheduled successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Publish a post immediately.
     */
    public function publish(Request $request, Workspace $workspace, Post $post): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify post belongs to this workspace
        if ($post->workspace_id !== $workspace->id) {
            return $this->notFound('Post not found');
        }

        // Check permissions - only admins/owners can publish
        $role = $workspace->getMemberRole($user->id);
        $canPublish = $user->isAdmin() || ($role !== null && $role->canPublishDirectly());

        if (!$canPublish) {
            return $this->forbidden('You do not have permission to publish this post');
        }

        try {
            $this->publishingService->publishNow($post);
            $post->refresh();

            return $this->success(
                PostDetailData::fromModel($post)->toArray(),
                'Post publishing initiated'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Cancel a post.
     */
    public function cancel(Request $request, Workspace $workspace, Post $post): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify post belongs to this workspace
        if ($post->workspace_id !== $workspace->id) {
            return $this->notFound('Post not found');
        }

        // Check permissions
        $role = $workspace->getMemberRole($user->id);
        $canCancel = $user->isAdmin() ||
            ($role !== null && $role->canApproveContent()) ||
            $post->created_by_user_id === $user->id;

        if (!$canCancel) {
            return $this->forbidden('You do not have permission to cancel this post');
        }

        try {
            $cancelledPost = $this->postService->cancel($post);

            return $this->success(
                PostDetailData::fromModel($cancelledPost)->toArray(),
                'Post cancelled successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Duplicate a post.
     */
    public function duplicate(Request $request, Workspace $workspace, Post $post): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify post belongs to this workspace
        if ($post->workspace_id !== $workspace->id) {
            return $this->notFound('Post not found');
        }

        // Check if user has access to this workspace
        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        // Check if user can create content
        $role = $workspace->getMemberRole($user->id);
        $canCreate = $user->isAdmin() || ($role !== null && $role->canCreateContent());

        if (!$canCreate) {
            return $this->forbidden('You do not have permission to duplicate this post');
        }

        $duplicatedPost = $this->postService->duplicate($post, $user);

        return $this->created(
            PostDetailData::fromModel($duplicatedPost)->toArray(),
            'Post duplicated successfully'
        );
    }
}
