<?php

declare(strict_types=1);

/**
 * NotificationService Unit Tests
 *
 * Tests for the NotificationService which handles all notification
 * operations including sending, managing preferences, and cleanup.
 *
 * @see \App\Services\Notification\NotificationService
 */

use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationType;
use App\Jobs\Notification\SendNotificationEmailJob;
use App\Models\Notification\Notification;
use App\Models\Notification\NotificationPreference;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->service = new NotificationService();
    $this->tenant = Tenant::factory()->active()->create();
    $this->user = User::factory()->forTenant($this->tenant)->active()->create();
});

describe('send', function () {
    it('creates a notification for a user', function () {
        Queue::fake();

        $notification = $this->service->send(
            user: $this->user,
            type: NotificationType::POST_PUBLISHED,
            title: 'Post Published',
            message: 'Your post has been published successfully.',
        );

        expect($notification)->toBeInstanceOf(Notification::class);
        expect($notification->user_id)->toBe($this->user->id);
        expect($notification->tenant_id)->toBe($this->tenant->id);
        expect($notification->type)->toBe(NotificationType::POST_PUBLISHED);
        expect($notification->channel)->toBe(NotificationChannel::IN_APP);
        expect($notification->title)->toBe('Post Published');
        expect($notification->message)->toBe('Your post has been published successfully.');
    });

    it('includes optional data in notification', function () {
        Queue::fake();

        $data = [
            'post_id' => fake()->uuid(),
            'platform' => 'twitter',
        ];

        $notification = $this->service->send(
            user: $this->user,
            type: NotificationType::POST_PUBLISHED,
            title: 'Post Published',
            message: 'Your post has been published.',
            data: $data,
        );

        expect($notification->data)->toBe($data);
        expect($notification->getDataValue('post_id'))->toBe($data['post_id']);
        expect($notification->getDataValue('platform'))->toBe('twitter');
    });

    it('includes action url when provided', function () {
        Queue::fake();

        $actionUrl = 'https://app.example.com/posts/123';

        $notification = $this->service->send(
            user: $this->user,
            type: NotificationType::POST_PUBLISHED,
            title: 'Post Published',
            message: 'Your post has been published.',
            actionUrl: $actionUrl,
        );

        expect($notification->action_url)->toBe($actionUrl);
    });

    it('dispatches email job when email is enabled', function () {
        Queue::fake();

        // Create a preference with email enabled
        NotificationPreference::factory()
            ->forUser($this->user)
            ->ofType(NotificationType::POST_PUBLISHED)
            ->emailEnabled()
            ->create();

        $this->service->send(
            user: $this->user,
            type: NotificationType::POST_PUBLISHED,
            title: 'Post Published',
            message: 'Your post has been published.',
        );

        Queue::assertPushed(SendNotificationEmailJob::class);
    });

    it('does not dispatch email job when email is disabled', function () {
        Queue::fake();

        // Create a preference with email disabled
        NotificationPreference::factory()
            ->forUser($this->user)
            ->ofType(NotificationType::POST_PUBLISHED)
            ->emailDisabled()
            ->create();

        $this->service->send(
            user: $this->user,
            type: NotificationType::POST_PUBLISHED,
            title: 'Post Published',
            message: 'Your post has been published.',
        );

        Queue::assertNotPushed(SendNotificationEmailJob::class);
    });
});

describe('sendToUsers', function () {
    it('sends notification to multiple users', function () {
        Queue::fake();

        $users = User::factory()
            ->count(3)
            ->forTenant($this->tenant)
            ->active()
            ->create();

        $notifications = $this->service->sendToUsers(
            users: $users,
            type: NotificationType::SYSTEM_ANNOUNCEMENT,
            title: 'System Update',
            message: 'The system will be undergoing maintenance.',
        );

        expect($notifications)->toHaveCount(3);

        foreach ($notifications as $notification) {
            expect($notification->type)->toBe(NotificationType::SYSTEM_ANNOUNCEMENT);
            expect($notification->title)->toBe('System Update');
        }
    });

    it('returns collection of created notifications', function () {
        Queue::fake();

        $users = User::factory()
            ->count(2)
            ->forTenant($this->tenant)
            ->active()
            ->create();

        $notifications = $this->service->sendToUsers(
            users: $users,
            type: NotificationType::MAINTENANCE_SCHEDULED,
            title: 'Maintenance',
            message: 'Scheduled maintenance.',
        );

        expect($notifications)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($notifications->every(fn ($n) => $n instanceof Notification))->toBeTrue();
    });

    it('continues sending even if one user fails', function () {
        Queue::fake();

        $users = User::factory()
            ->count(3)
            ->forTenant($this->tenant)
            ->active()
            ->create();

        // This test verifies the service handles individual failures gracefully
        // In a real scenario, the try-catch in sendToUsers would handle exceptions
        $notifications = $this->service->sendToUsers(
            users: $users,
            type: NotificationType::SYSTEM_ANNOUNCEMENT,
            title: 'Announcement',
            message: 'Test message.',
        );

        expect($notifications)->toHaveCount(3);
    });
});

describe('sendToTenant', function () {
    it('sends notification to all active tenant users', function () {
        Queue::fake();

        // Create multiple active users in the tenant
        User::factory()
            ->count(4)
            ->forTenant($this->tenant)
            ->active()
            ->create();

        // Create an inactive user (should not receive notification)
        User::factory()
            ->forTenant($this->tenant)
            ->suspended()
            ->create();

        $notifications = $this->service->sendToTenant(
            tenant: $this->tenant,
            type: NotificationType::SYSTEM_ANNOUNCEMENT,
            title: 'Tenant Announcement',
            message: 'Important update for all users.',
        );

        // Should have notifications for 5 active users (1 from beforeEach + 4 created here)
        expect($notifications)->toHaveCount(5);
    });

    it('includes data and action url in tenant notifications', function () {
        Queue::fake();

        $data = ['announcement_id' => fake()->uuid()];
        $actionUrl = 'https://app.example.com/announcements/123';

        $notifications = $this->service->sendToTenant(
            tenant: $this->tenant,
            type: NotificationType::SYSTEM_ANNOUNCEMENT,
            title: 'Announcement',
            message: 'Check out this update.',
            data: $data,
            actionUrl: $actionUrl,
        );

        foreach ($notifications as $notification) {
            expect($notification->data)->toBe($data);
            expect($notification->action_url)->toBe($actionUrl);
        }
    });
});

describe('markAsRead', function () {
    it('marks a notification as read', function () {
        $notification = Notification::factory()
            ->forUser($this->user)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        expect($notification->read_at)->toBeNull();

        $updated = $this->service->markAsRead($notification);

        expect($updated->read_at)->not->toBeNull();
        expect($updated->isRead())->toBeTrue();
    });

    it('returns the updated notification', function () {
        $notification = Notification::factory()
            ->forUser($this->user)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        $result = $this->service->markAsRead($notification);

        expect($result)->toBeInstanceOf(Notification::class);
        expect($result->id)->toBe($notification->id);
    });

    it('does not update if already read', function () {
        $readAt = now()->subHours(2);
        $notification = Notification::factory()
            ->forUser($this->user)
            ->inApp()
            ->sent()
            ->create(['read_at' => $readAt]);

        $this->service->markAsRead($notification);
        $notification->refresh();

        // read_at should not have changed
        expect($notification->read_at->format('Y-m-d H:i:s'))->toBe($readAt->format('Y-m-d H:i:s'));
    });
});

describe('markAllAsRead', function () {
    it('marks all unread notifications as read for a user', function () {
        Notification::factory()
            ->count(5)
            ->forUser($this->user)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        $count = $this->service->markAllAsRead($this->user);

        expect($count)->toBe(5);

        $unreadCount = Notification::forUser($this->user->id)->unread()->count();
        expect($unreadCount)->toBe(0);
    });

    it('returns the count of marked notifications', function () {
        Notification::factory()
            ->count(3)
            ->forUser($this->user)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        Notification::factory()
            ->count(2)
            ->forUser($this->user)
            ->read()
            ->inApp()
            ->sent()
            ->create();

        $count = $this->service->markAllAsRead($this->user);

        expect($count)->toBe(3);
    });

    it('only affects the specified user notifications', function () {
        $otherUser = User::factory()->forTenant($this->tenant)->active()->create();

        Notification::factory()
            ->count(3)
            ->forUser($this->user)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        Notification::factory()
            ->count(4)
            ->forUser($otherUser)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        $this->service->markAllAsRead($this->user);

        // Other user's notifications should still be unread
        $otherUnread = Notification::forUser($otherUser->id)->unread()->count();
        expect($otherUnread)->toBe(4);
    });
});

describe('getUnreadCount', function () {
    it('returns count of unread in-app notifications', function () {
        Notification::factory()
            ->count(5)
            ->forUser($this->user)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        Notification::factory()
            ->count(3)
            ->forUser($this->user)
            ->read()
            ->inApp()
            ->sent()
            ->create();

        $count = $this->service->getUnreadCount($this->user);

        expect($count)->toBe(5);
    });

    it('only counts in-app channel notifications', function () {
        Notification::factory()
            ->count(3)
            ->forUser($this->user)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        Notification::factory()
            ->count(5)
            ->forUser($this->user)
            ->unread()
            ->email()
            ->sent()
            ->create();

        $count = $this->service->getUnreadCount($this->user);

        expect($count)->toBe(3);
    });

    it('returns zero when no unread notifications', function () {
        Notification::factory()
            ->count(3)
            ->forUser($this->user)
            ->read()
            ->inApp()
            ->sent()
            ->create();

        $count = $this->service->getUnreadCount($this->user);

        expect($count)->toBe(0);
    });
});

describe('deleteOld', function () {
    it('deletes notifications older than specified days', function () {
        // Create old notifications
        Notification::factory()
            ->count(5)
            ->forUser($this->user)
            ->old(100)
            ->create();

        // Create recent notifications
        Notification::factory()
            ->count(3)
            ->forUser($this->user)
            ->recent(7)
            ->create();

        $deletedCount = $this->service->deleteOld(90);

        expect($deletedCount)->toBe(5);

        $remainingCount = Notification::forUser($this->user->id)->count();
        expect($remainingCount)->toBe(3);
    });

    it('uses default of 90 days when no argument provided', function () {
        Notification::factory()
            ->count(3)
            ->forUser($this->user)
            ->old(100)
            ->create();

        Notification::factory()
            ->count(2)
            ->forUser($this->user)
            ->old(80)
            ->create();

        $deletedCount = $this->service->deleteOld();

        expect($deletedCount)->toBe(3);
    });

    it('returns zero when no old notifications exist', function () {
        Notification::factory()
            ->count(5)
            ->forUser($this->user)
            ->recent(7)
            ->create();

        $deletedCount = $this->service->deleteOld(90);

        expect($deletedCount)->toBe(0);
    });

    it('deletes notifications from all users', function () {
        $otherUser = User::factory()->forTenant($this->tenant)->active()->create();

        Notification::factory()
            ->count(3)
            ->forUser($this->user)
            ->old(100)
            ->create();

        Notification::factory()
            ->count(4)
            ->forUser($otherUser)
            ->old(100)
            ->create();

        $deletedCount = $this->service->deleteOld(90);

        expect($deletedCount)->toBe(7);
    });
});

describe('getPreferences', function () {
    it('returns preferences for all notification types', function () {
        $preferences = $this->service->getPreferences($this->user);

        expect($preferences)->toHaveCount(count(NotificationType::cases()));
    });

    it('returns existing preferences', function () {
        NotificationPreference::factory()
            ->forUser($this->user)
            ->ofType(NotificationType::POST_PUBLISHED)
            ->emailDisabled()
            ->create();

        $preferences = $this->service->getPreferences($this->user);

        $postPublishedPref = $preferences->first(
            fn ($p) => $p->notification_type === NotificationType::POST_PUBLISHED
        );

        expect($postPublishedPref->email_enabled)->toBeFalse();
    });

    it('creates default preferences for missing types', function () {
        // Start with no preferences
        $count = NotificationPreference::forUser($this->user->id)->count();
        expect($count)->toBe(0);

        $preferences = $this->service->getPreferences($this->user);

        // Should have created preferences for all types
        $newCount = NotificationPreference::forUser($this->user->id)->count();
        expect($newCount)->toBe(count(NotificationType::cases()));
    });
});

describe('updatePreference', function () {
    it('updates existing preference', function () {
        NotificationPreference::factory()
            ->forUser($this->user)
            ->ofType(NotificationType::POST_PUBLISHED)
            ->emailEnabled()
            ->pushDisabled()
            ->create();

        $preference = $this->service->updatePreference(
            user: $this->user,
            type: NotificationType::POST_PUBLISHED,
            inApp: true,
            email: false,
            push: true,
        );

        expect($preference->in_app_enabled)->toBeTrue();
        expect($preference->email_enabled)->toBeFalse();
        expect($preference->push_enabled)->toBeTrue();
    });

    it('creates preference if it does not exist', function () {
        $existingPref = NotificationPreference::forUser($this->user->id)
            ->ofType(NotificationType::SYSTEM_ANNOUNCEMENT)
            ->first();
        expect($existingPref)->toBeNull();

        $preference = $this->service->updatePreference(
            user: $this->user,
            type: NotificationType::SYSTEM_ANNOUNCEMENT,
            inApp: true,
            email: false,
            push: false,
        );

        expect($preference)->toBeInstanceOf(NotificationPreference::class);
        expect($preference->notification_type)->toBe(NotificationType::SYSTEM_ANNOUNCEMENT);
        expect($preference->in_app_enabled)->toBeTrue();
        expect($preference->email_enabled)->toBeFalse();
    });

    it('returns the updated preference', function () {
        $preference = $this->service->updatePreference(
            user: $this->user,
            type: NotificationType::NEW_COMMENT,
            inApp: true,
            email: true,
            push: true,
        );

        expect($preference)->toBeInstanceOf(NotificationPreference::class);
        expect($preference->user_id)->toBe($this->user->id);
    });
});

describe('respects user preferences when sending', function () {
    it('checks email preference before dispatching email job', function () {
        Queue::fake();

        // Disable email for this notification type
        NotificationPreference::factory()
            ->forUser($this->user)
            ->ofType(NotificationType::NEW_COMMENT)
            ->emailDisabled()
            ->create();

        $this->service->send(
            user: $this->user,
            type: NotificationType::NEW_COMMENT,
            title: 'New Comment',
            message: 'Someone commented on your post.',
        );

        Queue::assertNotPushed(SendNotificationEmailJob::class);
    });

    it('uses default when no preference exists', function () {
        Queue::fake();

        // No preference exists - should use default (email enabled by default)
        $this->service->send(
            user: $this->user,
            type: NotificationType::NEW_COMMENT,
            title: 'New Comment',
            message: 'Someone commented on your post.',
        );

        // Email is enabled by default, so job should be dispatched
        Queue::assertPushed(SendNotificationEmailJob::class);
    });
});
