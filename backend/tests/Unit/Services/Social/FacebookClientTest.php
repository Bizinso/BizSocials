<?php

declare(strict_types=1);

/**
 * FacebookClient Unit Tests
 *
 * Tests for the Facebook Graph API client service:
 * - Publishing posts (text, images, videos)
 * - Fetching posts and comments
 * - Getting insights and metrics
 * - Error handling and rate limiting
 *
 * @see \App\Services\Social\FacebookClient
 */

use App\Data\Social\PlatformCredentials;
use App\Services\Social\FacebookClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\RateLimiter;

describe('FacebookClient::publishPost', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['pages_manage_posts'],
        );

        RateLimiter::clear('facebook_api_rate_limit:test_app_id');
    });

    it('publishes a text post successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => '123_456'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $fbClient = new FacebookClient($client, $this->credentials);

        $result = $fbClient->publishPost(
            pageId: 'page_123',
            accessToken: 'test_token',
            message: 'Hello Facebook!'
        );

        expect($result['success'])->toBeTrue();
        expect($result['post_id'])->toBe('123_456');
        expect($requestHistory)->toHaveCount(1);

        $uri = (string) $requestHistory[0]['request']->getUri();
        expect($uri)->toContain('/page_123/feed');
    });

    it('publishes an image post successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'photo_789'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $fbClient = new FacebookClient($client, $this->credentials);

        $result = $fbClient->publishPost(
            pageId: 'page_123',
            accessToken: 'test_token',
            message: 'Check this out!',
            options: ['image_url' => 'https://example.com/image.jpg']
        );

        expect($result['success'])->toBeTrue();
        expect($result['post_id'])->toBe('photo_789');

        $uri = (string) $requestHistory[0]['request']->getUri();
        expect($uri)->toContain('/page_123/photos');
    });

    it('publishes a video post successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'video_101'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $fbClient = new FacebookClient($client, $this->credentials);

        $result = $fbClient->publishPost(
            pageId: 'page_123',
            accessToken: 'test_token',
            message: 'Watch this!',
            options: ['video_url' => 'https://example.com/video.mp4']
        );

        expect($result['success'])->toBeTrue();
        expect($result['post_id'])->toBe('video_101');

        $uri = (string) $requestHistory[0]['request']->getUri();
        expect($uri)->toContain('/page_123/videos');
    });

    it('handles API errors gracefully', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('POST', '/feed'),
                new Response(400, [], json_encode([
                    'error' => ['code' => 190, 'message' => 'Invalid access token'],
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $fbClient = new FacebookClient($client, $this->credentials);

        $result = $fbClient->publishPost(
            pageId: 'page_123',
            accessToken: 'invalid_token',
            message: 'Test'
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Invalid access token');
    });

    it('respects rate limits', function () {
        // Fill up the rate limit
        for ($i = 0; $i < 200; $i++) {
            RateLimiter::hit('facebook_api_rate_limit:test_app_id', 3600);
        }

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => '123'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $fbClient = new FacebookClient($client, $this->credentials);

        $result = $fbClient->publishPost(
            pageId: 'page_123',
            accessToken: 'test_token',
            message: 'Test'
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toContain('Rate limit exceeded');
    });
});

describe('FacebookClient::fetchPosts', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['pages_read_engagement'],
        );

        RateLimiter::clear('facebook_api_rate_limit:test_app_id');
    });

    it('fetches posts successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    ['id' => '1', 'message' => 'Post 1'],
                    ['id' => '2', 'message' => 'Post 2'],
                ],
                'paging' => ['next' => 'https://graph.facebook.com/next'],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $fbClient = new FacebookClient($client, $this->credentials);

        $result = $fbClient->fetchPosts(
            pageId: 'page_123',
            accessToken: 'test_token'
        );

        expect($result['success'])->toBeTrue();
        expect($result['posts'])->toHaveCount(2);
        expect($result['posts'][0]['id'])->toBe('1');
    });

    it('handles fetch errors', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('GET', '/posts'),
                new Response(403, [], json_encode([
                    'error' => ['message' => 'Insufficient permissions'],
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $fbClient = new FacebookClient($client, $this->credentials);

        $result = $fbClient->fetchPosts(
            pageId: 'page_123',
            accessToken: 'test_token'
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Insufficient permissions');
    });
});

describe('FacebookClient::getPostInsights', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['read_insights'],
        );

        RateLimiter::clear('facebook_api_rate_limit:test_app_id');
    });

    it('fetches post insights successfully', function () {
        $mock = new MockHandler([
            // Insights response
            new Response(200, [], json_encode([
                'data' => [
                    ['name' => 'post_impressions', 'values' => [['value' => 1000]]],
                    ['name' => 'post_engaged_users', 'values' => [['value' => 50]]],
                ],
            ])),
            // Engagement response
            new Response(200, [], json_encode([
                'likes' => ['summary' => ['total_count' => 25]],
                'comments' => ['summary' => ['total_count' => 10]],
                'shares' => ['count' => 5],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $fbClient = new FacebookClient($client, $this->credentials);

        $result = $fbClient->getPostInsights(
            postId: 'post_123',
            accessToken: 'test_token'
        );

        expect($result['success'])->toBeTrue();
        expect($result['insights']['post_impressions'])->toBe(1000);
        expect($result['insights']['likes'])->toBe(25);
        expect($result['insights']['comments'])->toBe(10);
        expect($result['insights']['shares'])->toBe(5);
    });
});

describe('FacebookClient::getPageInsights', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['read_insights'],
        );

        RateLimiter::clear('facebook_api_rate_limit:test_app_id');
    });

    it('fetches page insights successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    [
                        'name' => 'page_impressions',
                        'title' => 'Page Impressions',
                        'values' => [['value' => 5000]],
                    ],
                    [
                        'name' => 'page_fans',
                        'title' => 'Page Fans',
                        'values' => [['value' => 1200]],
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $fbClient = new FacebookClient($client, $this->credentials);

        $result = $fbClient->getPageInsights(
            pageId: 'page_123',
            accessToken: 'test_token'
        );

        expect($result['success'])->toBeTrue();
        expect($result['insights'])->toHaveKey('page_impressions');
        expect($result['insights']['page_impressions']['values'])->toHaveCount(1);
    });
});

describe('FacebookClient::fetchComments', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['pages_read_engagement'],
        );

        RateLimiter::clear('facebook_api_rate_limit:test_app_id');
    });

    it('fetches comments successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    [
                        'id' => 'comment_1',
                        'message' => 'Great post!',
                        'from' => ['name' => 'John Doe', 'id' => 'user_1'],
                    ],
                    [
                        'id' => 'comment_2',
                        'message' => 'Thanks for sharing',
                        'from' => ['name' => 'Jane Smith', 'id' => 'user_2'],
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $fbClient = new FacebookClient($client, $this->credentials);

        $result = $fbClient->fetchComments(
            postId: 'post_123',
            accessToken: 'test_token'
        );

        expect($result['success'])->toBeTrue();
        expect($result['comments'])->toHaveCount(2);
        expect($result['comments'][0]['message'])->toBe('Great post!');
    });
});

describe('FacebookClient::replyToComment', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['pages_manage_engagement'],
        );

        RateLimiter::clear('facebook_api_rate_limit:test_app_id');
    });

    it('replies to comment successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'reply_123'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $fbClient = new FacebookClient($client, $this->credentials);

        $result = $fbClient->replyToComment(
            commentId: 'comment_123',
            accessToken: 'test_token',
            message: 'Thank you for your comment!'
        );

        expect($result['success'])->toBeTrue();
        expect($result['comment_id'])->toBe('reply_123');
    });
});

describe('FacebookClient::getPages', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['pages_show_list'],
        );

        RateLimiter::clear('facebook_api_rate_limit:test_app_id');
    });

    it('fetches user pages successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    [
                        'id' => 'page_1',
                        'name' => 'My Business Page',
                        'access_token' => 'page_token_1',
                        'category' => 'Business',
                    ],
                    [
                        'id' => 'page_2',
                        'name' => 'My Brand Page',
                        'access_token' => 'page_token_2',
                        'category' => 'Brand',
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $fbClient = new FacebookClient($client, $this->credentials);

        $result = $fbClient->getPages(userToken: 'user_token');

        expect($result['success'])->toBeTrue();
        expect($result['pages'])->toHaveCount(2);
        expect($result['pages'][0]['name'])->toBe('My Business Page');
    });
});

describe('FacebookClient::deletePost', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['pages_manage_posts'],
        );

        RateLimiter::clear('facebook_api_rate_limit:test_app_id');
    });

    it('deletes post successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $fbClient = new FacebookClient($client, $this->credentials);

        $result = $fbClient->deletePost(
            postId: 'post_123',
            accessToken: 'test_token'
        );

        expect($result['success'])->toBeTrue();
    });

    it('handles delete errors', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('DELETE', '/post_123'),
                new Response(404, [], json_encode([
                    'error' => ['message' => 'Post not found'],
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $fbClient = new FacebookClient($client, $this->credentials);

        $result = $fbClient->deletePost(
            postId: 'post_123',
            accessToken: 'test_token'
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Post not found');
    });
});
