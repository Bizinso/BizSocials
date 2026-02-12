<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inbox;

use App\Data\Inbox\AssignData;
use App\Data\Inbox\BulkActionData;
use App\Data\Inbox\InboxItemData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Inbox\AssignRequest;
use App\Http\Requests\Inbox\BulkActionRequest;
use App\Models\Inbox\InboxItem;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Inbox\InboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class InboxController extends Controller
{
    public function __construct(
        private readonly InboxService $inboxService,
    ) {}

    /**
     * List inbox items for a workspace.
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

        $filters = [
            'status' => $request->query('status'),
            'type' => $request->query('type'),
            'platform' => $request->query('platform'),
            'social_account_id' => $request->query('social_account_id'),
            'assigned_to' => $request->query('assigned_to'),
            'current_user_id' => $user->id,
            'search' => $request->query('search'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'per_page' => $request->query('per_page', 15),
            'sort_by' => $request->query('sort_by', 'platform_created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
        ];

        $items = $this->inboxService->list($workspace, $filters);

        // Transform paginated data
        $transformedItems = collect($items->items())->map(
            fn (InboxItem $item) => InboxItemData::fromModel($item)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Inbox items retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ],
            'links' => [
                'first' => $items->url(1),
                'last' => $items->url($items->lastPage()),
                'prev' => $items->previousPageUrl(),
                'next' => $items->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get inbox statistics for a workspace.
     */
    public function stats(Request $request, Workspace $workspace): JsonResponse
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

        $stats = $this->inboxService->getStats($workspace, $user->id);

        return $this->success($stats->toArray(), 'Inbox statistics retrieved successfully');
    }

    /**
     * Get a single inbox item.
     */
    public function show(Request $request, Workspace $workspace, InboxItem $inboxItem): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify inbox item belongs to this workspace
        if ($inboxItem->workspace_id !== $workspace->id) {
            return $this->notFound('Inbox item not found');
        }

        // Check if user has access to this workspace
        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        return $this->success(
            InboxItemData::fromModel($inboxItem)->toArray(),
            'Inbox item retrieved successfully'
        );
    }

    /**
     * Mark an inbox item as read.
     */
    public function markRead(Request $request, Workspace $workspace, InboxItem $inboxItem): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify inbox item belongs to this workspace
        if ($inboxItem->workspace_id !== $workspace->id) {
            return $this->notFound('Inbox item not found');
        }

        // Check if user has access (Viewer can do this)
        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        try {
            $item = $this->inboxService->markAsRead($inboxItem);

            return $this->success(
                InboxItemData::fromModel($item)->toArray(),
                'Inbox item marked as read'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Mark an inbox item as unread.
     */
    public function markUnread(Request $request, Workspace $workspace, InboxItem $inboxItem): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify inbox item belongs to this workspace
        if ($inboxItem->workspace_id !== $workspace->id) {
            return $this->notFound('Inbox item not found');
        }

        // Check if user has access
        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        try {
            $item = $this->inboxService->markAsUnread($inboxItem);

            return $this->success(
                InboxItemData::fromModel($item)->toArray(),
                'Inbox item marked as unread'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Resolve an inbox item.
     */
    public function resolve(Request $request, Workspace $workspace, InboxItem $inboxItem): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify inbox item belongs to this workspace
        if ($inboxItem->workspace_id !== $workspace->id) {
            return $this->notFound('Inbox item not found');
        }

        // Check if user has admin permissions (Admin or Owner)
        $role = $workspace->getMemberRole($user->id);
        $canResolve = $user->isAdmin() || ($role !== null && $role->canApproveContent());

        if (!$canResolve) {
            return $this->forbidden('You do not have permission to resolve inbox items');
        }

        try {
            $item = $this->inboxService->resolve($inboxItem, $user);

            return $this->success(
                InboxItemData::fromModel($item)->toArray(),
                'Inbox item resolved'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Unresolve (reopen) an inbox item.
     */
    public function unresolve(Request $request, Workspace $workspace, InboxItem $inboxItem): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify inbox item belongs to this workspace
        if ($inboxItem->workspace_id !== $workspace->id) {
            return $this->notFound('Inbox item not found');
        }

        // Check if user has admin permissions (Admin or Owner)
        $role = $workspace->getMemberRole($user->id);
        $canUnresolve = $user->isAdmin() || ($role !== null && $role->canApproveContent());

        if (!$canUnresolve) {
            return $this->forbidden('You do not have permission to reopen inbox items');
        }

        try {
            $item = $this->inboxService->unresolve($inboxItem);

            return $this->success(
                InboxItemData::fromModel($item)->toArray(),
                'Inbox item reopened'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Archive an inbox item.
     */
    public function archive(Request $request, Workspace $workspace, InboxItem $inboxItem): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify inbox item belongs to this workspace
        if ($inboxItem->workspace_id !== $workspace->id) {
            return $this->notFound('Inbox item not found');
        }

        // Check if user has admin permissions (Admin or Owner)
        $role = $workspace->getMemberRole($user->id);
        $canArchive = $user->isAdmin() || ($role !== null && $role->canApproveContent());

        if (!$canArchive) {
            return $this->forbidden('You do not have permission to archive inbox items');
        }

        try {
            $item = $this->inboxService->archive($inboxItem);

            return $this->success(
                InboxItemData::fromModel($item)->toArray(),
                'Inbox item archived'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Assign an inbox item to a user.
     */
    public function assign(AssignRequest $request, Workspace $workspace, InboxItem $inboxItem): JsonResponse
    {
        $data = AssignData::from($request->validated());

        // Find the user to assign to
        $assignee = User::find($data->user_id);

        if ($assignee === null) {
            return $this->notFound('User not found');
        }

        // Verify assignee belongs to the same tenant
        if ($assignee->tenant_id !== $workspace->tenant_id) {
            return $this->error('User does not belong to this organization', 422);
        }

        // Verify assignee is a member of the workspace
        if (!$workspace->hasMember($assignee->id)) {
            return $this->error('User is not a member of this workspace', 422);
        }

        $item = $this->inboxService->assign($inboxItem, $assignee);

        return $this->success(
            InboxItemData::fromModel($item)->toArray(),
            'Inbox item assigned successfully'
        );
    }

    /**
     * Unassign an inbox item.
     */
    public function unassign(Request $request, Workspace $workspace, InboxItem $inboxItem): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify inbox item belongs to this workspace
        if ($inboxItem->workspace_id !== $workspace->id) {
            return $this->notFound('Inbox item not found');
        }

        // Check if user has admin permissions (Admin or Owner)
        $role = $workspace->getMemberRole($user->id);
        $canUnassign = $user->isAdmin() || ($role !== null && $role->canApproveContent());

        if (!$canUnassign) {
            return $this->forbidden('You do not have permission to unassign inbox items');
        }

        $item = $this->inboxService->unassign($inboxItem);

        return $this->success(
            InboxItemData::fromModel($item)->toArray(),
            'Inbox item unassigned successfully'
        );
    }

    /**
     * Bulk mark inbox items as read.
     */
    public function bulkRead(BulkActionRequest $request, Workspace $workspace): JsonResponse
    {
        $data = BulkActionData::from($request->validated());

        $count = $this->inboxService->bulkMarkAsRead($workspace, $data->item_ids);

        return $this->success(
            ['updated_count' => $count],
            sprintf('%d item(s) marked as read', $count)
        );
    }

    /**
     * Bulk resolve inbox items.
     */
    public function bulkResolve(BulkActionRequest $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Check if user has admin permissions
        $role = $workspace->getMemberRole($user->id);
        $canResolve = $user->isAdmin() || ($role !== null && $role->canApproveContent());

        if (!$canResolve) {
            return $this->forbidden('You do not have permission to resolve inbox items');
        }

        $data = BulkActionData::from($request->validated());

        $count = $this->inboxService->bulkResolve($workspace, $data->item_ids, $user);

        return $this->success(
            ['updated_count' => $count],
            sprintf('%d item(s) resolved', $count)
        );
    }
}
