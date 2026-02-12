<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inbox;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Inbox\SavedReply;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Inbox\SavedReplyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SavedReplyController extends Controller
{
    public function __construct(
        private readonly SavedReplyService $savedReplyService,
    ) {}

    /**
     * List saved replies for a workspace.
     */
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $filters = [
            'category' => $request->query('category'),
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 20),
        ];

        $replies = $this->savedReplyService->list($workspace, $filters);

        return $this->paginated($replies, 'Saved replies retrieved successfully');
    }

    /**
     * Create a new saved reply.
     */
    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:5000',
            'shortcut' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:255',
        ]);

        $reply = $this->savedReplyService->create($workspace, $validated);

        return $this->created($reply->toArray(), 'Saved reply created successfully');
    }

    /**
     * Show a single saved reply.
     */
    public function show(Request $request, Workspace $workspace, SavedReply $savedReply): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($savedReply->workspace_id !== $workspace->id) {
            return $this->notFound('Saved reply not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        return $this->success($savedReply->toArray(), 'Saved reply retrieved successfully');
    }

    /**
     * Update a saved reply.
     */
    public function update(Request $request, Workspace $workspace, SavedReply $savedReply): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($savedReply->workspace_id !== $workspace->id) {
            return $this->notFound('Saved reply not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string|max:5000',
            'shortcut' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:255',
        ]);

        $reply = $this->savedReplyService->update($savedReply, $validated);

        return $this->success($reply->toArray(), 'Saved reply updated successfully');
    }

    /**
     * Delete a saved reply.
     */
    public function destroy(Request $request, Workspace $workspace, SavedReply $savedReply): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($savedReply->workspace_id !== $workspace->id) {
            return $this->notFound('Saved reply not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->savedReplyService->delete($savedReply);

        return $this->noContent();
    }
}
