<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inbox;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Inbox\InboxItem;
use App\Models\Inbox\InboxItemTag;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Inbox\InboxTagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class InboxTagController extends Controller
{
    public function __construct(
        private readonly InboxTagService $tagService,
    ) {}

    /**
     * List all tags for a workspace.
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
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 50),
        ];

        $tags = $this->tagService->list($workspace, $filters);

        return $this->paginated($tags, 'Inbox tags retrieved successfully');
    }

    /**
     * Create a new tag.
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
            'name' => 'required|string|max:255',
            'color' => 'sometimes|string|max:7',
        ]);

        try {
            $tag = $this->tagService->create($workspace, $validated);

            return $this->created($tag->toArray(), 'Inbox tag created successfully');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Update a tag.
     */
    public function update(Request $request, Workspace $workspace, InboxItemTag $inboxTag): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($inboxTag->workspace_id !== $workspace->id) {
            return $this->notFound('Tag not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'color' => 'sometimes|string|max:7',
        ]);

        $tag = $this->tagService->update($inboxTag, $validated);

        return $this->success($tag->toArray(), 'Inbox tag updated successfully');
    }

    /**
     * Delete a tag.
     */
    public function destroy(Request $request, Workspace $workspace, InboxItemTag $inboxTag): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($inboxTag->workspace_id !== $workspace->id) {
            return $this->notFound('Tag not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->tagService->delete($inboxTag);

        return $this->noContent();
    }

    /**
     * Attach a tag to an inbox item.
     */
    public function attach(Request $request, Workspace $workspace, InboxItem $inboxItem, InboxItemTag $tag): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($inboxItem->workspace_id !== $workspace->id) {
            return $this->notFound('Inbox item not found');
        }

        if ($tag->workspace_id !== $workspace->id) {
            return $this->notFound('Tag not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        try {
            $this->tagService->attachToItem($inboxItem, $tag);

            return $this->success(null, 'Tag attached successfully');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Detach a tag from an inbox item.
     */
    public function detach(Request $request, Workspace $workspace, InboxItem $inboxItem, InboxItemTag $tag): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($inboxItem->workspace_id !== $workspace->id) {
            return $this->notFound('Inbox item not found');
        }

        if ($tag->workspace_id !== $workspace->id) {
            return $this->notFound('Tag not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->tagService->detachFromItem($inboxItem, $tag);

        return $this->noContent();
    }
}
