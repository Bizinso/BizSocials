<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Content\Post;
use App\Models\Content\PostNote;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\PostNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PostNoteController extends Controller
{
    public function __construct(
        private readonly PostNoteService $noteService,
    ) {}

    /**
     * List all notes for a post.
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

        $notes = $this->noteService->list($post->id);

        return $this->success($notes, 'Post notes retrieved successfully');
    }

    /**
     * Create a new note on a post.
     */
    public function store(Request $request, Workspace $workspace, Post $post): JsonResponse
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

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $note = $this->noteService->create($post->id, $user->id, $validated['content']);

        return $this->created($note, 'Post note created successfully');
    }

    /**
     * Delete a post note.
     */
    public function destroy(Request $request, PostNote $note): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $post = $note->post;

        if ($post->workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Note not found');
        }

        if (!$post->workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        // Only the note author or admins can delete
        if ($note->user_id !== $user->id && !$user->isAdmin()) {
            return $this->forbidden('You can only delete your own notes');
        }

        $this->noteService->delete($note);

        return $this->success(null, 'Post note deleted successfully');
    }
}
