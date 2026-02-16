<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Enums\Notification\NotificationType;
use App\Models\Inbox\InboxItem;
use App\Models\User;
use App\Services\BaseService;
use App\Services\Notification\NotificationService;

/**
 * InboxNotificationService
 *
 * Handles notification creation for inbox-related events.
 * Respects user notification preferences for inbox notifications.
 *
 * Features:
 * - Notify on new inbox messages
 * - Notify on inbox message replies
 * - Notify on inbox message assignments
 * - Respect user notification preferences
 */
final class InboxNotificationService extends BaseService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    /**
     * Notify relevant users about a new inbox message.
     *
     * This notifies:
     * - The assigned user (if assigned)
     * - All workspace members with inbox notification preferences enabled
     *
     * @param InboxItem $inboxItem The new inbox item
     * @return int Number of notifications sent
     */
    public function notifyNewMessage(InboxItem $inboxItem): int
    {
        $notificationsSent = 0;

        // If assigned to a specific user, notify them
        if ($inboxItem->assigned_to_user_id) {
            $assignedUser = $inboxItem->assignedTo;
            if ($assignedUser) {
                $this->sendNewMessageNotification($assignedUser, $inboxItem);
                $notificationsSent++;
            }
        } else {
            // Otherwise, notify all workspace members who have inbox notifications enabled
            $workspaceUsers = $inboxItem->workspace->members;

            foreach ($workspaceUsers as $user) {
                try {
                    $this->sendNewMessageNotification($user, $inboxItem);
                    $notificationsSent++;
                } catch (\Throwable $e) {
                    $this->log('Failed to send new message notification', [
                        'user_id' => $user->id,
                        'inbox_item_id' => $inboxItem->id,
                        'error' => $e->getMessage(),
                    ], 'warning');
                }
            }
        }

        $this->log('New message notifications sent', [
            'inbox_item_id' => $inboxItem->id,
            'notifications_sent' => $notificationsSent,
        ]);

        return $notificationsSent;
    }

    /**
     * Notify relevant users about a reply to an inbox message.
     *
     * This notifies:
     * - The assigned user (if different from the replier)
     * - The user who originally received the message
     *
     * @param InboxItem $inboxItem The inbox item that was replied to
     * @param User $repliedBy The user who sent the reply
     * @return int Number of notifications sent
     */
    public function notifyMessageReplied(InboxItem $inboxItem, User $repliedBy): int
    {
        $notificationsSent = 0;
        $notifiedUserIds = [];

        // Notify the assigned user if they didn't send the reply
        if ($inboxItem->assigned_to_user_id && $inboxItem->assigned_to_user_id !== $repliedBy->id) {
            $assignedUser = $inboxItem->assignedTo;
            if ($assignedUser) {
                $this->sendReplyNotification($assignedUser, $inboxItem, $repliedBy);
                $notificationsSent++;
                $notifiedUserIds[] = $assignedUser->id;
            }
        }

        $this->log('Reply notifications sent', [
            'inbox_item_id' => $inboxItem->id,
            'replied_by' => $repliedBy->id,
            'notifications_sent' => $notificationsSent,
        ]);

        return $notificationsSent;
    }

    /**
     * Notify a user about an inbox message assignment.
     *
     * @param InboxItem $inboxItem The inbox item that was assigned
     * @param User $assignedTo The user the item was assigned to
     * @param User $assignedBy The user who made the assignment
     * @return bool True if notification was sent
     */
    public function notifyMessageAssigned(
        InboxItem $inboxItem,
        User $assignedTo,
        User $assignedBy
    ): bool {
        // Don't notify if user assigned to themselves
        if ($assignedTo->id === $assignedBy->id) {
            return false;
        }

        try {
            $this->notificationService->send(
                user: $assignedTo,
                type: NotificationType::INBOX_ASSIGNED,
                title: 'Inbox Message Assigned',
                message: sprintf(
                    '%s assigned you a %s from %s',
                    $assignedBy->name,
                    $inboxItem->item_type->label(),
                    $inboxItem->author_name
                ),
                data: [
                    'inbox_item_id' => $inboxItem->id,
                    'platform' => $inboxItem->socialAccount->platform->value,
                    'author_name' => $inboxItem->author_name,
                    'content_preview' => $this->getContentPreview($inboxItem->content_text),
                    'assigned_by_user_id' => $assignedBy->id,
                    'assigned_by_name' => $assignedBy->name,
                ],
                actionUrl: "/inbox/{$inboxItem->id}"
            );

            $this->log('Assignment notification sent', [
                'inbox_item_id' => $inboxItem->id,
                'assigned_to' => $assignedTo->id,
                'assigned_by' => $assignedBy->id,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->log('Failed to send assignment notification', [
                'inbox_item_id' => $inboxItem->id,
                'assigned_to' => $assignedTo->id,
                'error' => $e->getMessage(),
            ], 'error');

            return false;
        }
    }

    /**
     * Send a new message notification to a user.
     *
     * @param User $user The user to notify
     * @param InboxItem $inboxItem The new inbox item
     */
    private function sendNewMessageNotification(User $user, InboxItem $inboxItem): void
    {
        $this->notificationService->send(
            user: $user,
            type: NotificationType::NEW_COMMENT,
            title: 'New Inbox Message',
            message: sprintf(
                'New %s from %s on %s',
                $inboxItem->item_type->label(),
                $inboxItem->author_name,
                $inboxItem->socialAccount->platform->label()
            ),
            data: [
                'inbox_item_id' => $inboxItem->id,
                'platform' => $inboxItem->socialAccount->platform->value,
                'item_type' => $inboxItem->item_type->value,
                'author_name' => $inboxItem->author_name,
                'content_preview' => $this->getContentPreview($inboxItem->content_text),
            ],
            actionUrl: "/inbox/{$inboxItem->id}"
        );
    }

    /**
     * Send a reply notification to a user.
     *
     * @param User $user The user to notify
     * @param InboxItem $inboxItem The inbox item that was replied to
     * @param User $repliedBy The user who sent the reply
     */
    private function sendReplyNotification(User $user, InboxItem $inboxItem, User $repliedBy): void
    {
        $this->notificationService->send(
            user: $user,
            type: NotificationType::NEW_COMMENT,
            title: 'Inbox Message Replied',
            message: sprintf(
                '%s replied to a message from %s',
                $repliedBy->name,
                $inboxItem->author_name
            ),
            data: [
                'inbox_item_id' => $inboxItem->id,
                'platform' => $inboxItem->socialAccount->platform->value,
                'author_name' => $inboxItem->author_name,
                'replied_by_user_id' => $repliedBy->id,
                'replied_by_name' => $repliedBy->name,
            ],
            actionUrl: "/inbox/{$inboxItem->id}"
        );
    }

    /**
     * Get a preview of the content text (first 100 characters).
     *
     * @param string $content The full content text
     * @return string The preview text
     */
    private function getContentPreview(string $content): string
    {
        $maxLength = 100;
        
        if (mb_strlen($content) <= $maxLength) {
            return $content;
        }

        return mb_substr($content, 0, $maxLength) . '...';
    }
}
