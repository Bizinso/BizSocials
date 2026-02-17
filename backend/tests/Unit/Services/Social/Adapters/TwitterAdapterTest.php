<?php

declare(strict_types=1);

/**
 * TwitterAdapter Unit Tests
 *
 * Tests for the Twitter publishing adapter:
 * - Tweet posting with and without media
 * - OAuth token exchange and refresh
 * - Inbox item fetching
 * - Post metrics retrieval
 *
 * @see \App\Services\Social\Adapters\TwitterAdapter
 */

use App\Enums\Inbox\InboxItemType;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Social\Adapters\TwitterAdapter;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

describe('TwitterAdapter::exchangeCode', function () {
    it('exchanges authorization code for tokens successfully', function () {
        // Mock the request to store code verifier
        request()->merge(['twitter_code_verifier' => 'test_verifier_123']);

        $mock = new MockHandler([
            // Token exchange response
            new Response(200, [], json_encode([
                'access_token' => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 7200,
            ])),
            // Profile fetch response
            new Response(200, [], json_encode([
                'data' => [
                    'id' => 'user123',
                    'name' => 'Test User',
                    'username' => 'testuser',
                    'profile_image_url' => 'https://example.com/avatar.jpg',
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new TwitterAdapter($client);

        $tokenData = $adapter->exchangeCode('auth_code_123', 'https://example.com/callback');

        expect($tokenData->access_token)->toBe('new_access_token');
        expect($tokenData->refresh_token)->toBe('new_refresh_token');
        expect($tokenData->expires_in)->toBe(7200);
        expect($tokenData->platform_account_id)->toBe('user123');
        expect($tokenData->account_name)->toBe('Test User');
        expect($tokenData->account_username)->toBe('testuser');
    });
});

describe('TwitterAdapter::refreshToken', function () {
    it('refreshes access token successfully', function () {
        $mock = new MockHandler([
            // Token refresh response
            new Response(200, [], json_encode([
                'access_token' => 'refreshed_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 7200,
            ])),
            // Profile fetch response
            new Response(200, [], json_encode([
                'data' => [
                    'id' => 'user123',
                    'name' => 'Test User',
                    'username' => 'testuser',
                    'profile_image_url' => 'https://example.com/avatar.jpg',
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new TwitterAdapter($client);

        $tokenData = $adapter->refreshToken('old_refresh_token');

        expect($tokenData->access_token)->toBe('refreshed_access_token');
        expect($tokenData->refresh_token)->toBe('new_refresh_token');
        expect($tokenData->expires_in)->toBe(7200);
    });
});

describe('TwitterAdapter::publishPost', function () {
    beforeEach(function () {
        $this->tenant = Tenant::factory()->active()->create();
        $this->workspace = Workspace::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->twitterAccount = SocialAccount::factory()->twitter()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
            'account_username' => 'testuser',
        ]);
    });

    it('publishes a text tweet successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => ['id' => 'tweet_123'],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new TwitterAdapter($client);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Hello Twitter!',
        ]);

        $target = PostTarget::factory()->publishing()->create([
            'post_id' => $post->id,
            'social_account_id' => $this->twitterAccount->id,
            'platform_code' => 'twitter',
        ]);

        $result = $adapter->publishPost($target, $post, collect());

        expect($result->success)->toBeTrue();
        expect($result->externalPostId)->toBe('tweet_123');
        expect($result->externalPostUrl)->toContain('twitter.com/testuser/status/tweet_123');
        expect($requestHistory)->toHaveCount(1);

        $uri = (string) $requestHistory[0]['request']->getUri();
        expect($uri)->toContain('/tweets');

        $body = json_decode((string) $requestHistory[0]['request']->getBody(), true);
        expect($body['text'])->toBe('Hello Twitter!');
    });

    it('handles publishing errors gracefully', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('POST', '/tweets'),
                new Response(403, [], json_encode([
                    'detail' => 'Rate limit exceeded',
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new TwitterAdapter($client);

        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Test tweet',
        ]);

        $target = PostTarget::factory()->publishing()->create([
            'post_id' => $post->id,
            'social_account_id' => $this->twitterAccount->id,
            'platform_code' => 'twitter',
        ]);

        $result = $adapter->publishPost($target, $post, collect());

        expect($result->success)->toBeFalse();
        expect($result->errorMessage)->toContain('Rate limit exceeded');
    });
});

describe('TwitterAdapter::fetchInboxItems', function () {
    beforeEach(function () {
        $this->tenant = Tenant::factory()->active()->create();
        $this->workspace = Workspace::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->twitterAccount = SocialAccount::factory()->twitter()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
            'platform_account_id' => 'user123',
            'metadata' => ['user_id' => 'user123'],
        ]);
    });

    it('fetches mentions successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    [
                        'id' => 'mention_1',
                        'text' => '@testuser Great post!',
                        'created_at' => '2024-01-15T10:00:00Z',
                        'author_id' => 'author_1',
                    ],
                    [
                        'id' => 'mention_2',
                        'text' => '@testuser Thanks for sharing',
                        'created_at' => '2024-01-15T11:00:00Z',
                        'author_id' => 'author_2',
                    ],
                ],
                'includes' => [
                    'users' => [
                        [
                            'id' => 'author_1',
                            'name' => 'John Doe',
                            'username' => 'johndoe',
                            'profile_image_url' => 'https://example.com/john.jpg',
                        ],
                        [
                            'id' => 'author_2',
                            'name' => 'Jane Smith',
                            'username' => 'janesmith',
                            'profile_image_url' => 'https://example.com/jane.jpg',
                        ],
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new TwitterAdapter($client);

        $items = $adapter->fetchInboxItems($this->twitterAccount);

        expect($items)->toHaveCount(2);
        expect($items[0]['platform_item_id'])->toBe('mention_1');
        expect($items[0]['type'])->toBe(InboxItemType::MENTION);
        expect($items[0]['author_name'])->toBe('John Doe');
        expect($items[0]['author_username'])->toBe('johndoe');
        expect($items[0]['content_text'])->toBe('@testuser Great post!');
    });

    it('returns empty array on error', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('GET', '/users/user123/mentions'),
                new Response(401, [])
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new TwitterAdapter($client);

        $items = $adapter->fetchInboxItems($this->twitterAccount);

        expect($items)->toBeEmpty();
    });
});

describe('TwitterAdapter::fetchPostMetrics', function () {
    beforeEach(function () {
        $this->tenant = Tenant::factory()->active()->create();
        $this->workspace = Workspace::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->twitterAccount = SocialAccount::factory()->twitter()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);
    });

    it('fetches post metrics successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    'id' => 'tweet_123',
                    'public_metrics' => [
                        'like_count' => 100,
                        'retweet_count' => 50,
                        'reply_count' => 25,
                        'quote_count' => 10,
                        'bookmark_count' => 15,
                    ],
                    'non_public_metrics' => [
                        'impression_count' => 5000,
                        'url_link_clicks' => 200,
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new TwitterAdapter($client);

        $metrics = $adapter->fetchPostMetrics($this->twitterAccount, 'tweet_123');

        expect($metrics['impressions'])->toBe(5000);
        expect($metrics['likes'])->toBe(100);
        expect($metrics['comments'])->toBe(25);
        expect($metrics['shares'])->toBe(50);
        expect($metrics['saves'])->toBe(15);
        expect($metrics['clicks'])->toBe(200);
    });

    it('returns empty metrics on error', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('GET', '/tweets/tweet_123'),
                new Response(404, [])
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $adapter = new TwitterAdapter($client);

        $metrics = $adapter->fetchPostMetrics($this->twitterAccount, 'tweet_123');

        expect($metrics['impressions'])->toBe(0);
        expect($metrics['likes'])->toBe(0);
        expect($metrics['comments'])->toBe(0);
    });
});
