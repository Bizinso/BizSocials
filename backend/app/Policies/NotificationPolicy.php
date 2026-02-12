<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Notification\Notification;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class NotificationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any notifications.
     * Users can only view their own notifications.
     */
    public function viewAny(User $user): bool
    {
        // Users can always list their own notifications
        return true;
    }

    /**
     * Determine whether the user can view the notification.
     * Users can only view notifications that belong to them.
     */
    public function view(User $user, Notification $notification): bool
    {
        return $user->id === $notification->user_id;
    }

    /**
     * Determine whether the user can update the notification.
     * Users can only mark as read/unread notifications that belong to them.
     */
    public function update(User $user, Notification $notification): bool
    {
        return $user->id === $notification->user_id;
    }

    /**
     * Determine whether the user can delete the notification.
     * Users can only delete notifications that belong to them.
     */
    public function delete(User $user, Notification $notification): bool
    {
        return $user->id === $notification->user_id;
    }

    /**
     * Determine whether the user can mark the notification as read.
     * Alias for update permission.
     */
    public function markAsRead(User $user, Notification $notification): bool
    {
        return $this->update($user, $notification);
    }

    /**
     * Determine whether the user can mark all notifications as read.
     * Users can always mark their own notifications as read.
     */
    public function markAllAsRead(User $user): bool
    {
        return true;
    }
}
