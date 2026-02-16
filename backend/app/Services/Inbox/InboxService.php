<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Data\Inbox\InboxStatsData;
use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Inbox\InboxItemType;
use App\Enums\Social\SocialPlatform;
use App\Models\Inbox\InboxItem;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;
use App\Services\Notification\NotificationBroadcastService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class InboxService extends BaseService
{
    public function __construct(
        private readonly NotificationBroadcastService $broadcastService,
        private readonly InboxNotificationService $inboxNotificationService,
    ) {
    }

    /**
     * List inbox items for a workspace with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function list(Workspace $workspace, array $filters = []): LengthAwarePaginator
    {
        $query = InboxItem::forWorkspace($workspace->id)
            ->with(['socialAccount', 'assignedTo', 'resolvedBy']);

        // Filter by status
        if (!empty($filters['status'])) {
            $status = InboxItemStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->withStatus($status);
            }
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $type = InboxItemType::tryFrom($filters['type']);
            if ($type !== null) {
                $query->ofType($type);
            }
        }

        // Filter by platform (through social account)
        if (!empty($filters['platform'])) {
            $platform = SocialPlatform::tryFrom($filters['platform']);
            if ($platform !== null) {
                $query->whereHas('socialAccount', function ($q) use ($platform): void {
                    $q->where('platform', $platform);
                });
            }
        }

        // Filter by social account
        if (!empty($filters['social_account_id'])) {
            $query->where('social_account_id', $filters['social_account_id']);
        }

        // Filter by assigned user
        if (!empty($filters['assigned_to'])) {
            if ($filters['assigned_to'] === 'me' && !empty($filters['current_user_id'])) {
                $query->assignedToUser($filters['current_user_id']);
            } elseif ($filters['assigned_to'] !== 'me') {
                $query->assignedToUser($filters['assigned_to']);
            }
        }

        // Search in content and author name
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('content_text', 'like', '%' . $search . '%')
                    ->orWhere('author_name', 'like', '%' . $search . '%')
                    ->orWhere('author_username', 'like', '%' . $search . '%');
            });
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $query->where('platform_created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('platform_created_at', '<=', $filters['date_to']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = min($perPage, 100);

        $sortBy = $filters['sort_by'] ?? 'platform_created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    /**
     * Get an inbox item by ID.
     *
     * @throws ModelNotFoundException
     */
    public function get(string $id): InboxItem
    {
        $item = InboxItem::with(['socialAccount', 'assignedTo', 'resolvedBy', 'replies.repliedBy'])
            ->find($id);

        if ($item === null) {
            throw new ModelNotFoundException('Inbox item not found.');
        }

        return $item;
    }

    /**
     * Get an inbox item by ID within a workspace.
     *
     * @throws ValidationException
     */
    public function getByWorkspace(Workspace $workspace, string $id): InboxItem
    {
        $item = InboxItem::forWorkspace($workspace->id)
            ->with(['socialAccount', 'assignedTo', 'resolvedBy', 'replies.repliedBy'])
            ->where('id', $id)
            ->first();

        if ($item === null) {
            throw ValidationException::withMessages([
                'inbox_item' => ['Inbox item not found.'],
            ]);
        }

        return $item;
    }

    /**
     * Mark an inbox item as read.
     *
     * @throws ValidationException
     */
    public function markAsRead(InboxItem $item): InboxItem
    {
        if ($item->status === InboxItemStatus::ARCHIVED) {
            throw ValidationException::withMessages([
                'status' => ['Archived items cannot be marked as read.'],
            ]);
        }

        if ($item->status === InboxItemStatus::UNREAD) {
            $item->markAsRead();

            $this->log('Inbox item marked as read', [
                'item_id' => $item->id,
            ]);
        }

        return $item->fresh(['socialAccount', 'assignedTo', 'resolvedBy']) ?? $item;
    }

    /**
     * Mark an inbox item as unread.
     *
     * @throws ValidationException
     */
    public function markAsUnread(InboxItem $item): InboxItem
    {
        if ($item->status === InboxItemStatus::ARCHIVED) {
            throw ValidationException::withMessages([
                'status' => ['Archived items cannot be marked as unread.'],
            ]);
        }

        if ($item->status === InboxItemStatus::READ) {
            $item->status = InboxItemStatus::UNREAD;
            $item->save();

            $this->log('Inbox item marked as unread', [
                'item_id' => $item->id,
            ]);
        }

        return $item->fresh(['socialAccount', 'assignedTo', 'resolvedBy']) ?? $item;
    }

    /**
     * Resolve an inbox item.
     *
     * @throws ValidationException
     */
    public function resolve(InboxItem $item, User $user): InboxItem
    {
        if ($item->status === InboxItemStatus::ARCHIVED) {
            throw ValidationException::withMessages([
                'status' => ['Archived items cannot be resolved.'],
            ]);
        }

        if ($item->status === InboxItemStatus::UNREAD) {
            throw ValidationException::withMessages([
                'status' => ['Unread items must be marked as read first.'],
            ]);
        }

        if ($item->status !== InboxItemStatus::RESOLVED) {
            $item->markAsResolved($user);

            $this->log('Inbox item resolved', [
                'item_id' => $item->id,
                'resolved_by' => $user->id,
            ]);
        }

        return $item->fresh(['socialAccount', 'assignedTo', 'resolvedBy']) ?? $item;
    }

    /**
     * Unresolve an inbox item (reopen).
     *
     * @throws ValidationException
     */
    public function unresolve(InboxItem $item): InboxItem
    {
        if ($item->status === InboxItemStatus::ARCHIVED) {
            throw ValidationException::withMessages([
                'status' => ['Archived items cannot be reopened.'],
            ]);
        }

        if ($item->status === InboxItemStatus::RESOLVED) {
            $item->reopen();

            $this->log('Inbox item reopened', [
                'item_id' => $item->id,
            ]);
        }

        return $item->fresh(['socialAccount', 'assignedTo', 'resolvedBy']) ?? $item;
    }

    /**
     * Archive an inbox item.
     *
     * @throws ValidationException
     */
    public function archive(InboxItem $item): InboxItem
    {
        if ($item->status === InboxItemStatus::ARCHIVED) {
            throw ValidationException::withMessages([
                'status' => ['Item is already archived.'],
            ]);
        }

        $item->archive();

        $this->log('Inbox item archived', [
            'item_id' => $item->id,
        ]);

        return $item->fresh(['socialAccount', 'assignedTo', 'resolvedBy']) ?? $item;
    }

    /**
     * Assign an inbox item to a user.
     */
    public function assign(InboxItem $item, User $user, User $assignedBy): InboxItem
    {
        $item->assignTo($user);

        // Broadcast the assignment event for real-time updates
        $this->broadcastService->broadcastInboxMessageAssigned(
            $item,
            $user,
            $assignedBy
        );

        // Send notification to the assigned user
        $this->inboxNotificationService->notifyMessageAssigned($item, $user, $assignedBy);

        $this->log('Inbox item assigned', [
            'item_id' => $item->id,
            'assigned_to' => $user->id,
            'assigned_by' => $assignedBy->id,
        ]);

        return $item->fresh(['socialAccount', 'assignedTo', 'resolvedBy']) ?? $item;
    }

    /**
     * Unassign an inbox item.
     */
    public function unassign(InboxItem $item): InboxItem
    {
        $item->unassign();

        $this->log('Inbox item unassigned', [
            'item_id' => $item->id,
        ]);

        return $item->fresh(['socialAccount', 'assignedTo', 'resolvedBy']) ?? $item;
    }

    /**
     * Get inbox statistics for a workspace.
     */
    public function getStats(Workspace $workspace, ?string $currentUserId = null): InboxStatsData
    {
        $baseQuery = InboxItem::forWorkspace($workspace->id);

        $total = (clone $baseQuery)->count();
        $unread = (clone $baseQuery)->withStatus(InboxItemStatus::UNREAD)->count();
        $read = (clone $baseQuery)->withStatus(InboxItemStatus::READ)->count();
        $resolved = (clone $baseQuery)->withStatus(InboxItemStatus::RESOLVED)->count();
        $archived = (clone $baseQuery)->withStatus(InboxItemStatus::ARCHIVED)->count();

        $assignedToMe = 0;
        if ($currentUserId !== null) {
            $assignedToMe = (clone $baseQuery)->assignedToUser($currentUserId)->count();
        }

        // Count by type
        $byType = [];
        foreach (InboxItemType::cases() as $type) {
            $byType[$type->value] = (clone $baseQuery)->ofType($type)->count();
        }

        // Count by platform (through social accounts)
        $byPlatform = [];
        foreach (SocialPlatform::cases() as $platform) {
            $count = (clone $baseQuery)
                ->whereHas('socialAccount', function ($q) use ($platform): void {
                    $q->where('platform', $platform);
                })
                ->count();
            $byPlatform[$platform->value] = $count;
        }

        return new InboxStatsData(
            total: $total,
            unread: $unread,
            read: $read,
            resolved: $resolved,
            archived: $archived,
            assigned_to_me: $assignedToMe,
            by_type: $byType,
            by_platform: $byPlatform,
        );
    }

    /**
     * Bulk mark inbox items as read.
     *
     * @param array<string> $itemIds
     */
    public function bulkMarkAsRead(Workspace $workspace, array $itemIds): int
    {
        $count = InboxItem::forWorkspace($workspace->id)
            ->whereIn('id', $itemIds)
            ->where('status', InboxItemStatus::UNREAD)
            ->update(['status' => InboxItemStatus::READ]);

        $this->log('Bulk mark as read', [
            'workspace_id' => $workspace->id,
            'item_count' => $count,
        ]);

        return $count;
    }

    /**
     * Bulk resolve inbox items.
     *
     * @param array<string> $itemIds
     */
    public function bulkResolve(Workspace $workspace, array $itemIds, User $user): int
    {
        $count = InboxItem::forWorkspace($workspace->id)
            ->whereIn('id', $itemIds)
            ->where('status', InboxItemStatus::READ)
            ->update([
                'status' => InboxItemStatus::RESOLVED,
                'resolved_at' => now(),
                'resolved_by_user_id' => $user->id,
            ]);

        $this->log('Bulk resolve', [
            'workspace_id' => $workspace->id,
            'resolved_by' => $user->id,
            'item_count' => $count,
        ]);

        return $count;
    }

    /**
     * Bulk archive inbox items.
     *
     * @param array<string> $itemIds
     */
    public function bulkArchive(Workspace $workspace, array $itemIds): int
    {
        $count = InboxItem::forWorkspace($workspace->id)
            ->whereIn('id', $itemIds)
            ->whereNot('status', InboxItemStatus::ARCHIVED)
            ->update(['status' => InboxItemStatus::ARCHIVED]);

        $this->log('Bulk archive', [
            'workspace_id' => $workspace->id,
            'item_count' => $count,
        ]);

        return $count;
    }
}
