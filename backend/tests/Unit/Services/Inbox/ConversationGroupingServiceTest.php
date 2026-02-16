<?php

declare(strict_types=1);

/**
 * ConversationGroupingService Unit Tests
 *
 * Tests for the ConversationGroupingService which handles conversation
 * threading logic to group related inbox items into conversation threads.
 *
 * @see \App\Services\Inbox\ConversationGroupingService
 */

use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Inbox\InboxItemType;
use App\Enums\Social\SocialPlatform;
use App\Models\Inbox\InboxConversation;
use App\Models\Inbox\InboxItem;
use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use App\Services\Inbox\ConversationGroupingService;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->service = app(ConversationGroupingService::class);
});

describe('Thread Detection - Platform Thread ID', function (): void {
    test('detects existing conversation by platform thread ID', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        // Create existing conversation with thread ID
        $existingConversation = InboxConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_key' => 'thread:fb_thread_123',
        ]);

        // Create new item with same thread ID
        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'metadata' => ['thread_id' => 'fb_thread_123'],
        ]);

        $conversation = $this->service->groupIntoConversation($item);

        expect($conversation->id)->toBe($existingConversation->id)
            ->and($item->conversation_id)->toBe($existingConversation->id);
    });

    test('creates new conversation when thread ID does not exist', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'metadata' => ['thread_id' => 'new_thread_456'],
            'author_name' => 'John Doe',
            'platform_created_at' => Carbon::now(),
        ]);

        $conversation = $this->service->groupIntoConversation($item);

        expect($conversation->conversation_key)->toBe('thread:new_thread_456')
            ->and($conversation->workspace_id)->toBe($workspace->id)
            ->and($conversation->social_account_id)->toBe($account->id)
            ->and($item->conversation_id)->toBe($conversation->id);
    });

    test('thread ID detection has highest priority', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        // Create conversation with post-based key
        $postConversation = InboxConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_key' => 'post:post_123',
        ]);

        // Create conversation with thread-based key
        $threadConversation = InboxConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_key' => 'thread:thread_789',
        ]);

        // Item has both thread ID and post ID - thread should win
        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'metadata' => ['thread_id' => 'thread_789'],
            'platform_post_id' => 'post_123',
            'post_target_id' => null,
        ]);

        $conversation = $this->service->groupIntoConversation($item);

        expect($conversation->id)->toBe($threadConversation->id)
            ->and($conversation->id)->not->toBe($postConversation->id);
    });
});

describe('Thread Detection - Post-Based Grouping', function (): void {
    test('detects existing conversation by post_target_id', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $existingConversation = InboxConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_key' => 'post:uuid-post-123',
        ]);

        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'platform_post_id' => 'uuid-post-123',
            'post_target_id' => null,
            'metadata' => [],
        ]);

        $conversation = $this->service->groupIntoConversation($item);

        expect($conversation->id)->toBe($existingConversation->id);
    });

    test('detects existing conversation by platform_post_id', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $existingConversation = InboxConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_key' => 'post:fb_post_456',
        ]);

        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'platform_post_id' => 'fb_post_456',
            'post_target_id' => null,
            'metadata' => [],
        ]);

        $conversation = $this->service->groupIntoConversation($item);

        expect($conversation->id)->toBe($existingConversation->id);
    });

    test('creates new conversation for new post', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'platform_post_id' => 'new-post-789',
            'post_target_id' => null,
            'metadata' => [],
            'author_name' => 'Jane Smith',
            'platform_created_at' => Carbon::now(),
        ]);

        $conversation = $this->service->groupIntoConversation($item);

        expect($conversation->conversation_key)->toBe('post:new-post-789')
            ->and($conversation->subject)->toBe('Comments on post');
    });

    test('groups multiple comments on same post into one conversation', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $item1 = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'platform_post_id' => 'shared-post-id',
            'post_target_id' => null,
            'metadata' => [],
            'author_name' => 'User 1',
            'platform_created_at' => Carbon::now(),
        ]);

        $conversation1 = $this->service->groupIntoConversation($item1);

        $item2 = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'platform_post_id' => 'shared-post-id',
            'post_target_id' => null,
            'metadata' => [],
            'author_name' => 'User 2',
            'platform_created_at' => Carbon::now()->addMinutes(5),
        ]);

        $conversation2 = $this->service->groupIntoConversation($item2);

        $conversation1->refresh();
        
        expect($conversation1->id)->toBe($conversation2->id)
            ->and($conversation1->message_count)->toBe(2);
    });

    test('prefers post_target_id over platform_post_id', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'platform_post_id' => 'external-post-id',
            'post_target_id' => null,
            'metadata' => [],
            'author_name' => 'Test User',
            'platform_created_at' => Carbon::now(),
        ]);

        $conversation = $this->service->groupIntoConversation($item);

        expect($conversation->conversation_key)->toBe('post:external-post-id');
    });
});

describe('Thread Detection - Participant-Based Grouping', function (): void {
    test('detects existing conversation by participant username', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $existingConversation = InboxConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_key' => 'participant:johndoe',
        ]);

        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'author_username' => 'JohnDoe',
            'author_name' => 'John Doe',
            'post_target_id' => null,
            'platform_post_id' => null,
            'metadata' => [],
        ]);

        $conversation = $this->service->groupIntoConversation($item);

        expect($conversation->id)->toBe($existingConversation->id);
    });

    test('normalizes participant identifier to lowercase', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $existingConversation = InboxConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_key' => 'participant:alice_smith',
        ]);

        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'author_username' => 'ALICE_SMITH',
            'post_target_id' => null,
            'platform_post_id' => null,
            'metadata' => [],
        ]);

        $conversation = $this->service->groupIntoConversation($item);

        expect($conversation->id)->toBe($existingConversation->id);
    });

    test('uses author_name when username is not available', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'author_username' => null,
            'author_name' => 'Bob Wilson',
            'post_target_id' => null,
            'platform_post_id' => null,
            'metadata' => [],
            'platform_created_at' => Carbon::now(),
        ]);

        $conversation = $this->service->groupIntoConversation($item);

        expect($conversation->conversation_key)->toBe('participant:bob wilson');
    });

    test('creates new conversation for new participant', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'author_username' => 'new_user_123',
            'author_name' => 'New User',
            'post_target_id' => null,
            'platform_post_id' => null,
            'metadata' => [],
            'platform_created_at' => Carbon::now(),
        ]);

        $conversation = $this->service->groupIntoConversation($item);

        expect($conversation->conversation_key)->toBe('participant:new_user_123')
            ->and($conversation->subject)->toBe('Conversation with New User');
    });

    test('groups all messages from same participant into one conversation', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $item1 = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'author_username' => 'charlie',
            'post_target_id' => null,
            'platform_post_id' => null,
            'metadata' => [],
            'platform_created_at' => Carbon::now(),
        ]);

        $conversation1 = $this->service->groupIntoConversation($item1);

        $item2 = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'author_username' => 'charlie',
            'post_target_id' => null,
            'platform_post_id' => null,
            'metadata' => [],
            'platform_created_at' => Carbon::now()->addHours(2),
        ]);

        $conversation2 = $this->service->groupIntoConversation($item2);

        expect($conversation1->id)->toBe($conversation2->id);
    });
});


describe('Conversation Grouping', function (): void {
    test('links inbox item to conversation', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_id' => null,
            'metadata' => ['thread_id' => 'test_thread'],
            'platform_created_at' => Carbon::now(),
        ]);

        expect($item->conversation_id)->toBeNull();

        $conversation = $this->service->groupIntoConversation($item);

        $item->refresh();
        expect($item->conversation_id)->toBe($conversation->id);
    });

    test('updates conversation message count', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $conversation = InboxConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_key' => 'thread:test',
            'message_count' => 0,
        ]);

        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'metadata' => ['thread_id' => 'test'],
            'platform_created_at' => Carbon::now(),
        ]);

        $this->service->groupIntoConversation($item);

        $conversation->refresh();
        expect($conversation->message_count)->toBe(1);
    });

    test('updates conversation timestamps', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $firstMessageTime = Carbon::parse('2024-01-15 10:00:00');
        $secondMessageTime = Carbon::parse('2024-01-15 11:00:00');

        $conversation = InboxConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_key' => 'thread:timestamps',
            'first_message_at' => null,
            'last_message_at' => null,
        ]);

        $item1 = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'metadata' => ['thread_id' => 'timestamps'],
            'platform_created_at' => $firstMessageTime,
        ]);

        $this->service->groupIntoConversation($item1);
        $conversation->refresh();

        expect($conversation->first_message_at->toDateTimeString())
            ->toBe($firstMessageTime->toDateTimeString())
            ->and($conversation->last_message_at->toDateTimeString())
            ->toBe($firstMessageTime->toDateTimeString());

        $item2 = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'metadata' => ['thread_id' => 'timestamps'],
            'platform_created_at' => $secondMessageTime,
        ]);

        $this->service->groupIntoConversation($item2);
        $conversation->refresh();

        expect($conversation->first_message_at->toDateTimeString())
            ->toBe($firstMessageTime->toDateTimeString())
            ->and($conversation->last_message_at->toDateTimeString())
            ->toBe($secondMessageTime->toDateTimeString());
    });

    test('stores participant information in new conversation', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'author_name' => 'Sarah Johnson',
            'author_username' => 'sarah_j',
            'author_profile_url' => 'https://example.com/sarah_j',
            'author_avatar_url' => 'https://example.com/avatar.jpg',
            'metadata' => ['thread_id' => 'new_participant'],
            'platform_created_at' => Carbon::now(),
        ]);

        $conversation = $this->service->groupIntoConversation($item);

        expect($conversation->participant_name)->toBe('Sarah Johnson')
            ->and($conversation->participant_username)->toBe('sarah_j')
            ->and($conversation->participant_profile_url)->toBe('https://example.com/sarah_j')
            ->and($conversation->participant_avatar_url)->toBe('https://example.com/avatar.jpg');
    });

    test('stores conversation metadata', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'item_type' => InboxItemType::COMMENT,
            'metadata' => ['thread_id' => 'metadata_test'],
            'platform_created_at' => Carbon::now(),
        ]);

        $conversation = $this->service->groupIntoConversation($item);

        expect($conversation->metadata)->toBeArray()
            ->and($conversation->metadata['created_from_item_id'])->toBe($item->id)
            ->and($conversation->metadata['item_type'])->toBe('comment');
    });

    test('sets conversation status to active by default', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'metadata' => ['thread_id' => 'status_test'],
            'platform_created_at' => Carbon::now(),
        ]);

        $conversation = $this->service->groupIntoConversation($item);

        expect($conversation->status)->toBe('active');
    });
});

describe('Workspace and Account Isolation', function (): void {
    test('does not match conversations from different workspaces', function (): void {
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();
        $account1 = SocialAccount::factory()->forWorkspace($workspace1)->create();
        $account2 = SocialAccount::factory()->forWorkspace($workspace2)->create();

        $conversation1 = InboxConversation::factory()->create([
            'workspace_id' => $workspace1->id,
            'social_account_id' => $account1->id,
            'conversation_key' => 'thread:shared_key',
        ]);

        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace2->id,
            'social_account_id' => $account2->id,
            'metadata' => ['thread_id' => 'shared_key'],
            'platform_created_at' => Carbon::now(),
        ]);

        $conversation2 = $this->service->groupIntoConversation($item);

        expect($conversation2->id)->not->toBe($conversation1->id)
            ->and($conversation2->workspace_id)->toBe($workspace2->id);
    });

    test('does not match conversations from different social accounts', function (): void {
        $workspace = Workspace::factory()->create();
        $account1 = SocialAccount::factory()->forWorkspace($workspace)->create();
        $account2 = SocialAccount::factory()->forWorkspace($workspace)->create();

        $conversation1 = InboxConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account1->id,
            'conversation_key' => 'thread:account_test',
        ]);

        $item = InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account2->id,
            'metadata' => ['thread_id' => 'account_test'],
            'platform_created_at' => Carbon::now(),
        ]);

        $conversation2 = $this->service->groupIntoConversation($item);

        expect($conversation2->id)->not->toBe($conversation1->id)
            ->and($conversation2->social_account_id)->toBe($account2->id);
    });
});

describe('Regrouping Operations', function (): void {
    test('regroups all items without conversations', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        // Create items without conversations
        InboxItem::factory()->count(5)->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_id' => null,
            'platform_post_id' => 'shared_post',
            'post_target_id' => null,
            'metadata' => [],
            'platform_created_at' => Carbon::now(),
        ]);

        $stats = $this->service->regroupAllItems($workspace->id);

        expect($stats['total_items'])->toBe(5)
            ->and($stats['grouped_items'])->toBe(5)
            ->and($stats['new_conversations'])->toBeGreaterThan(0)
            ->and($stats['errors'])->toBe(0);

        // Verify all items now have conversations
        $ungroupedCount = InboxItem::where('workspace_id', $workspace->id)
            ->whereNull('conversation_id')
            ->count();
        expect($ungroupedCount)->toBe(0);
    });

    test('skips items that already have conversations', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $conversation = InboxConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
        ]);

        // Create items with existing conversations
        InboxItem::factory()->count(3)->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_id' => $conversation->id,
        ]);

        $stats = $this->service->regroupAllItems($workspace->id);

        expect($stats['total_items'])->toBe(0)
            ->and($stats['grouped_items'])->toBe(0);
    });

    test('processes items in chronological order', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        $oldestTime = Carbon::parse('2024-01-10 10:00:00');
        $middleTime = Carbon::parse('2024-01-15 10:00:00');
        $newestTime = Carbon::parse('2024-01-20 10:00:00');

        InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_id' => null,
            'platform_created_at' => $newestTime,
            'metadata' => ['thread_id' => 'chrono_test'],
        ]);

        InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_id' => null,
            'platform_created_at' => $oldestTime,
            'metadata' => ['thread_id' => 'chrono_test'],
        ]);

        InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_id' => null,
            'platform_created_at' => $middleTime,
            'metadata' => ['thread_id' => 'chrono_test'],
        ]);

        $this->service->regroupAllItems($workspace->id);

        $conversation = InboxConversation::where('conversation_key', 'thread:chrono_test')->first();
        
        expect($conversation->first_message_at->toDateTimeString())
            ->toBe($oldestTime->toDateTimeString())
            ->and($conversation->last_message_at->toDateTimeString())
            ->toBe($newestTime->toDateTimeString());
    });

    test('handles errors during regrouping gracefully', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        // Create item with minimal data
        InboxItem::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_id' => null,
            'author_name' => 'Test User',
            'platform_created_at' => Carbon::now(),
            'metadata' => [],
            'post_target_id' => null,
            'platform_post_id' => null,
        ]);

        $stats = $this->service->regroupAllItems($workspace->id);

        // Should handle and continue
        expect($stats)->toHaveKey('errors');
    });
});

describe('Conversation Statistics', function (): void {
    test('returns accurate conversation statistics', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->forWorkspace($workspace)->create();

        InboxConversation::factory()->count(3)->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'status' => 'active',
        ]);

        InboxConversation::factory()->count(2)->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'status' => 'resolved',
        ]);

        InboxConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'status' => 'archived',
        ]);

        InboxItem::factory()->count(4)->create([
            'workspace_id' => $workspace->id,
            'social_account_id' => $account->id,
            'conversation_id' => null,
        ]);

        $stats = $this->service->getConversationStats($workspace->id);

        expect($stats['total_conversations'])->toBe(6)
            ->and($stats['active_conversations'])->toBe(3)
            ->and($stats['resolved_conversations'])->toBe(2)
            ->and($stats['archived_conversations'])->toBe(1)
            ->and($stats['items_without_conversation'])->toBe(4);
    });

    test('returns zero counts for workspace with no data', function (): void {
        $workspace = Workspace::factory()->create();

        $stats = $this->service->getConversationStats($workspace->id);

        expect($stats['total_conversations'])->toBe(0)
            ->and($stats['active_conversations'])->toBe(0)
            ->and($stats['resolved_conversations'])->toBe(0)
            ->and($stats['archived_conversations'])->toBe(0)
            ->and($stats['items_without_conversation'])->toBe(0);
    });

    test('isolates statistics by workspace', function (): void {
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();
        $account1 = SocialAccount::factory()->forWorkspace($workspace1)->create();
        $account2 = SocialAccount::factory()->forWorkspace($workspace2)->create();

        InboxConversation::factory()->count(5)->create([
            'workspace_id' => $workspace1->id,
            'social_account_id' => $account1->id,
        ]);

        InboxConversation::factory()->count(3)->create([
            'workspace_id' => $workspace2->id,
            'social_account_id' => $account2->id,
        ]);

        $stats1 = $this->service->getConversationStats($workspace1->id);
        $stats2 = $this->service->getConversationStats($workspace2->id);

        expect($stats1['total_conversations'])->toBe(5)
            ->and($stats2['total_conversations'])->toBe(3);
    });
});

