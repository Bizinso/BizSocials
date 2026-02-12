<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Content\Post;
use App\Models\Content\PostRevision;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\PostRevisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PostRevisionController extends Controller
{
    public function __construct(
        private readonly PostRevisionService $revisionService,
    ) {}

    /**
     * List all revisions for a post.
     */
    public function index(Request $request, Workspace $workspace, Post $post): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($post->workspace_id !== $workspace->id) {
            return $this->notFound('Post not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $revisions = $this->revisionService->list($post->id);

        return $this->success($revisions, 'Post revisions retrieved successfully');
    }

    /**
     * Show a single revision.
     */
    public function show(Request $request, PostRevision $revision): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $post = $revision->post;

        if ($post->workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Revision not found');
        }

        if (!$post->workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $revision->load('user');

        return $this->success($revision, 'Post revision retrieved successfully');
    }

    /**
     * Restore a post to a previous revision.
     */
    public function restore(Request $request, PostRevision $revision): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $post = $revision->post;

        if ($post->workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Revision not found');
        }

        if (!$post->workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->revisionService->restoreRevision($revision, $post);

        return $this->success($post->fresh(), 'Post restored to revision successfully');
    }
}
