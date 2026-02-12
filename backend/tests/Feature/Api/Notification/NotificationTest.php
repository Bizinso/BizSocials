<?php

declare(strict_types=1);

/**
 * Notification API Feature Tests
 *
 * Tests for the notification API endpoints including listing,
 * filtering, marking as read, and managing preferences.
 *
 * @see \App\Http\Controllers\Api\V1\Notification\NotificationController
 */

use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationType;
use App\Models\Notification\Notification;
use App\Models\Notification\NotificationPreference;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->user = User::factory()->forTenant($this->tenant)->active()->create();
    $this->otherUser = User::factory()->forTenant($this->tenant)->active()->create();
});

describe('GET /api/v1/notifications', function () {
    it('returns a list of notifications for the authenticated user', function () {
        Notification::factory()
            ->count(5)
            ->forUser($this->user)
            ->inApp()
            ->sent()
            ->create();

        // Create notifications for another user (should not be returned)
        Notification::factory()
            ->count(3)
            ->forUser($this->otherUser)
            ->inApp()
            ->sent()
            ->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'channel',
                        'title',
                        'message',
                        'is_read',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonPath('meta.total', 5);
    });

    it('can filter notifications by type', function () {
        Notification::factory()
            ->count(3)
            ->forUser($this->user)
            ->ofType(NotificationType::POST_PUBLISHED)
            ->inApp()
            ->sent()
            ->create();

        Notification::factory()
            ->count(2)
            ->forUser($this->user)
            ->ofType(NotificationType::PAYMENT_FAILED)
            ->inApp()
            ->sent()
            ->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications?type=post_published');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3);
    });

    it('can filter notifications by read status - unread only', function () {
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

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications?is_read=false');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3);

        // All returned notifications should be unread
        $data = $response->json('data');
        foreach ($data as $notification) {
            expect($notification['is_read'])->toBeFalse();
        }
    });

    it('can filter notifications by read status - read only', function () {
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

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications?is_read=true');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);

        // All returned notifications should be read
        $data = $response->json('data');
        foreach ($data as $notification) {
            expect($notification['is_read'])->toBeTrue();
        }
    });

    it('paginates results correctly', function () {
        Notification::factory()
            ->count(25)
            ->forUser($this->user)
            ->inApp()
            ->sent()
            ->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications?per_page=10');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonCount(10, 'data');
    });
});

describe('GET /api/v1/notifications/unread-count', function () {
    it('returns the count of unread notifications', function () {
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

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications/unread-count');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['count'],
            ])
            ->assertJsonPath('data.count', 5);
    });

    it('returns zero when no unread notifications exist', function () {
        Notification::factory()
            ->count(3)
            ->forUser($this->user)
            ->read()
            ->inApp()
            ->sent()
            ->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications/unread-count');

        $response->assertOk()
            ->assertJsonPath('data.count', 0);
    });

    it('only counts notifications for the authenticated user', function () {
        Notification::factory()
            ->count(5)
            ->forUser($this->user)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        Notification::factory()
            ->count(10)
            ->forUser($this->otherUser)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications/unread-count');

        $response->assertOk()
            ->assertJsonPath('data.count', 5);
    });
});

describe('GET /api/v1/notifications/recent', function () {
    it('returns recent notifications', function () {
        Notification::factory()
            ->count(15)
            ->forUser($this->user)
            ->inApp()
            ->sent()
            ->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications/recent');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'title',
                        'message',
                    ],
                ],
            ]);

        // Default limit is 10
        expect(count($response->json('data')))->toBeLessThanOrEqual(10);
    });

    it('respects the limit parameter', function () {
        Notification::factory()
            ->count(10)
            ->forUser($this->user)
            ->inApp()
            ->sent()
            ->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications/recent?limit=5');

        $response->assertOk();
        expect(count($response->json('data')))->toBe(5);
    });

    it('enforces maximum limit of 50', function () {
        Notification::factory()
            ->count(60)
            ->forUser($this->user)
            ->inApp()
            ->sent()
            ->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications/recent?limit=100');

        $response->assertOk();
        expect(count($response->json('data')))->toBeLessThanOrEqual(50);
    });
});

describe('POST /api/v1/notifications/{notification}/read', function () {
    it('marks a notification as read', function () {
        $notification = Notification::factory()
            ->forUser($this->user)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $notification->refresh();
        expect($notification->read_at)->not->toBeNull();
    });

    it('returns the updated notification', function () {
        $notification = Notification::factory()
            ->forUser($this->user)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk()
            ->assertJsonPath('data.id', $notification->id)
            ->assertJsonPath('data.is_read', true);
    });

    it('prevents marking another user notification as read', function () {
        $notification = Notification::factory()
            ->forUser($this->otherUser)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertForbidden();

        $notification->refresh();
        expect($notification->read_at)->toBeNull();
    });

    it('returns 404 for non-existent notification', function () {
        Sanctum::actingAs($this->user);

        $fakeId = fake()->uuid();
        $response = $this->postJson("/api/v1/notifications/{$fakeId}/read");

        $response->assertNotFound();
    });
});

describe('POST /api/v1/notifications/read-all', function () {
    it('marks all notifications as read', function () {
        Notification::factory()
            ->count(5)
            ->forUser($this->user)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/notifications/read-all');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.marked_count', 5);

        // Verify all notifications are marked as read
        $unreadCount = Notification::forUser($this->user->id)->unread()->count();
        expect($unreadCount)->toBe(0);
    });

    it('only marks notifications for the authenticated user', function () {
        Notification::factory()
            ->count(3)
            ->forUser($this->user)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        Notification::factory()
            ->count(5)
            ->forUser($this->otherUser)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/notifications/read-all');

        $response->assertOk()
            ->assertJsonPath('data.marked_count', 3);

        // Other user's notifications should still be unread
        $otherUserUnread = Notification::forUser($this->otherUser->id)->unread()->count();
        expect($otherUserUnread)->toBe(5);
    });

    it('returns zero when no unread notifications exist', function () {
        Notification::factory()
            ->count(3)
            ->forUser($this->user)
            ->read()
            ->inApp()
            ->sent()
            ->create();

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/notifications/read-all');

        $response->assertOk()
            ->assertJsonPath('data.marked_count', 0);
    });
});

describe('POST /api/v1/notifications/read-multiple', function () {
    it('marks multiple notifications as read', function () {
        $notifications = Notification::factory()
            ->count(5)
            ->forUser($this->user)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        $idsToMark = $notifications->take(3)->pluck('id')->toArray();

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/notifications/read-multiple', [
            'ids' => $idsToMark,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.marked_count', 3);

        // Verify only specified notifications are marked as read
        $unreadCount = Notification::forUser($this->user->id)->unread()->count();
        expect($unreadCount)->toBe(2);
    });

    it('validates that ids array is required', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/notifications/read-multiple', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['ids']);
    });

    it('validates that ids are valid uuids', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/notifications/read-multiple', [
            'ids' => ['invalid-id', 'another-invalid'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['ids.0', 'ids.1']);
    });

    it('only marks notifications belonging to the user', function () {
        $userNotifications = Notification::factory()
            ->count(2)
            ->forUser($this->user)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        $otherNotifications = Notification::factory()
            ->count(2)
            ->forUser($this->otherUser)
            ->unread()
            ->inApp()
            ->sent()
            ->create();

        $allIds = $userNotifications->pluck('id')
            ->merge($otherNotifications->pluck('id'))
            ->toArray();

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/notifications/read-multiple', [
            'ids' => $allIds,
        ]);

        // Should only mark 2 (user's notifications) because validation passes for existing IDs
        // but the service only updates notifications belonging to the user
        $response->assertOk();

        // Other user's notifications should still be unread
        $otherUserUnread = Notification::forUser($this->otherUser->id)->unread()->count();
        expect($otherUserUnread)->toBe(2);
    });
});

describe('GET /api/v1/notifications/preferences', function () {
    it('returns notification preferences', function () {
        NotificationPreference::factory()
            ->forUser($this->user)
            ->ofType(NotificationType::POST_PUBLISHED)
            ->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications/preferences');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'notification_type',
                        'in_app_enabled',
                        'email_enabled',
                        'push_enabled',
                    ],
                ],
            ]);
    });

    it('returns preferences for all notification types', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications/preferences');

        $response->assertOk();

        $data = $response->json('data');
        $returnedTypes = collect($data)->pluck('notification_type')->toArray();

        // Should have preferences for all notification types
        foreach (NotificationType::cases() as $type) {
            expect($returnedTypes)->toContain($type->value);
        }
    });
});

describe('PUT /api/v1/notifications/preferences', function () {
    it('updates notification preferences', function () {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/notifications/preferences', [
            'notification_type' => NotificationType::POST_PUBLISHED->value,
            'in_app_enabled' => true,
            'email_enabled' => false,
            'push_enabled' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.notification_type', NotificationType::POST_PUBLISHED->value)
            ->assertJsonPath('data.in_app_enabled', true)
            ->assertJsonPath('data.email_enabled', false)
            ->assertJsonPath('data.push_enabled', true);

        // Verify database was updated
        $preference = NotificationPreference::forUser($this->user->id)
            ->ofType(NotificationType::POST_PUBLISHED)
            ->first();

        expect($preference->in_app_enabled)->toBeTrue();
        expect($preference->email_enabled)->toBeFalse();
        expect($preference->push_enabled)->toBeTrue();
    });

    it('validates notification type is required', function () {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/notifications/preferences', [
            'in_app_enabled' => true,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['notification_type']);
    });

    it('validates notification type is valid', function () {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/notifications/preferences', [
            'notification_type' => 'invalid_type',
            'in_app_enabled' => true,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['notification_type']);
    });

    it('creates preference if it does not exist', function () {
        Sanctum::actingAs($this->user);

        // Ensure no preference exists
        $existingPref = NotificationPreference::forUser($this->user->id)
            ->ofType(NotificationType::SYSTEM_ANNOUNCEMENT)
            ->first();
        expect($existingPref)->toBeNull();

        $response = $this->putJson('/api/v1/notifications/preferences', [
            'notification_type' => NotificationType::SYSTEM_ANNOUNCEMENT->value,
            'in_app_enabled' => true,
            'email_enabled' => false,
            'push_enabled' => false,
        ]);

        $response->assertOk();

        // Verify preference was created
        $preference = NotificationPreference::forUser($this->user->id)
            ->ofType(NotificationType::SYSTEM_ANNOUNCEMENT)
            ->first();

        expect($preference)->not->toBeNull();
        expect($preference->in_app_enabled)->toBeTrue();
        expect($preference->email_enabled)->toBeFalse();
    });
});

describe('Authentication', function () {
    it('returns 401 for unauthenticated access to notifications list', function () {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertUnauthorized();
    });

    it('returns 401 for unauthenticated access to unread count', function () {
        $response = $this->getJson('/api/v1/notifications/unread-count');

        $response->assertUnauthorized();
    });

    it('returns 401 for unauthenticated access to recent notifications', function () {
        $response = $this->getJson('/api/v1/notifications/recent');

        $response->assertUnauthorized();
    });

    it('returns 401 for unauthenticated mark as read', function () {
        $notification = Notification::factory()
            ->forUser($this->user)
            ->unread()
            ->create();

        $response = $this->postJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertUnauthorized();
    });

    it('returns 401 for unauthenticated mark all as read', function () {
        $response = $this->postJson('/api/v1/notifications/read-all');

        $response->assertUnauthorized();
    });

    it('returns 401 for unauthenticated preferences access', function () {
        $response = $this->getJson('/api/v1/notifications/preferences');

        $response->assertUnauthorized();
    });

    it('returns 401 for unauthenticated preferences update', function () {
        $response = $this->putJson('/api/v1/notifications/preferences', [
            'notification_type' => NotificationType::POST_PUBLISHED->value,
            'in_app_enabled' => true,
        ]);

        $response->assertUnauthorized();
    });
});
