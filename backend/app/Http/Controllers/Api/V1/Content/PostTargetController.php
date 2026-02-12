<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Data\Content\PostTargetData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Content\SetTargetsRequest;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\PostTargetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PostTargetController extends Controller
{
    public function __construct(
        private readonly PostTargetService $postTargetService,
    ) {}

    /**
     * List targets for a post.
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

        $targets = $this->postTargetService->listForPost($post);

        $transformedTargets = $targets->map(
            fn (PostTarget $t) => PostTargetData::fromModel($t)->toArray()
        );

        return $this->success(
            $transformedTargets->all(),
            'Targets retrieved successfully'
        );
    }

    /**
     * Set targets for a post (replace all).
     */
    public function update(SetTargetsRequest $request, Workspace $workspace, Post $post): JsonResponse
    {
        $socialAccountIds = $request->validated()['social_account_ids'];

        try {
            $targets = $this->postTargetService->setTargets($post, $socialAccountIds);

            $transformedTargets = $targets->map(
                fn (PostTarget $t) => PostTargetData::fromModel($t)->toArray()
            );

            return $this->success(
                $transformedTargets->all(),
                'Targets updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Remove a target from a post.
     */
    public function destroy(Request $request, Workspace $workspace, Post $post, PostTarget $target): JsonResponse
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

        // Verify target belongs to this post
        if ($target->post_id !== $post->id) {
            return $this->notFound('Target not found');
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
            $this->postTargetService->removeTarget($target);

            return $this->success(null, 'Target removed successfully');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
