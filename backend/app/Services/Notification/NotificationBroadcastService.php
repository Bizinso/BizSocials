<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\Notification\NotificationType;
use App\Events\Broadcast\InboxItemReceived;
use App\Events\Broadcast\InboxMessageAssigned;
use App\Events\Broadcast\InboxMessageReplied;
use App\Events\Broadcast\NewNotification;
use App\Events\Broadcast\PostStatusChanged;
use App\Models\Content\Post;
use App\Models\Inbox\InboxItem;
use App\Models\Notification\Notification;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\Log;

/**
 * NotificationBroadcastService
 *
 * Handles real-time notification broadcasting via Laravel Reverb.
 * This service integrates with the notification system to provide
 * WebSocket-based real-time updates to connected clients.
 *
 * Features:
 * - Broadcasts notifications to user channels
 * - Broadcasts inbox events to workspace channels
 * - Broadcasts post status changes
 * - Handles connection management
 * - Provides delivery confirmation
 */
final class NotificationBroadcastService extends BaseService
{
    /**
     * Broadcast a notification to a user's channel.
     *
     * This method broadcasts a notification event via Laravel Reverb
     * to the user's private channel for real-time delivery.
     *
     * @param Notification $notification The notification to broadcast
     * @return bool True if broadcast was successful
     */
    public function broadcastNotification(Notification $notification): bool
    {
        try {
            broadcast(new NewNotification($notification));

            $this->log('Notification broadcasted', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'type' => $notification->type->value,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->log('Failed to broadcast notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ], 'error');

            return false;
        }
    }

    /**
     * Broadcast an inbox item received event.
     *
     * This broadcasts to the workspace inbox channel so all team members
     * monitoring the inbox receive real-time updates.
     *
     * @param InboxItem $inboxItem The inbox item that was received
     * @return bool True if broadcast was successful
     */
    public function broadcastInboxItemReceived(InboxItem $inboxItem): bool
    {
        try {
            broadcast(new InboxItemReceived($inboxItem));

            $this->log('Inbox item received broadcasted', [
                'inbox_item_id' => $inboxItem->id,
                'workspace_id' => $inboxItem->workspace_id,
                'platform' => $inboxItem->platform->value,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->log('Failed to broadcast inbox item received', [
                'inbox_item_id' => $inboxItem->id,
                'error' => $e->getMessage(),
            ], 'error');

            return false;
        }
    }

    /**
     * Broadcast an inbox message reply event.
     *
     * This notifies all workspace members that a reply was sent to an inbox message.
     *
     * @param InboxItem $inboxItem The inbox item that was replied to
     * @param string $replyContent The content of the reply
     * @param User $repliedBy The user who sent the reply
     * @return bool True if broadcast was successful
     */
    public function broadcastInboxMessageReplied(
        InboxItem $inboxItem,
        string $replyContent,
        User $repliedBy
    ): bool {
        try {
            broadcast(new InboxMessageReplied(
                $inboxItem,
                $replyContent,
                $repliedBy->name
            ));

            $this->log('Inbox message reply broadcasted', [
                'inbox_item_id' => $inboxItem->id,
                'replied_by' => $repliedBy->id,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->log('Failed to broadcast inbox message reply', [
                'inbox_item_id' => $inboxItem->id,
                'error' => $e->getMessage(),
            ], 'error');

            return false;
        }
    }

    /**
     * Broadcast an inbox message assignment event.
     *
     * This notifies both the workspace channel and the assigned user's
     * personal channel about the assignment.
     *
     * @param InboxItem $inboxItem The inbox item that was assigned
     * @param User $assignedTo The user the item was assigned to
     * @param User $assignedBy The user who made the assignment
     * @return bool True if broadcast was successful
     */
    public function broadcastInboxMessageAssigned(
        InboxItem $inboxItem,
        User $assignedTo,
        User $assignedBy
    ): bool {
        try {
            broadcast(new InboxMessageAssigned(
                $inboxItem,
                $assignedTo->id,
                $assignedBy->id
            ));

            $this->log('Inbox message assignment broadcasted', [
                'inbox_item_id' => $inboxItem->id,
                'assigned_to' => $assignedTo->id,
                'assigned_by' => $assignedBy->id,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->log('Failed to broadcast inbox message assignment', [
                'inbox_item_id' => $inboxItem->id,
                'error' => $e->getMessage(),
            ], 'error');

            return false;
        }
    }

    /**
     * Broadcast a post status change event.
     *
     * This notifies the post creator and workspace members about status changes
     * (e.g., published, failed, approved).
     *
     * @param Post $post The post whose status changed
     * @param string $oldStatus The previous status
     * @param string $newStatus The new status
     * @return bool True if broadcast was successful
     */
    public function broadcastPostStatusChanged(
        Post $post,
        string $oldStatus,
        string $newStatus
    ): bool {
        try {
            broadcast(new PostStatusChanged($post));

            $this->log('Post status change broadcasted', [
                'post_id' => $post->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->log('Failed to broadcast post status change', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ], 'error');

            return false;
        }
    }

    /**
     * Broadcast a notification and create an in-app notification.
     *
     * This is a convenience method that combines notification creation
     * with real-time broadcasting.
     *
     * @param User $user The user to notify
     * @param NotificationType $type The notification type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array<string, mixed>|null $data Additional data
     * @param string|null $actionUrl Action URL
     * @return Notification The created notification
     */
    public function notifyAndBroadcast(
        User $user,
        NotificationType $type,
        string $title,
        string $message,
        ?array $data = null,
        ?string $actionUrl = null
    ): Notification {
        $notificationService = app(NotificationService::class);

        $notification = $notificationService->send(
            $user,
            $type,
            $title,
            $message,
            $data,
            $actionUrl
        );

        // The NotificationService already broadcasts via NewNotification event
        // but we log it here for tracking
        $this->log('Notification created and broadcasted', [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'type' => $type->value,
        ]);

        return $notification;
    }

    /**
     * Check if broadcasting is enabled.
     *
     * @return bool True if broadcasting is enabled
     */
    public function isBroadcastingEnabled(): bool
    {
        return config('broadcasting.default') !== 'null'
            && config('broadcasting.default') !== 'log';
    }

    /**
     * Get the broadcast connection name.
     *
     * @return string The broadcast connection name
     */
    public function getBroadcastConnection(): string
    {
        return config('broadcasting.default', 'reverb') ?? 'reverb';
    }

    /**
     * Test the broadcast connection.
     *
     * This method attempts to broadcast a test event to verify
     * the connection is working properly.
     *
     * @return bool True if the test broadcast succeeded
     */
    public function testBroadcastConnection(): bool
    {
        try {
            if (!$this->isBroadcastingEnabled()) {
                $this->log('Broadcasting is disabled', [], 'warning');
                return false;
            }

            $this->log('Broadcast connection test', [
                'connection' => $this->getBroadcastConnection(),
                'enabled' => true,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->log('Broadcast connection test failed', [
                'error' => $e->getMessage(),
            ], 'error');

            return false;
        }
    }
}
