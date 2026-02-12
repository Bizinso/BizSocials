<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content\Post;
use App\Models\Content\PostRevision;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;

final class PostRevisionService extends BaseService
{
    /**
     * List all revisions for a post, ordered by revision number descending.
     *
     * @return Collection<int, PostRevision>
     */
    public function list(string $postId): Collection
    {
        return PostRevision::where('post_id', $postId)
            ->with('user')
            ->orderByDesc('revision_number')
            ->get();
    }

    /**
     * Create a new revision snapshot from the current post state.
     */
    public function createRevision(Post $post, string $userId, ?string $changeSummary = null): PostRevision
    {
        $latestRevisionNumber = PostRevision::where('post_id', $post->id)
            ->max('revision_number') ?? 0;

        $revision = PostRevision::create([
            'post_id' => $post->id,
            'user_id' => $userId,
            'content_text' => $post->content_text,
            'content_variations' => $post->content_variations,
            'hashtags' => $post->hashtags,
            'revision_number' => $latestRevisionNumber + 1,
            'change_summary' => $changeSummary,
            'created_at' => now(),
        ]);

        $revision->load('user');

        $this->log('Post revision created', [
            'revision_id' => $revision->id,
            'post_id' => $post->id,
            'revision_number' => $revision->revision_number,
        ]);

        return $revision;
    }

    /**
     * Get a single revision by ID.
     */
    public function getRevision(string $revisionId): PostRevision
    {
        return PostRevision::with('user')->findOrFail($revisionId);
    }

    /**
     * Restore a post to a previous revision's content.
     */
    public function restoreRevision(PostRevision $revision, Post $post): void
    {
        $this->transaction(function () use ($revision, $post) {
            $post->update([
                'content_text' => $revision->content_text,
                'content_variations' => $revision->content_variations,
                'hashtags' => $revision->hashtags,
            ]);

            $this->log('Post restored from revision', [
                'post_id' => $post->id,
                'revision_id' => $revision->id,
                'revision_number' => $revision->revision_number,
            ]);
        });
    }
}
