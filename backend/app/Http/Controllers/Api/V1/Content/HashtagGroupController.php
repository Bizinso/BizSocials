<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Content\HashtagGroup;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\HashtagGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HashtagGroupController extends Controller
{
    public function __construct(
        private readonly HashtagGroupService $hashtagGroupService,
    ) {}

    /**
     * List hashtag groups.
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
            'per_page' => $request->query('per_page', 15),
        ];

        $groups = $this->hashtagGroupService->list($workspace->id, $filters);

        return $this->paginated($groups, 'Hashtag groups retrieved successfully');
    }

    /**
     * Create a new hashtag group.
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
            'name' => 'required|string|max:100',
            'hashtags' => 'required|array|min:1',
            'hashtags.*' => 'string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $group = $this->hashtagGroupService->create($workspace->id, $validated);

        return $this->created($group, 'Hashtag group created successfully');
    }

    /**
     * Get a single hashtag group.
     */
    public function show(Request $request, Workspace $workspace, HashtagGroup $hashtagGroup): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($hashtagGroup->workspace_id !== $workspace->id) {
            return $this->notFound('Hashtag group not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        return $this->success($hashtagGroup, 'Hashtag group retrieved successfully');
    }

    /**
     * Update a hashtag group.
     */
    public function update(Request $request, Workspace $workspace, HashtagGroup $hashtagGroup): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($hashtagGroup->workspace_id !== $workspace->id) {
            return $this->notFound('Hashtag group not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'hashtags' => 'sometimes|array|min:1',
            'hashtags.*' => 'string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $group = $this->hashtagGroupService->update($hashtagGroup, $validated);

        return $this->success($group, 'Hashtag group updated successfully');
    }

    /**
     * Delete a hashtag group.
     */
    public function destroy(Request $request, Workspace $workspace, HashtagGroup $hashtagGroup): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($hashtagGroup->workspace_id !== $workspace->id) {
            return $this->notFound('Hashtag group not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->hashtagGroupService->delete($hashtagGroup);

        return $this->success(null, 'Hashtag group deleted successfully');
    }
}
