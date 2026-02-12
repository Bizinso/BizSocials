<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content\PostNote;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;

final class PostNoteService extends BaseService
{
    /**
     * List all notes for a post.
     *
     * @return Collection<int, PostNote>
     */
    public function list(string $postId): Collection
    {
        return PostNote::where('post_id', $postId)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Create a new note on a post.
     */
    public function create(string $postId, string $userId, string $content): PostNote
    {
        $note = PostNote::create([
            'post_id' => $postId,
            'user_id' => $userId,
            'content' => $content,
        ]);

        $note->load('user');

        $this->log('Post note created', ['note_id' => $note->id, 'post_id' => $postId]);

        return $note;
    }

    /**
     * Delete a post note.
     */
    public function delete(PostNote $note): void
    {
        $note->delete();

        $this->log('Post note deleted', ['note_id' => $note->id]);
    }
}
