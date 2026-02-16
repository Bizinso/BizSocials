<?php

declare(strict_types=1);

use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Inbox\InboxItemType;
use App\Enums\Social\SocialPlatform;
use App\Models\Inbox\InboxItem;
use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use App\Services\Inbox\WebhookHandlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new WebhookHandlerService();
    $this->workspace = Workspace::factory()->create();
});

describe('WebhookHandlerService', function () {
    describe('Facebook webhooks', function () {
        it('verifies Facebook webhook signature correctly', function () {
            $payload = ['test' => 'data'];
            $appSecret = 'test_secret';
            $payloadJson = json_encode($payload);
            $validSignature = 'sha256=' . hash_hmac('sha256', $payloadJson, $appSecret);

            $result = $this->service->handleFacebookWebhook($payload, $validSignature, $appSecret);

            expect($result['success'])->toBeTrue();
        });

        it('rejects invalid Facebook webhook signature', function () {
            $payload = ['test' => 'data'];
            $appSecret = 'test_secret';
            $invalidSignature = 'sha256=invalid_hash';

            expect(fn() => $this->service->handleFacebookWebhook($payload, $invalidSignature, $appSecret))
                ->toThrow(ValidationException::class);
        });

        it('processes Facebook comment webhook', function () {
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
                                    'comment_id' => 'comment_123',
                                    'post_id' => 'post_456',
                                    'message' => 'Great post!',
                                    'from' => [
                                        'name' => 'John Doe',
                                        'id' => 'user_789',
                                    ],
                                    'created_time' => '2024-01-01T12:00:00+0000',
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $appSecret = 'test_secret';
            $payloadJson = json_encode($payload);
            $signature = 'sha256=' . hash_hmac('sha256', $payloadJson, $appSecret);

            $result = $this->service->handleFacebookWebhook($payload, $signature, $appSecret);

            expect($result['success'])->toBeTrue();
            expect($result['processed'])->toBe(1);

            $item = InboxItem::first();
            expect($item)->not->toBeNull();
            expect($item->platform_item_id)->toBe('comment_123');
            expect($item->content_text)->toBe('Great post!');
            expect($item->author_name)->toBe('John Doe');
            expect($item->item_type)->toBe(InboxItemType::COMMENT);
            expect($item->status)->toBe(InboxItemStatus::UNREAD);
        });

        it('does not create duplicate Facebook comments', function () {
            $account = SocialAccount::factory()->create([
                'workspace_id' => $this->workspace->id,
                'platform' => SocialPlatform::FACEBOOK,
                'platform_account_id' => 'page_123',
            ]);

            // Create existing comment
            InboxItem::factory()->create([
                'workspace_id' => $this->workspace->id,
                'social_account_id' => $account->id,
                'platform_item_id' => 'comment_123',
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
                                    'comment_id' => 'comment_123', // Same ID
                                    'post_id' => 'post_456',
                                    'message' => 'Test',
                                    'from' => ['name' => 'User', 'id' => 'user_1'],
                                    'created_time' => '2024-01-01T12:00:00+0000',
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $appSecret = 'test_secret';
            $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $appSecret);

            $result = $this->service->handleFacebookWebhook($payload, $signature, $appSecret);

            expect($result['processed'])->toBe(0);
            expect(InboxItem::count())->toBe(1);
        });
    });

    describe('Instagram webhooks', function () {
        it('verifies Instagram webhook signature correctly', function () {
            $payload = ['test' => 'data'];
            $appSecret = 'test_secret';
            $payloadJson = json_encode($payload);
            $validSignature = 'sha256=' . hash_hmac('sha256', $payloadJson, $appSecret);

            $result = $this->service->handleInstagramWebhook($payload, $validSignature, $appSecret);

            expect($result['success'])->toBeTrue();
        });

        it('processes Instagram comment webhook', function () {
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
                                    'id' => 'comment_456',
                                    'media_id' => 'media_789',
                                    'text' => 'Love this!',
                                    'from' => [
                                        'username' => 'jane_doe',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $appSecret = 'test_secret';
            $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $appSecret);

            $result = $this->service->handleInstagramWebhook($payload, $signature, $appSecret);

            expect($result['success'])->toBeTrue();
            expect($result['processed'])->toBe(1);

            $item = InboxItem::first();
            expect($item->platform_item_id)->toBe('comment_456');
            expect($item->content_text)->toBe('Love this!');
            expect($item->author_name)->toBe('jane_doe');
        });

        it('processes Instagram mention webhook', function () {
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
                                'field' => 'mentions',
                                'value' => [
                                    'media_id' => 'story_123',
                                    'comment_id' => 'mention_456',
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $appSecret = 'test_secret';
            $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $appSecret);

            $result = $this->service->handleInstagramWebhook($payload, $signature, $appSecret);

            expect($result['success'])->toBeTrue();
            expect($result['processed'])->toBe(1);

            $item = InboxItem::first();
            expect($item->item_type)->toBe(InboxItemType::MENTION);
            expect($item->platform_post_id)->toBe('story_123');
        });
    });

    describe('Twitter webhooks', function () {
        it('verifies Twitter webhook signature correctly', function () {
            $payload = ['test' => 'data'];
            $consumerSecret = 'test_secret';
            $payloadJson = json_encode($payload);
            $validSignature = 'sha256=' . base64_encode(hash_hmac('sha256', $payloadJson, $consumerSecret, true));

            $result = $this->service->handleTwitterWebhook($payload, $validSignature, $consumerSecret);

            expect($result['success'])->toBeTrue();
        });

        it('processes Twitter mention webhook', function () {
            $account = SocialAccount::factory()->create([
                'workspace_id' => $this->workspace->id,
                'platform' => SocialPlatform::TWITTER,
                'platform_username' => 'bizsocials',
            ]);

            $payload = [
                'tweet_create_events' => [
                    [
                        'id_str' => 'tweet_123',
                        'text' => '@bizsocials Great tool!',
                        'user' => [
                            'id_str' => 'user_456',
                            'name' => 'John Twitter',
                            'screen_name' => 'john_twitter',
                            'profile_image_url_https' => 'https://example.com/avatar.jpg',
                        ],
                        'entities' => [
                            'user_mentions' => [
                                ['screen_name' => 'bizsocials'],
                            ],
                        ],
                        'created_at' => 'Mon Jan 01 12:00:00 +0000 2024',
                    ],
                ],
            ];

            $consumerSecret = 'test_secret';
            $signature = 'sha256=' . base64_encode(hash_hmac('sha256', json_encode($payload), $consumerSecret, true));

            $result = $this->service->handleTwitterWebhook($payload, $signature, $consumerSecret);

            expect($result['success'])->toBeTrue();
            expect($result['processed'])->toBe(1);

            $item = InboxItem::first();
            expect($item->platform_item_id)->toBe('tweet_123');
            expect($item->item_type)->toBe(InboxItemType::MENTION);
            expect($item->author_name)->toBe('John Twitter');
            expect($item->author_username)->toBe('john_twitter');
        });

        it('processes Twitter direct message webhook', function () {
            $account = SocialAccount::factory()->create([
                'workspace_id' => $this->workspace->id,
                'platform' => SocialPlatform::TWITTER,
                'platform_account_id' => 'recipient_123',
            ]);

            $payload = [
                'direct_message_events' => [
                    [
                        'id' => 'dm_456',
                        'created_timestamp' => '1704110400000',
                        'message_create' => [
                            'sender_id' => 'sender_789',
                            'target' => [
                                'recipient_id' => 'recipient_123',
                            ],
                            'message_data' => [
                                'text' => 'Hello, I have a question',
                            ],
                        ],
                    ],
                ],
            ];

            $consumerSecret = 'test_secret';
            $signature = 'sha256=' . base64_encode(hash_hmac('sha256', json_encode($payload), $consumerSecret, true));

            $result = $this->service->handleTwitterWebhook($payload, $signature, $consumerSecret);

            expect($result['success'])->toBeTrue();
            expect($result['processed'])->toBe(1);

            $item = InboxItem::first();
            expect($item->platform_item_id)->toBe('dm_456');
            expect($item->item_type)->toBe(InboxItemType::DIRECT_MESSAGE);
            expect($item->content_text)->toBe('Hello, I have a question');
        });
    });

    it('handles multiple webhook entries', function () {
        $account1 = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
            'platform_account_id' => 'page_1',
        ]);

        $account2 = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
            'platform_account_id' => 'page_2',
        ]);

        $payload = [
            'entry' => [
                [
                    'id' => 'page_1',
                    'changes' => [
                        [
                            'field' => 'feed',
                            'value' => [
                                'item' => 'comment',
                                'comment_id' => 'comment_1',
                                'post_id' => 'post_1',
                                'message' => 'Comment 1',
                                'from' => ['name' => 'User 1', 'id' => 'user_1'],
                                'created_time' => '2024-01-01T12:00:00+0000',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'page_2',
                    'changes' => [
                        [
                            'field' => 'feed',
                            'value' => [
                                'item' => 'comment',
                                'comment_id' => 'comment_2',
                                'post_id' => 'post_2',
                                'message' => 'Comment 2',
                                'from' => ['name' => 'User 2', 'id' => 'user_2'],
                                'created_time' => '2024-01-01T12:00:00+0000',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $appSecret = 'test_secret';
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $appSecret);

        $result = $this->service->handleFacebookWebhook($payload, $signature, $appSecret);

        expect($result['processed'])->toBe(2);
        expect(InboxItem::count())->toBe(2);
    });

    it('marks webhook items with metadata flag', function () {
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
                                'comment_id' => 'comment_123',
                                'post_id' => 'post_456',
                                'message' => 'Test',
                                'from' => ['name' => 'User', 'id' => 'user_1'],
                                'created_time' => '2024-01-01T12:00:00+0000',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $appSecret = 'test_secret';
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $appSecret);

        $this->service->handleFacebookWebhook($payload, $signature, $appSecret);

        $item = InboxItem::first();
        expect($item->metadata['webhook'])->toBeTrue();
    });
});
