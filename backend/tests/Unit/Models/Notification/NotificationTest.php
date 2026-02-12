<?php

declare(strict_types=1);

/**
 * Notification Model Unit Tests
 *
 * Tests for the Notification model which represents notifications
 * sent to users within a tenant.
 *
 * @see \App\Models\Notification\Notification
 */

use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationType;
use App\Models\Notification\Notification;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('has correct table name', function (): void {
    $notification = new Notification();

    expect($notification->getTable())->toBe('notifications');
});

test('uses uuid primary key', function (): void {
    $notification = Notification::factory()->create();

    expect($notification->id)->not->toBeNull()
        ->and(strlen($notification->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $notification = new Notification();
    $fillable = $notification->getFillable();

    expect($fillable)->toContain('tenant_id')
        ->and($fillable)->toContain('user_id')
        ->and($fillable)->toContain('type')
        ->and($fillable)->toContain('channel')
        ->and($fillable)->toContain('title')
        ->and($fillable)->toContain('message')
        ->and($fillable)->toContain('data')
        ->and($fillable)->toContain('action_url')
        ->and($fillable)->toContain('icon')
        ->and($fillable)->toContain('read_at')
        ->and($fillable)->toContain('sent_at')
        ->and($fillable)->toContain('failed_at')
        ->and($fillable)->toContain('failure_reason');
});

test('type casts to NotificationType enum', function (): void {
    $notification = Notification::factory()
        ->ofType(NotificationType::POST_PUBLISHED)
        ->create();

    expect($notification->type)->toBeInstanceOf(NotificationType::class)
        ->and($notification->type)->toBe(NotificationType::POST_PUBLISHED);
});

test('channel casts to NotificationChannel enum', function (): void {
    $notification = Notification::factory()->inApp()->create();

    expect($notification->channel)->toBeInstanceOf(NotificationChannel::class)
        ->and($notification->channel)->toBe(NotificationChannel::IN_APP);
});

test('data casts to array', function (): void {
    $data = [
        'post_id' => fake()->uuid(),
        'platform' => 'twitter',
    ];

    $notification = Notification::factory()->withData($data)->create();

    expect($notification->data)->toBeArray()
        ->and($notification->data['post_id'])->toBe($data['post_id'])
        ->and($notification->data['platform'])->toBe('twitter');
});

test('read_at casts to datetime', function (): void {
    $notification = Notification::factory()->read()->create();

    expect($notification->read_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('sent_at casts to datetime', function (): void {
    $notification = Notification::factory()->sent()->create();

    expect($notification->sent_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('failed_at casts to datetime', function (): void {
    $notification = Notification::factory()->failed()->create();

    expect($notification->failed_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('user relationship returns belongs to', function (): void {
    $notification = new Notification();

    expect($notification->user())->toBeInstanceOf(BelongsTo::class);
});

test('user relationship works correctly', function (): void {
    $user = User::factory()->create();
    $notification = Notification::factory()->forUser($user)->create();

    expect($notification->user)->toBeInstanceOf(User::class)
        ->and($notification->user->id)->toBe($user->id);
});

test('tenant relationship returns belongs to', function (): void {
    $notification = new Notification();

    expect($notification->tenant())->toBeInstanceOf(BelongsTo::class);
});

test('tenant relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    $notification = Notification::factory()->forUser($user)->create();

    expect($notification->tenant)->toBeInstanceOf(Tenant::class)
        ->and($notification->tenant->id)->toBe($tenant->id);
});

describe('scopes', function () {
    test('unread scope returns only unread notifications', function (): void {
        $user = User::factory()->create();
        Notification::factory()->count(3)->forUser($user)->unread()->create();
        Notification::factory()->count(2)->forUser($user)->read()->create();

        $unread = Notification::unread()->get();

        expect($unread)->toHaveCount(3)
            ->and($unread->every(fn ($n) => $n->read_at === null))->toBeTrue();
    });

    test('read scope returns only read notifications', function (): void {
        $user = User::factory()->create();
        Notification::factory()->count(2)->forUser($user)->unread()->create();
        Notification::factory()->count(3)->forUser($user)->read()->create();

        $read = Notification::read()->get();

        expect($read)->toHaveCount(3)
            ->and($read->every(fn ($n) => $n->read_at !== null))->toBeTrue();
    });

    test('ofType scope filters by notification type', function (): void {
        $user = User::factory()->create();
        Notification::factory()
            ->count(2)
            ->forUser($user)
            ->ofType(NotificationType::POST_PUBLISHED)
            ->create();
        Notification::factory()
            ->count(3)
            ->forUser($user)
            ->ofType(NotificationType::PAYMENT_FAILED)
            ->create();

        $postPublished = Notification::ofType(NotificationType::POST_PUBLISHED)->get();

        expect($postPublished)->toHaveCount(2)
            ->and($postPublished->every(fn ($n) => $n->type === NotificationType::POST_PUBLISHED))->toBeTrue();
    });

    test('ofChannel scope filters by channel', function (): void {
        $user = User::factory()->create();
        Notification::factory()->count(3)->forUser($user)->inApp()->create();
        Notification::factory()->count(2)->forUser($user)->email()->create();

        $inApp = Notification::ofChannel(NotificationChannel::IN_APP)->get();

        expect($inApp)->toHaveCount(3)
            ->and($inApp->every(fn ($n) => $n->channel === NotificationChannel::IN_APP))->toBeTrue();
    });

    test('sent scope returns only sent notifications', function (): void {
        $user = User::factory()->create();
        Notification::factory()->count(3)->forUser($user)->sent()->create();
        Notification::factory()->count(2)->forUser($user)->pending()->create();

        $sent = Notification::sent()->get();

        expect($sent)->toHaveCount(3)
            ->and($sent->every(fn ($n) => $n->sent_at !== null))->toBeTrue();
    });

    test('pending scope returns only pending notifications', function (): void {
        $user = User::factory()->create();
        Notification::factory()->count(2)->forUser($user)->pending()->create();
        Notification::factory()->count(3)->forUser($user)->sent()->create();

        $pending = Notification::pending()->get();

        expect($pending)->toHaveCount(2)
            ->and($pending->every(fn ($n) => $n->sent_at === null && $n->failed_at === null))->toBeTrue();
    });

    test('failed scope returns only failed notifications', function (): void {
        $user = User::factory()->create();
        Notification::factory()->count(2)->forUser($user)->failed()->create();
        Notification::factory()->count(3)->forUser($user)->sent()->create();

        $failed = Notification::failed()->get();

        expect($failed)->toHaveCount(2)
            ->and($failed->every(fn ($n) => $n->failed_at !== null))->toBeTrue();
    });

    test('forUser scope filters by user id', function (): void {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Notification::factory()->count(3)->forUser($user1)->create();
        Notification::factory()->count(2)->forUser($user2)->create();

        $user1Notifications = Notification::forUser($user1->id)->get();

        expect($user1Notifications)->toHaveCount(3)
            ->and($user1Notifications->every(fn ($n) => $n->user_id === $user1->id))->toBeTrue();
    });

    test('recent scope returns notifications within days', function (): void {
        $user = User::factory()->create();
        Notification::factory()->count(2)->forUser($user)->recent(5)->create();
        Notification::factory()->count(3)->forUser($user)->old(60)->create();

        $recent = Notification::recent(30)->get();

        expect($recent)->toHaveCount(2);
    });
});

describe('helper methods', function () {
    test('isRead returns true only when read_at is set', function (): void {
        $unread = Notification::factory()->unread()->create();
        $read = Notification::factory()->read()->create();

        expect($unread->isRead())->toBeFalse()
            ->and($read->isRead())->toBeTrue();
    });

    test('isSent returns true only when sent_at is set', function (): void {
        $pending = Notification::factory()->pending()->create();
        $sent = Notification::factory()->sent()->create();

        expect($pending->isSent())->toBeFalse()
            ->and($sent->isSent())->toBeTrue();
    });

    test('hasFailed returns true only when failed_at is set', function (): void {
        $sent = Notification::factory()->sent()->create();
        $failed = Notification::factory()->failed()->create();

        expect($sent->hasFailed())->toBeFalse()
            ->and($failed->hasFailed())->toBeTrue();
    });

    test('isPending returns true only when not sent and not failed', function (): void {
        $pending = Notification::factory()->pending()->create();
        $sent = Notification::factory()->sent()->create();
        $failed = Notification::factory()->failed()->create();

        expect($pending->isPending())->toBeTrue()
            ->and($sent->isPending())->toBeFalse()
            ->and($failed->isPending())->toBeFalse();
    });

    test('getDataValue retrieves value using dot notation', function (): void {
        $notification = Notification::factory()->withData([
            'post' => [
                'id' => 'post-123',
                'title' => 'Test Post',
            ],
            'platform' => 'twitter',
        ])->create();

        expect($notification->getDataValue('post.id'))->toBe('post-123')
            ->and($notification->getDataValue('platform'))->toBe('twitter')
            ->and($notification->getDataValue('nonexistent', 'default'))->toBe('default');
    });

    test('getIcon returns custom icon when set', function (): void {
        $notification = Notification::factory()
            ->ofType(NotificationType::POST_PUBLISHED)
            ->create(['icon' => 'custom-icon']);

        expect($notification->getIcon())->toBe('custom-icon');
    });

    test('getIcon returns default icon when not set', function (): void {
        $notification = Notification::factory()
            ->ofType(NotificationType::POST_PUBLISHED)
            ->create(['icon' => null]);

        expect($notification->getIcon())->toBe(NotificationType::POST_PUBLISHED->defaultIcon());
    });

    test('isUrgent delegates to notification type', function (): void {
        $urgent = Notification::factory()
            ->ofType(NotificationType::PAYMENT_FAILED)
            ->create();

        $notUrgent = Notification::factory()
            ->ofType(NotificationType::POST_PUBLISHED)
            ->create();

        expect($urgent->isUrgent())->toBeTrue()
            ->and($notUrgent->isUrgent())->toBeFalse();
    });

    test('getCategory returns notification type category', function (): void {
        $notification = Notification::factory()
            ->ofType(NotificationType::POST_PUBLISHED)
            ->create();

        expect($notification->getCategory())->toBe('content');
    });
});

describe('markAsRead', function () {
    test('sets read_at timestamp', function (): void {
        $notification = Notification::factory()->unread()->create();

        expect($notification->read_at)->toBeNull();

        $notification->markAsRead();

        expect($notification->read_at)->not->toBeNull()
            ->and($notification->read_at)->toBeInstanceOf(\Carbon\Carbon::class);
    });

    test('does not update if already read', function (): void {
        $originalReadAt = now()->subHours(2);
        $notification = Notification::factory()->create([
            'read_at' => $originalReadAt,
        ]);

        $notification->markAsRead();

        expect($notification->read_at->format('Y-m-d H:i:s'))
            ->toBe($originalReadAt->format('Y-m-d H:i:s'));
    });
});

describe('markAsUnread', function () {
    test('clears read_at timestamp', function (): void {
        $notification = Notification::factory()->read()->create();

        expect($notification->read_at)->not->toBeNull();

        $notification->markAsUnread();
        $notification->refresh();

        expect($notification->read_at)->toBeNull();
    });

    test('does nothing if already unread', function (): void {
        $notification = Notification::factory()->unread()->create();

        $notification->markAsUnread();
        $notification->refresh();

        expect($notification->read_at)->toBeNull();
    });
});

describe('markAsSent', function () {
    test('sets sent_at timestamp', function (): void {
        $notification = Notification::factory()->pending()->create();

        expect($notification->sent_at)->toBeNull();

        $notification->markAsSent();
        $notification->refresh();

        expect($notification->sent_at)->not->toBeNull();
    });
});

describe('markAsFailed', function () {
    test('sets failed_at timestamp', function (): void {
        $notification = Notification::factory()->pending()->create();

        $notification->markAsFailed();
        $notification->refresh();

        expect($notification->failed_at)->not->toBeNull();
    });

    test('sets failure reason when provided', function (): void {
        $notification = Notification::factory()->pending()->create();

        $notification->markAsFailed('Connection timeout');
        $notification->refresh();

        expect($notification->failure_reason)->toBe('Connection timeout');
    });

    test('sets null failure reason when not provided', function (): void {
        $notification = Notification::factory()->pending()->create();

        $notification->markAsFailed();
        $notification->refresh();

        expect($notification->failure_reason)->toBeNull();
    });
});

describe('createForUser static method', function () {
    test('creates notification for user with required fields', function (): void {
        $user = User::factory()->create();

        $notification = Notification::createForUser(
            user: $user,
            type: NotificationType::POST_PUBLISHED,
            title: 'Post Published',
            message: 'Your post has been published.',
        );

        expect($notification)->toBeInstanceOf(Notification::class)
            ->and($notification->user_id)->toBe($user->id)
            ->and($notification->tenant_id)->toBe($user->tenant_id)
            ->and($notification->type)->toBe(NotificationType::POST_PUBLISHED)
            ->and($notification->channel)->toBe(NotificationChannel::IN_APP)
            ->and($notification->title)->toBe('Post Published')
            ->and($notification->message)->toBe('Your post has been published.');
    });

    test('creates notification with optional parameters', function (): void {
        $user = User::factory()->create();
        $data = ['post_id' => fake()->uuid()];

        $notification = Notification::createForUser(
            user: $user,
            type: NotificationType::POST_PUBLISHED,
            title: 'Post Published',
            message: 'Your post has been published.',
            channel: NotificationChannel::EMAIL,
            data: $data,
            actionUrl: 'https://example.com/posts/123',
            icon: 'custom-icon',
        );

        expect($notification->channel)->toBe(NotificationChannel::EMAIL)
            ->and($notification->data)->toBe($data)
            ->and($notification->action_url)->toBe('https://example.com/posts/123')
            ->and($notification->icon)->toBe('custom-icon');
    });
});

test('factory creates valid model', function (): void {
    $notification = Notification::factory()->create();

    expect($notification)->toBeInstanceOf(Notification::class)
        ->and($notification->id)->not->toBeNull()
        ->and($notification->tenant_id)->not->toBeNull()
        ->and($notification->user_id)->not->toBeNull()
        ->and($notification->type)->toBeInstanceOf(NotificationType::class)
        ->and($notification->channel)->toBeInstanceOf(NotificationChannel::class)
        ->and($notification->title)->toBeString()
        ->and($notification->message)->toBeString();
});
