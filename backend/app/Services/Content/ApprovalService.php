<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\Content\ApprovalDecisionType;
use App\Enums\Content\PostStatus;
use App\Enums\Workspace\WorkspaceRole;
use App\Events\Content\PostApproved;
use App\Events\Content\PostRejected;
use App\Models\Content\ApprovalDecision;
use App\Models\Content\Post;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class ApprovalService extends BaseService
{
    /**
     * Get posts pending approval for a workspace.
     *
     * @return Collection<int, Post>
     */
    public function getPendingForWorkspace(Workspace $workspace): Collection
    {
        return Post::forWorkspace($workspace->id)
            ->withStatus(PostStatus::SUBMITTED)
            ->with(['author', 'targets.socialAccount', 'media'])
            ->orderBy('submitted_at', 'asc')
            ->get();
    }

    /**
     * Approve a post.
     *
     * @throws ValidationException
     */
    public function approve(Post $post, User $approver, ?string $comment = null): ApprovalDecision
    {
        if (!$post->status->canTransitionTo(PostStatus::APPROVED)) {
            throw ValidationException::withMessages([
                'post' => ['Post cannot be approved from its current status.'],
            ]);
        }

        return $this->transaction(function () use ($post, $approver, $comment) {
            // Deactivate any previous active decisions
            $post->approvalDecisions()
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Create the approval decision
            $decision = ApprovalDecision::create([
                'post_id' => $post->id,
                'decided_by_user_id' => $approver->id,
                'decision' => ApprovalDecisionType::APPROVED,
                'comment' => $comment,
                'is_active' => true,
                'decided_at' => now(),
            ]);

            // Update post status
            $post->approve();

            $this->log('Post approved', [
                'post_id' => $post->id,
                'approver_id' => $approver->id,
                'decision_id' => $decision->id,
            ]);

            event(new PostApproved($post, $approver));

            return $decision->load('decidedBy');
        });
    }

    /**
     * Reject a post.
     *
     * @throws ValidationException
     */
    public function reject(Post $post, User $approver, string $reason, ?string $comment = null): ApprovalDecision
    {
        if (!$post->status->canTransitionTo(PostStatus::REJECTED)) {
            throw ValidationException::withMessages([
                'post' => ['Post cannot be rejected from its current status.'],
            ]);
        }

        return $this->transaction(function () use ($post, $approver, $reason, $comment) {
            // Deactivate any previous active decisions
            $post->approvalDecisions()
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Create the rejection decision
            $decision = ApprovalDecision::create([
                'post_id' => $post->id,
                'decided_by_user_id' => $approver->id,
                'decision' => ApprovalDecisionType::REJECTED,
                'comment' => $comment,
                'is_active' => true,
                'decided_at' => now(),
            ]);

            // Update post status with rejection reason
            $post->reject($reason);

            $this->log('Post rejected', [
                'post_id' => $post->id,
                'approver_id' => $approver->id,
                'decision_id' => $decision->id,
                'reason' => $reason,
            ]);

            event(new PostRejected($post, $approver, $reason));

            return $decision->load('decidedBy');
        });
    }

    /**
     * Get the decision history for a post.
     *
     * @return Collection<int, ApprovalDecision>
     */
    public function getDecisionHistory(Post $post): Collection
    {
        return $post->approvalDecisions()
            ->with('decidedBy')
            ->orderByDesc('decided_at')
            ->get();
    }

    /**
     * Check if a user can approve/reject posts in a workspace.
     */
    public function canUserApprove(User $user, Post $post): bool
    {
        $workspace = $post->workspace;

        // User must be in the same tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Tenant admins can approve
        if ($user->isAdmin()) {
            return true;
        }

        // Check workspace role
        $role = $workspace->getMemberRole($user->id);

        if ($role === null) {
            return false;
        }

        return $role->canApproveContent();
    }

    /**
     * Validate that a user can approve/reject a post.
     *
     * @throws ValidationException
     */
    public function validateCanApprove(User $user, Post $post): void
    {
        if (!$this->canUserApprove($user, $post)) {
            throw ValidationException::withMessages([
                'permission' => ['You do not have permission to approve or reject this post.'],
            ]);
        }
    }
}
