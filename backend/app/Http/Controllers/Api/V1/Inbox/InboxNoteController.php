<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inbox;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Inbox\InboxInternalNote;
use App\Models\Inbox\InboxItem;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Inbox\InboxNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InboxNoteController extends Controller
{
    public function __construct(
        private readonly InboxNoteService $noteService,
    ) {}

    /**
     * List notes for an inbox item.
     */
    public function index(Request $request, Workspace $workspace, InboxItem $inboxItem): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($inboxItem->workspace_id !== $workspace->id) {
            return $this->notFound('Inbox item not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $notes = $this->noteService->list($inboxItem);

        $data = $notes->map(fn (InboxInternalNote $note) => [
            'id' => $note->id,
            'inbox_item_id' => $note->inbox_item_id,
            'user_id' => $note->user_id,
            'user_name' => $note->user?->name,
            'content' => $note->content,
            'created_at' => $note->created_at?->toISOString(),
        ])->toArray();

        return $this->success($data, 'Internal notes retrieved successfully');
    }

    /**
     * Create a new note on an inbox item.
     */
    public function store(Request $request, Workspace $workspace, InboxItem $inboxItem): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($inboxItem->workspace_id !== $workspace->id) {
            return $this->notFound('Inbox item not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $note = $this->noteService->create($inboxItem, $user, $validated);

        return $this->created([
            'id' => $note->id,
            'inbox_item_id' => $note->inbox_item_id,
            'user_id' => $note->user_id,
            'user_name' => $note->user?->name,
            'content' => $note->content,
            'created_at' => $note->created_at?->toISOString(),
        ], 'Internal note created successfully');
    }

    /**
     * Delete a note.
     */
    public function destroy(Request $request, Workspace $workspace, InboxInternalNote $note): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify the note belongs to this workspace through its inbox item
        $note->loadMissing('inboxItem');
        if ($note->inboxItem === null || $note->inboxItem->workspace_id !== $workspace->id) {
            return $this->notFound('Note not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        // Only the note author or admins can delete
        if ($note->user_id !== $user->id && !$user->isAdmin()) {
            return $this->forbidden('You can only delete your own notes');
        }

        $this->noteService->delete($note);

        return $this->noContent();
    }
}
