<?php

declare(strict_types=1);

/**
 * MessageFetchingService Unit Tests
 *
 * Tests for the MessageFetchingService which handles fetching messages
 * from social media platforms and storing them in the inbox.
 *
 * @see \App\Services\Inbox\MessageFetchingService
 */

use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Inbox\InboxItemType;
use App\Enums\Social\SocialPlatform;
use App\Models\Inbox\InboxItem;
use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use App\Services\Inbox\MessageFetchingService;
use App\Services\Social\FacebookClient;
use App\Services\Social\InstagramClient;
use App\Services\Social\TwitterClient;
use Carbon\Carbon;
use Tests\Stubs\Services\FakeFacebookClient;
use Tests\Stubs\Services\FakeInstagramClient;
use Tests\Stubs\Services\FakeTwitterClient;

beforeEach(function (): void {
    // Create fake clients
    $this->facebookClient = new FakeFacebookClient(
        new \GuzzleHttp\Client(),
        new \App\Data\Social\PlatformCredentials(
            appId: 'test',
            appSecret: 'test',
            redirectUri: 'test',
            apiVersion: 'v24.0',
            scopes: []
        )
    );
    
    $this->instagramClient = new FakeInstagramClient(
        new \GuzzleHttp\Client(),
        new \App\Data\Social\PlatformCredentials(
            appId: 'test',
            appSecret: 'test',
            redirectUri: 'test',
            apiVersion: 'v24.0',
            scopes: []
        )
    );
    
    $this->twitterClient = new FakeTwitterClient(
        new \GuzzleHttp\Client(),
        new \App\Data\Social\PlatformCredentials(
            appId: 'test',
            appSecret: 'test',
            redirectUri: 'test',
            apiVersion: 'v2',
            scopes: []
        )
    );

    // Bind fake clients to the container
    $this->app->instance(FacebookClient::class, $this->facebookClient);
    $this->app->instance(InstagramClient::class, $this->instagramClient);
    $this->app->instance(TwitterClient::class, $this->twitterClient);

    $this->service = app(MessageFetchingService::class);
});

describe('fetchAllMessages', function (): void {
    test('fetches messages from all active social accounts', function (): void {
        $workspace = Workspace::factory()->create();
        
        $fbAccount = SocialAccount::factory()
            ->forWorkspace($workspace)
            ->create([
                'platform' => SocialPlatform::FACEBOOK,
                'is_active' => true,
                'access_token' => 'fb_token',
            ]);
        
        $igAccount = SocialAccount::factory()
            ->forWorkspace($workspace)
            ->create([
                'platform' => SocialPlatform::INSTAGRAM,
                'is_active' => true,
                'access_token' => 'ig_token',
            ]);

        // Set up Facebook responses
        $this->facebookClient->setPostsResponse([
            'success' => true,
            'posts' => [
                ['id' => 'post_1'],
            ],
        ]);

        $this->facebookClient->setCommentsResponse([
            'success' => true,
            'comments' => [
                [
                    'id' => 'comment_1',
                    'from' => ['name' => 'John Doe', 'id' => '123'],
                    'message' => 'Great post!',
                    'created_time' => '2024-01-15T10:00:00+0000',
                ],
            ],
        ]);

        // Set up Instagram responses
        $this->instagramClient->setMediaResponse([
            'success' => true,
            'media' => [
                ['id' => 'media_1'],
            ],
        ]);

        $this->instagramClient->setCommentsResponse([
            'success' => true,
            'comments' => [
                [
                    'id' => 'ig_comment_1',
                    'from' => ['username' => 'jane_doe'],
                    'text' => 'Love this!',
                    'timestamp' => '2024-01-15T11:00:00+0000',
                ],
            ],
        ]);

        $result = $this->service->fetchAllMessages($workspace);

        expect($result['success'])->toBeTrue()
            ->and($result['fetched'])->toBe(2)
            ->and($result['errors'])->toBeEmpty();

        // Verify messages were stored
        expect(InboxItem::count())->toBe(2);
    });

    test('skips inactive social accounts', function (): void {
        $workspace = Workspace::factory()->create();
        
        SocialAccount::factory()
            ->forWorkspace($workspace)
            ->create([
                'platform' => SocialPlatform::FACEBOOK,
                'is_active' => false,
                'access_token' => 'fb_token',
            ]);

        $result = $this->service->fetchAllMessages($workspace);

        expect($result['success'])->toBeTrue()
            ->and($result['fetched'])->toBe(0);
    });

    test('handles errors from individual accounts gracefully', function (): void {
        $workspace = Workspace::factory()->create();
        
        $fbAccount = SocialAccount::factory()
            ->forWorkspace($workspace)
            ->create([
                'platform' => SocialPlatform::FACEBOOK,
                'is_active' => true,
                'access_token' => 'fb_token',
            ]);

        $this->facebookClient->setPostsResponse([
            'success' => false,
            'error' => 'API rate limit exceeded',
        ]);

        $result = $this->service->fetchAllMessages($workspace);

        expect($result['success'])->toBeFalse()
            ->and($result['fetched'])->toBe(0)
            ->and($result['errors'])->toHaveKey($fbAccount->id);
    });
});

describe('fetchMessagesForAccount - Facebook', function (): void {
    test('fetches and stores Facebook comments', function (): void {
        $account = SocialAccount::factory()->create([
            'platform' => SocialPlatform::FACEBOOK,
            'access_token' => 'fb_token',
            'platform_account_id' => 'fb_page_123',
        ]);

        $this->facebookClient->setPostsResponse([
            'success' => true,
            'posts' => [
                ['id' => 'post_1'],
                ['id' => 'post_2'],
            ],
        ]);

        $this->facebookClient->setCommentsResponse([
            'success' => true,
            'comments' => [
                [
                    'id' => 'comment_1',
                    'from' => [
                        'name' => 'John Doe',
                        'id' => '123',
                    ],
                    'message' => 'Great post!',
                    'created_time' => '2024-01-15T10:00:00+0000',
                ],
            ],
        ]);

        $result = $this->service->fetchMessagesForAccount($account);

        expect($result['success'])->toBeTrue()
            ->and($result['fetched'])->toBe(2);

        $inboxItem = InboxItem::first();
        expect($inboxItem)->not->toBeNull()
            ->and($inboxItem->workspace_id)->toBe($account->workspace_id)
            ->and($inboxItem->social_account_id)->toBe($account->id)
            ->and($inboxItem->item_type)->toBe(InboxItemType::COMMENT)
            ->and($inboxItem->status)->toBe(InboxItemStatus::UNREAD)
            ->and($inboxItem->platform_item_id)->toBe('comment_1')
            ->and($inboxItem->author_name)->toBe('John Doe')
            ->and($inboxItem->content_text)->toBe('Great post!');
    });

    test('returns error when access token is missing', function (): void {
        $account = SocialAccount::factory()->create([
            'platform' => SocialPlatform::FACEBOOK,
            'access_token' => null,
        ]);

        $result = $this->service->fetchMessagesForAccount($account);

        expect($result['success'])->toBeFalse()
            ->and($result['fetched'])->toBe(0)
            ->and($result['error'])->toBe('No access token available');
    });

    test('handles Facebook API errors', function (): void {
        $account = SocialAccount::factory()->create([
            'platform' => SocialPlatform::FACEBOOK,
            'access_token' => 'fb_token',
        ]);

        $this->facebookClient->setPostsResponse([
            'success' => false,
            'error' => 'Invalid OAuth token',
        ]);

        $result = $this->service->fetchMessagesForAccount($account);

        expect($result['success'])->toBeFalse()
            ->and($result['fetched'])->toBe(0)
            ->and($result['error'])->toBe('Invalid OAuth token');
    });

    test('does not store duplicate comments', function (): void {
        $account = SocialAccount::factory()->create([
            'platform' => SocialPlatform::FACEBOOK,
            'access_token' => 'fb_token',
        ]);

        // Create existing inbox item
        InboxItem::factory()->create([
            'platform_item_id' => 'comment_1',
            'social_account_id' => $account->id,
        ]);

        $this->facebookClient->setPostsResponse([
            'success' => true,
            'posts' => [['id' => 'post_1']],
        ]);

        $this->facebookClient->setCommentsResponse([
            'success' => true,
            'comments' => [
                [
                    'id' => 'comment_1',
                    'from' => ['name' => 'John Doe', 'id' => '123'],
                    'message' => 'Great post!',
                    'created_time' => '2024-01-15T10:00:00+0000',
                ],
            ],
        ]);

        $result = $this->service->fetchMessagesForAccount($account);

        expect($result['success'])->toBeTrue()
            ->and($result['fetched'])->toBe(0);

        // Should still only have 1 inbox item
        expect(InboxItem::count())->toBe(1);
    });
});

describe('fetchMessagesForAccount - Instagram', function (): void {
    test('fetches and stores Instagram comments', function (): void {
        $account = SocialAccount::factory()->create([
            'platform' => SocialPlatform::INSTAGRAM,
            'access_token' => 'ig_token',
            'platform_account_id' => 'ig_account_123',
        ]);

        $this->instagramClient->setMediaResponse([
            'success' => true,
            'media' => [
                ['id' => 'media_1'],
            ],
        ]);

        $this->instagramClient->setCommentsResponse([
            'success' => true,
            'comments' => [
                [
                    'id' => 'ig_comment_1',
                    'from' => ['username' => 'jane_doe'],
                    'text' => 'Love this!',
                    'timestamp' => '2024-01-15T11:00:00+0000',
                ],
            ],
        ]);

        $result = $this->service->fetchMessagesForAccount($account);

        expect($result['success'])->toBeTrue()
            ->and($result['fetched'])->toBe(1);

        $inboxItem = InboxItem::first();
        expect($inboxItem)->not->toBeNull()
            ->and($inboxItem->platform_item_id)->toBe('ig_comment_1')
            ->and($inboxItem->author_name)->toBe('jane_doe')
            ->and($inboxItem->author_username)->toBe('jane_doe')
            ->and($inboxItem->content_text)->toBe('Love this!');
    });

    test('returns error when Instagram access token is missing', function (): void {
        $account = SocialAccount::factory()->create([
            'platform' => SocialPlatform::INSTAGRAM,
            'access_token' => null,
        ]);

        $result = $this->service->fetchMessagesForAccount($account);

        expect($result['success'])->toBeFalse()
            ->and($result['error'])->toBe('No access token available');
    });
});

describe('fetchMessagesForAccount - Twitter', function (): void {
    test('returns not implemented error for Twitter', function (): void {
        $account = SocialAccount::factory()->create([
            'platform' => SocialPlatform::TWITTER,
            'access_token' => 'twitter_token',
        ]);

        $result = $this->service->fetchMessagesForAccount($account);

        expect($result['success'])->toBeFalse()
            ->and($result['fetched'])->toBe(0)
            ->and($result['error'])->toContain('not yet implemented');
    });
});

describe('fetchMessagesForAccount - Unsupported Platform', function (): void {
    test('returns error for unsupported platforms', function (): void {
        $account = SocialAccount::factory()->create([
            'platform' => SocialPlatform::LINKEDIN,
            'access_token' => 'linkedin_token',
        ]);

        $result = $this->service->fetchMessagesForAccount($account);

        expect($result['success'])->toBeFalse()
            ->and($result['error'])->toBe('Platform not supported for message fetching');
    });
});

describe('message parsing', function (): void {
    test('parses Facebook comment with complete data', function (): void {
        $account = SocialAccount::factory()->create([
            'platform' => SocialPlatform::FACEBOOK,
            'access_token' => 'fb_token',
        ]);

        $this->facebookClient->setPostsResponse([
            'success' => true,
            'posts' => [['id' => 'post_1']],
        ]);

        $this->facebookClient->setCommentsResponse([
            'success' => true,
            'comments' => [
                [
                    'id' => 'comment_123',
                    'from' => [
                        'name' => 'Alice Smith',
                        'id' => 'fb_user_456',
                    ],
                    'message' => 'This is amazing!',
                    'created_time' => '2024-01-20T14:30:00+0000',
                ],
            ],
        ]);

        $this->service->fetchMessagesForAccount($account);

        $item = InboxItem::first();
        expect($item->platform_item_id)->toBe('comment_123')
            ->and($item->author_name)->toBe('Alice Smith')
            ->and($item->author_profile_url)->toBe('https://facebook.com/fb_user_456')
            ->and($item->content_text)->toBe('This is amazing!')
            ->and($item->platform_created_at->toDateTimeString())
            ->toBe(Carbon::parse('2024-01-20T14:30:00+0000')->toDateTimeString())
            ->and($item->metadata['platform'])->toBe('facebook');
    });

    test('parses Instagram comment with username', function (): void {
        $account = SocialAccount::factory()->create([
            'platform' => SocialPlatform::INSTAGRAM,
            'access_token' => 'ig_token',
        ]);

        $this->instagramClient->setMediaResponse([
            'success' => true,
            'media' => [['id' => 'media_1']],
        ]);

        $this->instagramClient->setCommentsResponse([
            'success' => true,
            'comments' => [
                [
                    'id' => 'ig_comment_789',
                    'from' => ['username' => 'bob_photographer'],
                    'text' => 'Beautiful shot!',
                    'timestamp' => '2024-01-21T09:15:00+0000',
                ],
            ],
        ]);

        $this->service->fetchMessagesForAccount($account);

        $item = InboxItem::first();
        expect($item->platform_item_id)->toBe('ig_comment_789')
            ->and($item->author_name)->toBe('bob_photographer')
            ->and($item->author_username)->toBe('bob_photographer')
            ->and($item->author_profile_url)->toBe('https://instagram.com/bob_photographer')
            ->and($item->content_text)->toBe('Beautiful shot!')
            ->and($item->metadata['platform'])->toBe('instagram');
    });

    test('handles missing optional fields gracefully', function (): void {
        $account = SocialAccount::factory()->create([
            'platform' => SocialPlatform::FACEBOOK,
            'access_token' => 'fb_token',
        ]);

        $this->facebookClient->setPostsResponse([
            'success' => true,
            'posts' => [['id' => 'post_1']],
        ]);

        $this->facebookClient->setCommentsResponse([
            'success' => true,
            'comments' => [
                [
                    'id' => 'comment_minimal',
                    'from' => [],
                    // Missing message field
                    // Missing created_time field
                ],
            ],
        ]);

        $this->service->fetchMessagesForAccount($account);

        $item = InboxItem::first();
        expect($item)->not->toBeNull()
            ->and($item->author_name)->toBe('Unknown')
            ->and($item->content_text)->toBe('')
            ->and($item->platform_created_at)->not->toBeNull();
    });

    test('skips comments without id', function (): void {
        $account = SocialAccount::factory()->create([
            'platform' => SocialPlatform::FACEBOOK,
            'access_token' => 'fb_token',
        ]);

        $this->facebookClient->setPostsResponse([
            'success' => true,
            'posts' => [['id' => 'post_1']],
        ]);

        $this->facebookClient->setCommentsResponse([
            'success' => true,
            'comments' => [
                [
                    // Missing id field
                    'from' => ['name' => 'John'],
                    'message' => 'Test',
                ],
            ],
        ]);

        $result = $this->service->fetchMessagesForAccount($account);

        expect($result['fetched'])->toBe(0);
        expect(InboxItem::count())->toBe(0);
    });
});

describe('incremental sync', function (): void {
    test('fetches only new messages since last fetch', function (): void {
        $account = SocialAccount::factory()->create([
            'platform' => SocialPlatform::FACEBOOK,
            'access_token' => 'fb_token',
        ]);

        // Create existing message with timestamp
        $lastMessageTime = Carbon::parse('2024-01-15T10:00:00+0000');
        InboxItem::factory()->create([
            'social_account_id' => $account->id,
            'platform_created_at' => $lastMessageTime,
        ]);

        $this->facebookClient->setPostsResponse([
            'success' => true,
            'posts' => [['id' => 'post_1']],
        ]);

        $this->facebookClient->setCommentsResponse([
            'success' => true,
            'comments' => [
                [
                    'id' => 'new_comment',
                    'from' => ['name' => 'New User', 'id' => '999'],
                    'message' => 'New comment',
                    'created_time' => '2024-01-16T10:00:00+0000',
                ],
            ],
        ]);

        $result = $this->service->fetchIncrementalMessages($account);

        expect($result['success'])->toBeTrue()
            ->and($result['fetched'])->toBe(1);

        // Should now have 2 messages total
        expect(InboxItem::where('social_account_id', $account->id)->count())->toBe(2);
    });

    test('fetches all messages when no previous messages exist', function (): void {
        $account = SocialAccount::factory()->create([
            'platform' => SocialPlatform::FACEBOOK,
            'access_token' => 'fb_token',
        ]);

        $this->facebookClient->setPostsResponse([
            'success' => true,
            'posts' => [['id' => 'post_1']],
        ]);

        $this->facebookClient->setCommentsResponse([
            'success' => true,
            'comments' => [
                [
                    'id' => 'first_comment',
                    'from' => ['name' => 'First User', 'id' => '111'],
                    'message' => 'First comment',
                    'created_time' => '2024-01-10T10:00:00+0000',
                ],
            ],
        ]);

        $result = $this->service->fetchIncrementalMessages($account);

        expect($result['success'])->toBeTrue()
            ->and($result['fetched'])->toBe(1);
    });

    test('incremental sync respects platform-specific timestamp formats', function (): void {
        $account = SocialAccount::factory()->create([
            'platform' => SocialPlatform::INSTAGRAM,
            'access_token' => 'ig_token',
        ]);

        $lastMessageTime = Carbon::parse('2024-01-15T12:00:00+0000');
        InboxItem::factory()->create([
            'social_account_id' => $account->id,
            'platform_created_at' => $lastMessageTime,
        ]);

        $this->instagramClient->setMediaResponse([
            'success' => true,
            'media' => [['id' => 'media_1']],
        ]);

        $this->instagramClient->setCommentsResponse([
            'success' => true,
            'comments' => [],
        ]);

        $result = $this->service->fetchIncrementalMessages($account);

        expect($result['success'])->toBeTrue();
    });
});

describe('storage verification', function (): void {
    test('stores all required fields in database', function (): void {
        $workspace = Workspace::factory()->create();
        $account = SocialAccount::factory()->create([
            'workspace_id' => $workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
            'access_token' => 'fb_token',
        ]);

        $this->facebookClient->setPostsResponse([
            'success' => true,
            'posts' => [['id' => 'post_1']],
        ]);

        $this->facebookClient->setCommentsResponse([
            'success' => true,
            'comments' => [
                [
                    'id' => 'comment_full',
                    'from' => ['name' => 'Test User', 'id' => '123'],
                    'message' => 'Test message',
                    'created_time' => '2024-01-15T10:00:00+0000',
                ],
            ],
        ]);

        $this->service->fetchMessagesForAccount($account);

        $item = InboxItem::first();
        
        // Verify all required fields are stored
        expect($item->workspace_id)->toBe($workspace->id)
            ->and($item->social_account_id)->toBe($account->id)
            ->and($item->item_type)->toBe(InboxItemType::COMMENT)
            ->and($item->status)->toBe(InboxItemStatus::UNREAD)
            ->and($item->platform_item_id)->toBe('comment_full')
            ->and($item->platform_post_id)->toBe('post_1')
            ->and($item->author_name)->toBe('Test User')
            ->and($item->content_text)->toBe('Test message')
            ->and($item->platform_created_at)->toBeInstanceOf(Carbon::class)
            ->and($item->metadata)->toBeArray()
            ->and($item->metadata['raw_data'])->toBeArray()
            ->and($item->metadata['platform'])->toBe('facebook');
    });

    test('persists metadata with raw API response', function (): void {
        $account = SocialAccount::factory()->create([
            'platform' => SocialPlatform::FACEBOOK,
            'access_token' => 'fb_token',
        ]);

        $rawComment = [
            'id' => 'comment_with_metadata',
            'from' => ['name' => 'User', 'id' => '456'],
            'message' => 'Message',
            'created_time' => '2024-01-15T10:00:00+0000',
            'extra_field' => 'extra_value',
        ];

        $this->facebookClient->setPostsResponse([
            'success' => true,
            'posts' => [['id' => 'post_1']],
        ]);

        $this->facebookClient->setCommentsResponse([
            'success' => true,
            'comments' => [$rawComment],
        ]);

        $this->service->fetchMessagesForAccount($account);

        $item = InboxItem::first();
        expect($item->metadata['raw_data'])->toBe($rawComment)
            ->and($item->metadata['raw_data']['extra_field'])->toBe('extra_value');
    });
});
