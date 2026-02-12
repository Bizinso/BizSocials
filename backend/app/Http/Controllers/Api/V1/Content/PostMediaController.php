<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Data\Content\AttachMediaData;
use App\Data\Content\PostMediaData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Content\AttachMediaRequest;
use App\Http\Requests\Content\UpdateMediaOrderRequest;
use App\Models\Content\Post;
use App\Models\Content\PostMedia;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\PostMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PostMediaController extends Controller
{
    public function __construct(
        private readonly PostMediaService $postMediaService,
    ) {}

    /**
     * List media for a post.
     */
    public function index(Request $request, Workspace $workspace, Post $post): JsonResponse
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

        $media = $this->postMediaService->listForPost($post);

        $transformedMedia = $media->map(
            fn (PostMedia $m) => PostMediaData::fromModel($m)->toArray()
        );

        return $this->success(
            $transformedMedia->all(),
            'Media retrieved successfully'
        );
    }

    /**
     * Attach media to a post.
     */
    public function store(AttachMediaRequest $request, Workspace $workspace, Post $post): JsonResponse
    {
        $data = AttachMediaData::from($request->validated());

        try {
            $media = $this->postMediaService->attach($post, $data);

            return $this->created(
                PostMediaData::fromModel($media)->toArray(),
                'Media attached successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Update the order of media items.
     */
    public function updateOrder(UpdateMediaOrderRequest $request, Workspace $workspace, Post $post): JsonResponse
    {
        $mediaOrder = $request->validated()['media_order'];

        try {
            $this->postMediaService->updateOrder($post, $mediaOrder);

            // Return the updated media list
            $media = $this->postMediaService->listForPost($post);
            $transformedMedia = $media->map(
                fn (PostMedia $m) => PostMediaData::fromModel($m)->toArray()
            );

            return $this->success(
                $transformedMedia->all(),
                'Media order updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Remove a media item from a post.
     */
    public function destroy(Request $request, Workspace $workspace, Post $post, PostMedia $media): JsonResponse
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

        // Verify media belongs to this post
        if ($media->post_id !== $post->id) {
            return $this->notFound('Media not found');
        }

        // Check permissions
        $role = $workspace->getMemberRole($user->id);
        $canEdit = $user->isAdmin() ||
            ($role !== null && $role->canApproveContent()) ||
            $post->created_by_user_id === $user->id;

        if (!$canEdit) {
            return $this->forbidden('You do not have permission to modify this post');
        }

        try {
            $this->postMediaService->remove($media);

            return $this->success(null, 'Media removed successfully');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
