<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Data\Content\ApprovalDecisionData;
use App\Data\Content\ApprovePostData;
use App\Data\Content\PostData;
use App\Data\Content\RejectPostData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Content\ApprovePostRequest;
use App\Http\Requests\Content\RejectPostRequest;
use App\Models\Content\ApprovalDecision;
use App\Models\Content\Post;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\ApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class ApprovalController extends Controller
{
    public function __construct(
        private readonly ApprovalService $approvalService,
    ) {}

    /**
     * List posts pending approval for a workspace.
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

        // Check if user can approve content
        $role = $workspace->getMemberRole($user->id);
        $canApprove = $user->isAdmin() || ($role !== null && $role->canApproveContent());

        if (!$canApprove) {
            return $this->forbidden('You do not have permission to view pending approvals');
        }

        $pendingPosts = $this->approvalService->getPendingForWorkspace($workspace);

        $transformedPosts = $pendingPosts->map(
            fn (Post $p) => PostData::fromModel($p)->toArray()
        );

        return $this->success(
            $transformedPosts->all(),
            'Pending approvals retrieved successfully'
        );
    }

    /**
     * Approve a post.
     */
    public function approve(ApprovePostRequest $request, Workspace $workspace, Post $post): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = ApprovePostData::from($request->validated());

        try {
            $decision = $this->approvalService->approve($post, $user, $data->comment);

            return $this->success(
                ApprovalDecisionData::fromModel($decision)->toArray(),
                'Post approved successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Reject a post.
     */
    public function reject(RejectPostRequest $request, Workspace $workspace, Post $post): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = RejectPostData::from($request->validated());

        try {
            $decision = $this->approvalService->reject($post, $user, $data->reason, $data->comment);

            return $this->success(
                ApprovalDecisionData::fromModel($decision)->toArray(),
                'Post rejected'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Get approval history for a post.
     */
    public function history(Request $request, Workspace $workspace, Post $post): JsonResponse
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

        $decisions = $this->approvalService->getDecisionHistory($post);

        $transformedDecisions = $decisions->map(
            fn (ApprovalDecision $d) => ApprovalDecisionData::fromModel($d)->toArray()
        );

        return $this->success(
            $transformedDecisions->all(),
            'Approval history retrieved successfully'
        );
    }
}
