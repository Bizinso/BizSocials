<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\Content\PostTargetStatus;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class PostTargetService extends BaseService
{
    /**
     * List targets for a post.
     *
     * @return Collection<int, PostTarget>
     */
    public function listForPost(Post $post): Collection
    {
        return $post->targets()->with('socialAccount')->get();
    }

    /**
     * Set targets for a post (replaces existing targets).
     *
     * @param array<string> $socialAccountIds
     * @return Collection<int, PostTarget>
     * @throws ValidationException
     */
    public function setTargets(Post $post, array $socialAccountIds): Collection
    {
        if (!$post->canEdit()) {
            throw ValidationException::withMessages([
                'post' => ['Cannot modify targets for a post that cannot be edited.'],
            ]);
        }

        return $this->transaction(function () use ($post, $socialAccountIds) {
            // Validate all accounts exist and belong to the same workspace
            $accounts = SocialAccount::whereIn('id', $socialAccountIds)
                ->where('workspace_id', $post->workspace_id)
                ->get();

            if ($accounts->count() !== count($socialAccountIds)) {
                throw ValidationException::withMessages([
                    'social_account_ids' => ['One or more social accounts are invalid or not in this workspace.'],
                ]);
            }

            // Remove existing targets
            $post->targets()->delete();

            // Create new targets
            foreach ($accounts as $account) {
                $post->targets()->create([
                    'social_account_id' => $account->id,
                    'platform_code' => $account->platform->value,
                    'status' => PostTargetStatus::PENDING,
                ]);
            }

            $this->log('Post targets set', [
                'post_id' => $post->id,
                'account_count' => count($socialAccountIds),
            ]);

            return $post->targets()->with('socialAccount')->get();
        });
    }

    /**
     * Add a target to a post.
     *
     * @throws ValidationException
     */
    public function addTarget(Post $post, SocialAccount $account): PostTarget
    {
        if (!$post->canEdit()) {
            throw ValidationException::withMessages([
                'post' => ['Cannot add targets to a post that cannot be edited.'],
            ]);
        }

        // Validate account belongs to the same workspace
        if ($account->workspace_id !== $post->workspace_id) {
            throw ValidationException::withMessages([
                'social_account' => ['Social account does not belong to this workspace.'],
            ]);
        }

        // Check if target already exists
        $existingTarget = $post->targets()
            ->where('social_account_id', $account->id)
            ->first();

        if ($existingTarget !== null) {
            throw ValidationException::withMessages([
                'social_account' => ['This account is already a target for this post.'],
            ]);
        }

        return $this->transaction(function () use ($post, $account) {
            $target = $post->targets()->create([
                'social_account_id' => $account->id,
                'platform_code' => $account->platform->value,
                'status' => PostTargetStatus::PENDING,
            ]);

            $this->log('Target added to post', [
                'target_id' => $target->id,
                'post_id' => $post->id,
                'account_id' => $account->id,
            ]);

            return $target->load('socialAccount');
        });
    }

    /**
     * Remove a target from a post.
     *
     * @throws ValidationException
     */
    public function removeTarget(PostTarget $target): void
    {
        $post = $target->post;

        if (!$post->canEdit()) {
            throw ValidationException::withMessages([
                'post' => ['Cannot remove targets from a post that cannot be edited.'],
            ]);
        }

        $this->transaction(function () use ($target) {
            $targetId = $target->id;
            $postId = $target->post_id;

            $target->delete();

            $this->log('Target removed from post', [
                'target_id' => $targetId,
                'post_id' => $postId,
            ]);
        });
    }

    /**
     * Update the status of a target.
     *
     * @param array<string, mixed>|null $response Platform response data
     */
    public function updateTargetStatus(
        PostTarget $target,
        PostTargetStatus $status,
        ?array $response = null
    ): PostTarget {
        return $this->transaction(function () use ($target, $status, $response) {
            $target->status = $status;

            if ($status === PostTargetStatus::PUBLISHED && $response !== null) {
                $target->external_post_id = $response['post_id'] ?? null;
                $target->external_post_url = $response['post_url'] ?? null;
                $target->published_at = now();
                $target->error_code = null;
                $target->error_message = null;
            } elseif ($status === PostTargetStatus::FAILED && $response !== null) {
                $target->error_code = $response['error_code'] ?? 'UNKNOWN';
                $target->error_message = $response['error_message'] ?? 'Unknown error';
            }

            $target->save();

            $this->log('Target status updated', [
                'target_id' => $target->id,
                'new_status' => $status->value,
            ]);

            return $target;
        });
    }
}
