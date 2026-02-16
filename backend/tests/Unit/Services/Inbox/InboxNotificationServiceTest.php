<?php

declare(strict_types=1);

use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Inbox\InboxItemType;
use App\Enums\Notification\NotificationType;
use App\Enums\Social\SocialPlatform;
use App\Models\Inbox\InboxItem;
use App\Models\Notification\Notification;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Inbox\InboxNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(InboxNotificationService::class);
});

describe('InboxNotificationService', function (): void {
    it('notifies assigned user about new message', function (): void {
        $workspace = Workspace::factory()->create();
        $assignedUser = User::factory()->create(['tenant_id' => $workspace->tenant_id]);
        $socialAccount = SocialAccount::factory()->create([
            'workspace_id' => $workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
        ]);

        $inboxItem = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $socialAccount->id,
            'assigned_to_user_id' => $assignedUser->id,
            'item_type' => InboxItemType::COMMENT,
            'status' => InboxItemStatus::UNREAD,
            'author_name' => 'John Doe',
            'content_text' => 'This is a test comment',
        ]);

        $count = $this->service->notifyNewMessage($inboxItem);

        expect($count)->toBe(1);
        
        // Verify notification was created
        $notification = Notification::forUser($assignedUser->id)
            ->ofType(NotificationType::NEW_COMMENT)
            ->first();
            
        expect($notification)->not->toBeNull();
        expect($notification->title)->toBe('New Inbox Message');
        expect($notification->action_url)->toBe("/inbox/{$inboxItem->id}");
    });

    it('notifies all workspace members when message is not assigned', function (): void {
        $workspace = Workspace::factory()->create();
        $user1 = User::factory()->create(['tenant_id' => $workspace->tenant_id]);
        $user2 = User::factory()->create(['tenant_id' => $workspace->tenant_id]);
        
        $workspace->addMember($user1, \App\Enums\Workspace\WorkspaceRole::EDITOR);
        $workspace->addMember($user2, \App\Enums\Workspace\WorkspaceRole::EDITOR);

        $socialAccount = SocialAccount::factory()->create([
            'workspace_id' => $workspace->id,
            'platform' => SocialPlatform::INSTAGRAM,
        ]);

        $inboxItem = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $socialAccount->id,
            'assigned_to_user_id' => null,
            'item_type' => InboxItemType::COMMENT,
            'status' => InboxItemStatus::UNREAD,
            'author_name' => 'Jane Smith',
            'content_text' => 'Great post!',
        ]);

        $count = $this->service->notifyNewMessage($inboxItem);

        expect($count)->toBe(2);
        
        // Verify notifications were created for both users
        expect(Notification::forUser($user1->id)->count())->toBe(1);
        expect(Notification::forUser($user2->id)->count())->toBe(1);
    });

    it('notifies assigned user when message is replied to', function (): void {
        $workspace = Workspace::factory()->create();
        $assignedUser = User::factory()->create(['tenant_id' => $workspace->tenant_id]);
        $repliedByUser = User::factory()->create(['tenant_id' => $workspace->tenant_id]);
        
        $socialAccount = SocialAccount::factory()->create([
            'workspace_id' => $workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
        ]);

        $inboxItem = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $socialAccount->id,
            'assigned_to_user_id' => $assignedUser->id,
            'item_type' => InboxItemType::COMMENT,
            'status' => InboxItemStatus::READ,
            'author_name' => 'Customer',
            'content_text' => 'I have a question',
        ]);

        $count = $this->service->notifyMessageReplied($inboxItem, $repliedByUser);

        expect($count)->toBe(1);
        
        // Verify notification was created
        $notification = Notification::forUser($assignedUser->id)
            ->ofType(NotificationType::NEW_COMMENT)
            ->first();
            
        expect($notification)->not->toBeNull();
        expect($notification->title)->toBe('Inbox Message Replied');
    });

    it('does not notify user who sent the reply', function (): void {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create(['tenant_id' => $workspace->tenant_id]);
        
        $socialAccount = SocialAccount::factory()->create([
            'workspace_id' => $workspace->id,
            'platform' => SocialPlatform::TWITTER,
        ]);

        $inboxItem = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $socialAccount->id,
            'assigned_to_user_id' => $user->id,
            'item_type' => InboxItemType::MENTION,
            'status' => InboxItemStatus::READ,
        ]);

        $count = $this->service->notifyMessageReplied($inboxItem, $user);

        expect($count)->toBe(0);
        expect(Notification::forUser($user->id)->count())->toBe(0);
    });

    it('notifies user when message is assigned to them', function (): void {
        $workspace = Workspace::factory()->create();
        $assignedTo = User::factory()->create(['tenant_id' => $workspace->tenant_id]);
        $assignedBy = User::factory()->create(['tenant_id' => $workspace->tenant_id]);
        
        $socialAccount = SocialAccount::factory()->create([
            'workspace_id' => $workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
        ]);

        $inboxItem = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $socialAccount->id,
            'item_type' => InboxItemType::COMMENT,
            'status' => InboxItemStatus::UNREAD,
            'author_name' => 'Customer',
            'content_text' => 'Need help with this',
        ]);

        $result = $this->service->notifyMessageAssigned($inboxItem, $assignedTo, $assignedBy);

        expect($result)->toBeTrue();
        
        // Verify notification was created
        $notification = Notification::forUser($assignedTo->id)
            ->ofType(NotificationType::INBOX_ASSIGNED)
            ->first();
            
        expect($notification)->not->toBeNull();
        expect($notification->title)->toBe('Inbox Message Assigned');
        expect($notification->action_url)->toBe("/inbox/{$inboxItem->id}");
    });

    it('does not notify when user assigns message to themselves', function (): void {
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
            'status' => InboxItemStatus::UNREAD,
        ]);

        $result = $this->service->notifyMessageAssigned($inboxItem, $user, $user);

        expect($result)->toBeFalse();
        expect(Notification::forUser($user->id)->count())->toBe(0);
    });

    it('truncates long content in notification preview', function (): void {
        $workspace = Workspace::factory()->create();
        $assignedUser = User::factory()->create(['tenant_id' => $workspace->tenant_id]);
        
        $socialAccount = SocialAccount::factory()->create([
            'workspace_id' => $workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
        ]);

        $longContent = str_repeat('This is a very long comment. ', 20);
        
        $inboxItem = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $socialAccount->id,
            'assigned_to_user_id' => $assignedUser->id,
            'item_type' => InboxItemType::COMMENT,
            'status' => InboxItemStatus::UNREAD,
            'author_name' => 'Verbose User',
            'content_text' => $longContent,
        ]);

        $this->service->notifyNewMessage($inboxItem);
        
        // Verify notification was created with truncated content
        $notification = Notification::forUser($assignedUser->id)->first();
        
        expect($notification)->not->toBeNull();
        $contentPreview = $notification->data['content_preview'] ?? '';
        expect(mb_strlen($contentPreview))->toBeLessThanOrEqual(103); // 100 chars + '...'
        expect($contentPreview)->toEndWith('...');
    });
});
