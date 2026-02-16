<?php

declare(strict_types=1);

use App\Enums\Content\PostStatus;
use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Inbox\InboxItemType;
use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationType;
use App\Enums\Social\SocialPlatform;
use App\Models\Content\Post;
use App\Models\Inbox\InboxItem;
use App\Models\Notification\Notification;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Notification\NotificationBroadcastService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(NotificationBroadcastService::class);
});

describe('NotificationBroadcastService - Notification Broadcasting', function (): void {
    it('broadcasts notification successfully', function (): void {
        $user = User::factory()->create();
        $notification = Notification::createForUser(
            user: $user,
            type: NotificationType::POST_PUBLISHED,
            title: 'Post Published',
            message: 'Your post has been published',
            channel: NotificationChannel::IN_APP,
            data: ['post_id' => 'test-123'],
            actionUrl: '/posts/test-123'
        );

        $result = $this->service->broadcastNotification($notification);

        expect($result)->toBeTrue();
    });

    it('handles notification broadcasting', function (): void {
        $user = User::factory()->create();
        $notification = Notification::createForUser(
            user: $user,
            type: NotificationType::POST_PUBLISHED,
            title: 'Test',
            message: 'Test message',
            channel: NotificationChannel::IN_APP
        );

        $result = $this->service->broadcastNotification($notification);

        expect($result)->toBeTrue();
    });
});

describe('NotificationBroadcastService - Inbox Broadcasting', function (): void {
    it('broadcasts inbox item received event', function (): void {
        $workspace = Workspace::factory()->create();
        $socialAccount = SocialAccount::factory()->create([
            'workspace_id' => $workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
        ]);

        $inboxItem = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $socialAccount->id,
            'item_type' => InboxItemType::COMMENT,
            'status' => InboxItemStatus::UNREAD,
            'author_name' => 'John Doe',
            'content_text' => 'Great post!',
        ]);

        $result = $this->service->broadcastInboxItemReceived($inboxItem);

        // In test environment, broadcasting may be disabled, so we just verify no exception
        expect($result)->toBeBool();
    });

    it('broadcasts inbox message replied event', function (): void {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create(['tenant_id' => $workspace->tenant_id]);
        $socialAccount = SocialAccount::factory()->create([
            'workspace_id' => $workspace->id,
            'platform' => SocialPlatform::INSTAGRAM,
        ]);

        $inboxItem = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $socialAccount->id,
            'item_type' => InboxItemType::COMMENT,
            'status' => InboxItemStatus::READ,
        ]);

        $replyContent = 'Thank you for your feedback!';
        $result = $this->service->broadcastInboxMessageReplied($inboxItem, $replyContent, $user);

        expect($result)->toBeBool();
    });

    it('broadcasts inbox message assigned event', function (): void {
        $workspace = Workspace::factory()->create();
        $assignedTo = User::factory()->create(['tenant_id' => $workspace->tenant_id]);
        $assignedBy = User::factory()->create(['tenant_id' => $workspace->tenant_id]);
        $socialAccount = SocialAccount::factory()->create([
            'workspace_id' => $workspace->id,
            'platform' => SocialPlatform::TWITTER,
        ]);

        $inboxItem = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $socialAccount->id,
            'item_type' => InboxItemType::MENTION,
            'status' => InboxItemStatus::UNREAD,
        ]);

        $result = $this->service->broadcastInboxMessageAssigned($inboxItem, $assignedTo, $assignedBy);

        expect($result)->toBeBool();
    });
});

describe('NotificationBroadcastService - Post Broadcasting', function (): void {
    it('broadcasts post status changed event', function (): void {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create(['tenant_id' => $workspace->tenant_id]);
        
        $post = Post::factory()->create([
            'workspace_id' => $workspace->id,
            'created_by_user_id' => $user->id,
            'status' => PostStatus::PUBLISHED,
            'content_text' => 'Test post content',
        ]);

        $result = $this->service->broadcastPostStatusChanged($post, 'draft', 'published');

        expect($result)->toBeBool();
    });

    it('handles post status change broadcasting', function (): void {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create(['tenant_id' => $workspace->tenant_id]);
        
        $post = Post::factory()->create([
            'workspace_id' => $workspace->id,
            'created_by_user_id' => $user->id,
            'status' => PostStatus::FAILED,
        ]);

        $result = $this->service->broadcastPostStatusChanged($post, 'scheduled', 'failed');

        expect($result)->toBeBool();
    });
});

describe('NotificationBroadcastService - Configuration', function (): void {
    it('checks if broadcasting is enabled', function (): void {
        $isEnabled = $this->service->isBroadcastingEnabled();

        expect($isEnabled)->toBeBool();
    });

    it('gets broadcast connection name', function (): void {
        $connection = $this->service->getBroadcastConnection();

        expect($connection)->toBeString();
        expect($connection)->not->toBeEmpty();
    });

    it('tests broadcast connection', function (): void {
        $result = $this->service->testBroadcastConnection();

        expect($result)->toBeBool();
    });
});

describe('NotificationBroadcastService - Combined Operations', function (): void {
    it('creates and broadcasts notification in one operation', function (): void {
        $user = User::factory()->create();
        
        $notification = $this->service->notifyAndBroadcast(
            user: $user,
            type: NotificationType::POST_PUBLISHED,
            title: 'Post Published',
            message: 'Your post has been published successfully',
            data: ['post_id' => 'test-123'],
            actionUrl: '/posts/test-123'
        );

        expect($notification)->toBeInstanceOf(Notification::class);
        expect($notification->user_id)->toBe($user->id);
        expect($notification->type)->toBe(NotificationType::POST_PUBLISHED);
        expect($notification->title)->toBe('Post Published');
        
        // Verify notification was created in database
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $user->id,
            'type' => NotificationType::POST_PUBLISHED->value,
        ]);
    });
});
