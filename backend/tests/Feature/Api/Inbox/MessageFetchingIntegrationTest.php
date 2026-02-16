<?php

declare(strict_types=1);

use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Social\SocialPlatform;
use App\Models\Inbox\InboxItem;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->workspace = Workspace::factory()->create(['tenant_id' => $this->tenant->id]);
    
    $this->actingAs($this->user);
});

describe('Message Fetching Integration Tests', function () {
    it('fetches messages from Facebook and stores in database', function () {
        $account = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
            'platform_account_id' => 'test_page_123',
            'access_token' => 'test_access_token',
            'is_active' => true,
        ]);

        Http::fake([
            'graph.facebook.com/*/posts*' => Http::response([
                'data' => [
                    ['id' => 'post_1'],
                ],
            ], 200),
            'graph.facebook.com/*/comments*' => Http::response([
                'data' => [
                    [
                        'id' => 'comment_123',
                        'message' => 'Great post!',
                        'from' => [
                            'name' => 'John Doe',
                            'id' => 'user_456',
                        ],
                        'created_time' => '2024-01-01T12:00:00+0000',
                    ],
                ],
            ], 200),
        ]);

        $service = app(\App\Services\Inbox\MessageFetchingService::class);
        $result = $service->fetchMessagesForAccount($account);

        expect($result['success'])->toBeTrue();
        expect($result['fetched'])->toBe(1);

        $item = InboxItem::where('platform_item_id', 'comment_123')->first();
        expect($item)->not->toBeNull();
        expect($item->content_text)->toBe('Great post!');
        expect($item->author_name)->toBe('John Doe');
        expect($item->status)->toBe(InboxItemStatus::UNREAD);
    });

    it('handles webhook for Facebook comment', function () {
        $account = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
            'platform_account_id' => 'page_123',
        ]);

        $payload = [
            'entry' => [
                [
                    'id' => 'page_123',
                    'changes' => [
                        [
                            'field' => 'feed',
                            'value' => [
                                'item' => 'comment',
                                'comment_id' => 'webhook_comment_456',
                                'post_id' => 'post_789',
                                'message' => 'Webhook comment',
                                'from' => [
                                    'name' => 'Jane Smith',
                                    'id' => 'user_999',
                                ],
                                'created_time' => '2024-01-02T10:00:00+0000',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $appSecret = config('services.facebook.client_secret', 'test_secret');
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $appSecret);

        $response = $this->postJson('/api/v1/webhooks/facebook', $payload, [
            'X-Hub-Signature-256' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $item = InboxItem::where('platform_item_id', 'webhook_comment_456')->first();
        expect($item)->not->toBeNull();
        expect($item->content_text)->toBe('Webhook comment');
        expect($item->author_name)->toBe('Jane Smith');
    });

    it('rejects webhook with invalid signature', function () {
        $payload = [
            'entry' => [
                [
                    'id' => 'page_123',
                    'changes' => [],
                ],
            ],
        ];

        $invalidSignature = 'sha256=invalid_signature_here';

        $response = $this->postJson('/api/v1/webhooks/facebook', $payload, [
            'X-Hub-Signature-256' => $invalidSignature,
        ]);

        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
    });
});

describe('Inbox Messages API Integration Tests', function () {
    it('retrieves inbox messages with filters', function () {
        // Create inbox items
        $account = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
        ]);

        $items = InboxItem::factory()->count(5)->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $account->id,
            'status' => InboxItemStatus::UNREAD,
        ]);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'platform',
                    'content_text',
                    'author_name',
                    'status',
                    'created_at',
                ],
            ],
            'meta' => [
                'current_page',
                'total',
            ],
        ]);

        expect($response->json('data'))->toHaveCount(5);
    });

    it('filters inbox messages by status', function () {
        $account = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
        ]);

        InboxItem::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $account->id,
            'status' => InboxItemStatus::UNREAD,
        ]);

        InboxItem::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $account->id,
            'status' => InboxItemStatus::READ,
        ]);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox?status=unread");

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(3);
        
        foreach ($response->json('data') as $item) {
            expect($item['status'])->toBe('unread');
        }
    });

    it('filters inbox messages by platform', function () {
        $facebookAccount = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
        ]);

        $twitterAccount = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::TWITTER,
        ]);

        InboxItem::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $facebookAccount->id,
            'platform' => SocialPlatform::FACEBOOK,
        ]);

        InboxItem::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $twitterAccount->id,
            'platform' => SocialPlatform::TWITTER,
        ]);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox?platform=facebook");

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(2);
        
        foreach ($response->json('data') as $item) {
            expect($item['platform'])->toBe('facebook');
        }
    });

    it('retrieves inbox statistics', function () {
        $account = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
        ]);

        InboxItem::factory()->count(5)->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $account->id,
            'status' => InboxItemStatus::UNREAD,
        ]);

        InboxItem::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $account->id,
            'status' => InboxItemStatus::READ,
        ]);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox/stats");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total',
            'unread',
            'read',
            'resolved',
            'by_platform',
        ]);

        expect($response->json('total'))->toBe(8);
        expect($response->json('unread'))->toBe(5);
        expect($response->json('read'))->toBe(3);
    });

    it('marks inbox item as read', function () {
        $account = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
        ]);

        $item = InboxItem::factory()->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $account->id,
            'status' => InboxItemStatus::UNREAD,
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/read");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $item->refresh();
        expect($item->status)->toBe(InboxItemStatus::READ);
        expect($item->read_at)->not->toBeNull();
    });

    it('marks inbox item as unread', function () {
        $account = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
        ]);

        $item = InboxItem::factory()->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $account->id,
            'status' => InboxItemStatus::READ,
            'read_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/unread");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $item->refresh();
        expect($item->status)->toBe(InboxItemStatus::UNREAD);
        expect($item->read_at)->toBeNull();
    });

    it('resolves inbox item', function () {
        $account = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
        ]);

        $item = InboxItem::factory()->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $account->id,
            'status' => InboxItemStatus::UNREAD,
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/resolve");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $item->refresh();
        expect($item->status)->toBe(InboxItemStatus::RESOLVED);
        expect($item->resolved_at)->not->toBeNull();
    });

    it('assigns inbox item to user', function () {
        $account = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
        ]);

        $assignee = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $item = InboxItem::factory()->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $account->id,
            'status' => InboxItemStatus::UNREAD,
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/assign", [
            'user_id' => $assignee->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $item->refresh();
        expect($item->assigned_to)->toBe($assignee->id);
        expect($item->assigned_at)->not->toBeNull();
    });

    it('performs bulk read operation', function () {
        $account = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
        ]);

        $items = InboxItem::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $account->id,
            'status' => InboxItemStatus::UNREAD,
        ]);

        $itemIds = $items->pluck('id')->toArray();

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/bulk-read", [
            'item_ids' => $itemIds,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'updated' => 3,
        ]);

        foreach ($items as $item) {
            $item->refresh();
            expect($item->status)->toBe(InboxItemStatus::READ);
        }
    });

    it('performs bulk resolve operation', function () {
        $account = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
        ]);

        $items = InboxItem::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'social_account_id' => $account->id,
            'status' => InboxItemStatus::UNREAD,
        ]);

        $itemIds = $items->pluck('id')->toArray();

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/bulk-resolve", [
            'item_ids' => $itemIds,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'updated' => 3,
        ]);

        foreach ($items as $item) {
            $item->refresh();
            expect($item->status)->toBe(InboxItemStatus::RESOLVED);
        }
    });

    it('validates workspace membership for inbox access', function () {
        $otherTenant = Tenant::factory()->create();
        $otherWorkspace = Workspace::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->getJson("/api/v1/workspaces/{$otherWorkspace->id}/inbox");

        $response->assertStatus(403);
    });

    it('handles Twitter webhook for mentions', function () {
        $account = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::TWITTER,
            'platform_account_id' => 'twitter_user_123',
        ]);

        $payload = [
            'for_user_id' => 'twitter_user_123',
            'tweet_create_events' => [
                [
                    'id_str' => 'tweet_456',
                    'text' => '@ourhandle This is a mention',
                    'user' => [
                        'id_str' => 'user_789',
                        'screen_name' => 'testuser',
                        'name' => 'Test User',
                    ],
                    'created_at' => 'Mon Jan 01 12:00:00 +0000 2024',
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/webhooks/twitter', $payload);

        $response->assertStatus(200);

        $item = InboxItem::where('platform_item_id', 'tweet_456')->first();
        expect($item)->not->toBeNull();
        expect($item->content_text)->toBe('@ourhandle This is a mention');
        expect($item->author_name)->toBe('Test User');
    });

    it('handles Instagram webhook for comments', function () {
        $account = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::INSTAGRAM,
            'platform_account_id' => 'ig_account_123',
        ]);

        $payload = [
            'entry' => [
                [
                    'id' => 'ig_account_123',
                    'changes' => [
                        [
                            'field' => 'comments',
                            'value' => [
                                'id' => 'comment_789',
                                'text' => 'Great photo!',
                                'from' => [
                                    'id' => 'user_456',
                                    'username' => 'testuser',
                                ],
                                'media' => [
                                    'id' => 'media_123',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $appSecret = config('services.facebook.client_secret', 'test_secret');
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $appSecret);

        $response = $this->postJson('/api/v1/webhooks/instagram', $payload, [
            'X-Hub-Signature-256' => $signature,
        ]);

        $response->assertStatus(200);

        $item = InboxItem::where('platform_item_id', 'comment_789')->first();
        expect($item)->not->toBeNull();
        expect($item->content_text)->toBe('Great photo!');
    });
});
