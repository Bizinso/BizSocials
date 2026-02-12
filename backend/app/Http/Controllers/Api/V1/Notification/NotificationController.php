<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Notification;

use App\Data\Notification\NotificationData;
use App\Data\Notification\NotificationPreferenceData;
use App\Enums\Notification\NotificationType;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Notification\MarkMultipleReadRequest;
use App\Http\Requests\Notification\UpdatePreferencesRequest;
use App\Models\Notification\Notification;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * List notifications for the authenticated user.
     * GET /api/v1/notifications
     *
     * Query parameters:
     * - type: Filter by notification type
     * - is_read: Filter by read status (true/false)
     * - category: Filter by notification category
     * - per_page: Number of items per page (max 100)
     * - sort_by: Column to sort by (default: created_at)
     * - sort_dir: Sort direction (default: desc)
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $filters = [
            'type' => $request->query('type'),
            'is_read' => $request->query('is_read'),
            'category' => $request->query('category'),
            'per_page' => $request->query('per_page', 20),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
        ];

        $notifications = $this->notificationService->listForUser($user, $filters);

        $transformedItems = collect($notifications->items())->map(
            fn (Notification $notification): array => NotificationData::fromModel($notification)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'from' => $notifications->firstItem(),
                'to' => $notifications->lastItem(),
            ],
            'links' => [
                'first' => $notifications->url(1),
                'last' => $notifications->url($notifications->lastPage()),
                'prev' => $notifications->previousPageUrl(),
                'next' => $notifications->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get the count of unread notifications.
     * GET /api/v1/notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $count = $this->notificationService->getUnreadCount($user);

        return $this->success(
            ['count' => $count],
            'Unread count retrieved successfully'
        );
    }

    /**
     * Get recent notifications (for dropdown/widget).
     * GET /api/v1/notifications/recent
     *
     * Query parameters:
     * - limit: Maximum number of notifications to return (default: 10, max: 50)
     */
    public function recent(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $limit = min((int) $request->query('limit', 10), 50);
        $notifications = $this->notificationService->getRecent($user, $limit);

        return $this->success(
            NotificationData::fromCollection($notifications),
            'Recent notifications retrieved successfully'
        );
    }

    /**
     * Mark a single notification as read.
     * POST /api/v1/notifications/{notification}/read
     *
     * @throws AuthorizationException
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify user owns the notification
        $this->authorize('update', $notification);

        $notification = $this->notificationService->markAsRead($notification);

        return $this->success(
            NotificationData::fromModel($notification)->toArray(),
            'Notification marked as read'
        );
    }

    /**
     * Mark all notifications as read for the authenticated user.
     * POST /api/v1/notifications/read-all
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $count = $this->notificationService->markAllAsRead($user);

        return $this->success(
            ['marked_count' => $count],
            'All notifications marked as read'
        );
    }

    /**
     * Mark multiple notifications as read.
     * POST /api/v1/notifications/read-multiple
     */
    public function markMultipleAsRead(MarkMultipleReadRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array<string> $ids */
        $ids = $request->input('ids');

        $count = $this->notificationService->markMultipleAsRead($user, $ids);

        return $this->success(
            ['marked_count' => $count],
            'Notifications marked as read'
        );
    }

    /**
     * Get notification preferences for the authenticated user.
     * GET /api/v1/notifications/preferences
     */
    public function preferences(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $preferences = $this->notificationService->getPreferences($user);

        return $this->success(
            NotificationPreferenceData::fromCollection($preferences),
            'Notification preferences retrieved successfully'
        );
    }

    /**
     * Update notification preferences.
     * PUT /api/v1/notifications/preferences
     */
    public function updatePreferences(UpdatePreferencesRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $type = NotificationType::from($request->input('notification_type'));

        $preference = $this->notificationService->updatePreference(
            user: $user,
            type: $type,
            inApp: $request->boolean('in_app_enabled', true),
            email: $request->boolean('email_enabled', true),
            push: $request->boolean('push_enabled', false)
        );

        return $this->success(
            NotificationPreferenceData::fromModel($preference)->toArray(),
            'Notification preferences updated successfully'
        );
    }
}
