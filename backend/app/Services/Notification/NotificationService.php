<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationType;
use App\Events\Broadcast\NewNotification;
use App\Jobs\Notification\SendNotificationEmailJob;
use App\Models\Notification\Notification;
use App\Models\Notification\NotificationPreference;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * NotificationService
 *
 * Handles all notification-related operations including:
 * - Sending notifications to single or multiple users
 * - Respecting user notification preferences
 * - Managing read/unread status
 * - Retrieving and filtering notifications
 * - Managing notification preferences
 * - Cleaning up old notifications
 */
final class NotificationService extends BaseService
{
    /**
     * Send a notification to a single user.
     *
     * This method:
     * 1. Checks if in-app notifications are enabled for the user/type
     * 2. Creates an in-app notification if enabled
     * 3. Dispatches an email job if email notifications are enabled
     *
     * @param User $user The user to notify
     * @param NotificationType $type The notification type
     * @param string $title Notification title
     * @param string $message Notification message body
     * @param array<string, mixed>|null $data Additional data to include
     * @param string|null $actionUrl URL for the notification action
     * @return Notification The created notification
     */
    public function send(
        User $user,
        NotificationType $type,
        string $title,
        string $message,
        ?array $data = null,
        ?string $actionUrl = null
    ): Notification {
        return $this->transaction(function () use ($user, $type, $title, $message, $data, $actionUrl): Notification {
            $notification = $this->createInAppNotification(
                $user,
                $type,
                $title,
                $message,
                $data,
                $actionUrl
            );

            // Broadcast via WebSocket for real-time delivery
            broadcast(new NewNotification($notification));

            // Dispatch email job if email is enabled for this notification type
            if ($this->isChannelEnabled($user, $type, NotificationChannel::EMAIL)) {
                SendNotificationEmailJob::dispatch($notification);
            }

            $this->log('Notification sent', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'type' => $type->value,
            ]);

            return $notification;
        });
    }

    /**
     * Send a notification to multiple users.
     *
     * @param Collection<int, User> $users Collection of users to notify
     * @param NotificationType $type The notification type
     * @param string $title Notification title
     * @param string $message Notification message body
     * @param array<string, mixed>|null $data Additional data to include
     * @param string|null $actionUrl URL for the notification action
     * @return Collection<int, Notification> Collection of created notifications
     */
    public function sendToUsers(
        Collection $users,
        NotificationType $type,
        string $title,
        string $message,
        ?array $data = null,
        ?string $actionUrl = null
    ): Collection {
        $notifications = collect();

        foreach ($users as $user) {
            try {
                $notification = $this->send($user, $type, $title, $message, $data, $actionUrl);
                $notifications->push($notification);
            } catch (\Throwable $e) {
                $this->log('Failed to send notification to user', [
                    'user_id' => $user->id,
                    'type' => $type->value,
                    'error' => $e->getMessage(),
                ], 'warning');
            }
        }

        $this->log('Bulk notification sent', [
            'total_users' => $users->count(),
            'successful' => $notifications->count(),
            'type' => $type->value,
        ]);

        return $notifications;
    }

    /**
     * Send a notification to all users in a tenant.
     *
     * @param Tenant $tenant The tenant whose users should be notified
     * @param NotificationType $type The notification type
     * @param string $title Notification title
     * @param string $message Notification message body
     * @param array<string, mixed>|null $data Additional data to include
     * @param string|null $actionUrl URL for the notification action
     * @return Collection<int, Notification> Collection of created notifications
     */
    public function sendToTenant(
        Tenant $tenant,
        NotificationType $type,
        string $title,
        string $message,
        ?array $data = null,
        ?string $actionUrl = null
    ): Collection {
        $users = User::forTenant($tenant->id)->active()->get();

        $this->log('Sending tenant-wide notification', [
            'tenant_id' => $tenant->id,
            'user_count' => $users->count(),
            'type' => $type->value,
        ]);

        return $this->sendToUsers($users, $type, $title, $message, $data, $actionUrl);
    }

    /**
     * Mark a single notification as read.
     *
     * @param Notification $notification The notification to mark as read
     * @return Notification The updated notification
     */
    public function markAsRead(Notification $notification): Notification
    {
        $notification->markAsRead();

        $this->log('Notification marked as read', [
            'notification_id' => $notification->id,
        ]);

        return $notification->fresh() ?? $notification;
    }

    /**
     * Mark all notifications as read for a user.
     *
     * @param User $user The user whose notifications should be marked as read
     * @return int The number of notifications marked as read
     */
    public function markAllAsRead(User $user): int
    {
        $count = Notification::forUser($user->id)
            ->unread()
            ->update(['read_at' => now()]);

        $this->log('All notifications marked as read', [
            'user_id' => $user->id,
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Mark multiple notifications as read.
     *
     * @param User $user The user who owns the notifications
     * @param array<string> $ids Array of notification IDs to mark as read
     * @return int The number of notifications marked as read
     */
    public function markMultipleAsRead(User $user, array $ids): int
    {
        $count = Notification::forUser($user->id)
            ->whereIn('id', $ids)
            ->unread()
            ->update(['read_at' => now()]);

        $this->log('Multiple notifications marked as read', [
            'user_id' => $user->id,
            'requested_ids' => count($ids),
            'updated_count' => $count,
        ]);

        return $count;
    }

    /**
     * List notifications for a user with pagination and filtering.
     *
     * Available filters:
     * - type: NotificationType value to filter by type
     * - is_read: boolean to filter read/unread
     * - category: string to filter by notification category
     * - per_page: int for pagination (max 100)
     * - sort_by: column to sort by (default: created_at)
     * - sort_dir: sort direction (default: desc)
     *
     * @param User $user The user whose notifications to retrieve
     * @param array<string, mixed> $filters Optional filters to apply
     * @return LengthAwarePaginator Paginated list of notifications
     */
    public function listForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Notification::forUser($user->id)
            ->ofChannel(NotificationChannel::IN_APP);

        // Filter by notification type
        if (!empty($filters['type'])) {
            $type = NotificationType::tryFrom($filters['type']);
            if ($type !== null) {
                $query->ofType($type);
            }
        }

        // Filter by read status
        if (isset($filters['is_read'])) {
            if ($filters['is_read'] === true || $filters['is_read'] === 'true') {
                $query->read();
            } else {
                $query->unread();
            }
        }

        // Filter by category
        if (!empty($filters['category'])) {
            $typesInCategory = NotificationType::byCategory($filters['category']);
            if (!empty($typesInCategory)) {
                $query->whereIn('type', $typesInCategory);
            }
        }

        // Filter by date range
        if (!empty($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        // Pagination
        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = min($perPage, 100);

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    /**
     * Get the count of unread notifications for a user.
     *
     * @param User $user The user to count unread notifications for
     * @return int The count of unread notifications
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::forUser($user->id)
            ->ofChannel(NotificationChannel::IN_APP)
            ->unread()
            ->count();
    }

    /**
     * Get recent notifications for a user.
     *
     * @param User $user The user to get notifications for
     * @param int $limit Maximum number of notifications to return
     * @return Collection<int, Notification> Collection of recent notifications
     */
    public function getRecent(User $user, int $limit = 10): Collection
    {
        return Notification::forUser($user->id)
            ->ofChannel(NotificationChannel::IN_APP)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all notification preferences for a user.
     *
     * If a preference for a notification type doesn't exist, it will be created
     * with default values.
     *
     * @param User $user The user to get preferences for
     * @return Collection<int, NotificationPreference> Collection of preferences
     */
    public function getPreferences(User $user): Collection
    {
        $existingPreferences = NotificationPreference::forUser($user->id)->get();
        $existingTypes = $existingPreferences->pluck('notification_type')->map(fn ($type) => $type->value)->toArray();

        $preferences = collect();

        foreach (NotificationType::cases() as $type) {
            if (in_array($type->value, $existingTypes, true)) {
                $preference = $existingPreferences->first(
                    fn (NotificationPreference $pref) => $pref->notification_type === $type
                );
            } else {
                // Create default preference if it doesn't exist
                $preference = NotificationPreference::getOrCreateForUser($user, $type);
            }

            $preferences->push($preference);
        }

        return $preferences;
    }

    /**
     * Update a single notification preference for a user.
     *
     * @param User $user The user to update preference for
     * @param NotificationType $type The notification type
     * @param bool $inApp Whether in-app notifications are enabled
     * @param bool $email Whether email notifications are enabled
     * @param bool $push Whether push notifications are enabled
     * @return NotificationPreference The updated preference
     */
    public function updatePreference(
        User $user,
        NotificationType $type,
        bool $inApp,
        bool $email,
        bool $push
    ): NotificationPreference {
        $preference = NotificationPreference::createOrUpdateForUser($user, $type, [
            'in_app_enabled' => $inApp,
            'email_enabled' => $email,
            'push_enabled' => $push,
        ]);

        $this->log('Notification preference updated', [
            'user_id' => $user->id,
            'type' => $type->value,
            'in_app' => $inApp,
            'email' => $email,
            'push' => $push,
        ]);

        return $preference;
    }

    /**
     * Delete old notifications based on age.
     *
     * @param int $daysOld Delete notifications older than this many days
     * @return int The number of notifications deleted
     */
    public function deleteOld(int $daysOld = 90): int
    {
        $cutoffDate = now()->subDays($daysOld);

        $count = Notification::where('created_at', '<', $cutoffDate)
            ->delete();

        $this->log('Old notifications deleted', [
            'days_old' => $daysOld,
            'deleted_count' => $count,
        ]);

        return $count;
    }

    /**
     * Delete a notification.
     *
     * @param Notification $notification The notification to delete
     * @return bool True if deleted successfully
     */
    public function delete(Notification $notification): bool
    {
        $id = $notification->id;
        $deleted = $notification->delete();

        if ($deleted) {
            $this->log('Notification deleted', [
                'notification_id' => $id,
            ]);
        }

        return (bool) $deleted;
    }

    /**
     * Check if a notification channel is enabled for a user and notification type.
     *
     * @param User $user The user to check
     * @param NotificationType $type The notification type
     * @param NotificationChannel $channel The channel to check
     * @return bool True if the channel is enabled
     */
    private function isChannelEnabled(User $user, NotificationType $type, NotificationChannel $channel): bool
    {
        $preference = NotificationPreference::forUser($user->id)
            ->ofType($type)
            ->first();

        if ($preference === null) {
            // Use default channel settings if no preference exists
            return $channel->isEnabledByDefault();
        }

        return $preference->isChannelEnabled($channel);
    }

    /**
     * Create an in-app notification.
     *
     * @param User $user The user to create notification for
     * @param NotificationType $type The notification type
     * @param string $title Notification title
     * @param string $message Notification message body
     * @param array<string, mixed>|null $data Additional data
     * @param string|null $actionUrl Action URL
     * @return Notification The created notification
     */
    private function createInAppNotification(
        User $user,
        NotificationType $type,
        string $title,
        string $message,
        ?array $data,
        ?string $actionUrl
    ): Notification {
        return Notification::createForUser(
            user: $user,
            type: $type,
            title: $title,
            message: $message,
            channel: NotificationChannel::IN_APP,
            data: $data ?? [],
            actionUrl: $actionUrl
        );
    }
}
