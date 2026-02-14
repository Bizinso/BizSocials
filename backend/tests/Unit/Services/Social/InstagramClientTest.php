<?php

declare(strict_types=1);

/**
 * InstagramClient Unit Tests
 *
 * Tests for the Instagram Graph API client service:
 * - Publishing posts (images, videos, carousels)
 * - Publishing stories
 * - Fetching media and comments
 * - Getting insights and metrics
 * - Error handling and rate limiting
 *
 * @see \App\Services\Social\InstagramClient
 */

use App\Data\Social\PlatformCredentials;
use App\Services\Social\InstagramClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\RateLimiter;

describe('InstagramClient::publishImagePost', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['instagram_content_publish'],
        );

        RateLimiter::clear('instagram_api_rate_limit:test_app_id');
    });

    it('publishes an image post successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            // Create container
            new Response(200, [], json_encode(['id' => 'container_123'])),
            // Publish container
            new Response(200, [], json_encode(['id' => 'media_456'])),
            // Get permalink
            new Response(200, [], json_encode(['permalink' => 'https://instagram.com/p/abc123'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $igClient = new InstagramClient($client, $this->credentials);

        $result = $igClient->publishImagePost(
            igUserId: 'ig_user_123',
            accessToken: 'test_token',
            imageUrl: 'https://example.com/image.jpg',
            caption: 'Beautiful sunset!'
        );

        expect($result['success'])->toBeTrue();
        expect($result['media_id'])->toBe('media_456');
        expect($result['permalink'])->toBe('https://instagram.com/p/abc123');
        expect($requestHistory)->toHaveCount(3);
    });

    it('handles image post errors', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('POST', '/media'),
                new Response(400, [], json_encode([
                    'error' => ['message' => 'Invalid image URL'],
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $igClient = new InstagramClient($client, $this->credentials);

        $result = $igClient->publishImagePost(
            igUserId: 'ig_user_123',
            accessToken: 'test_token',
            imageUrl: 'invalid_url',
            caption: 'Test'
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Invalid image URL');
    });
});

describe('InstagramClient::publishVideoPost', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['instagram_content_publish'],
        );

        RateLimiter::clear('instagram_api_rate_limit:test_app_id');
    });

    it('publishes a video post successfully', function () {
        $mock = new MockHandler([
            // Create container
            new Response(200, [], json_encode(['id' => 'container_789'])),
            // Check status (finished)
            new Response(200, [], json_encode(['status_code' => 'FINISHED'])),
            // Publish container
            new Response(200, [], json_encode(['id' => 'media_101'])),
            // Get permalink
            new Response(200, [], json_encode(['permalink' => 'https://instagram.com/p/xyz789'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $igClient = new InstagramClient($client, $this->credentials);

        $result = $igClient->publishVideoPost(
            igUserId: 'ig_user_123',
            accessToken: 'test_token',
            videoUrl: 'https://example.com/video.mp4',
            caption: 'Check out this video!'
        );

        expect($result['success'])->toBeTrue();
        expect($result['media_id'])->toBe('media_101');
        expect($result['permalink'])->toBe('https://instagram.com/p/xyz789');
    });

    it('handles video processing timeout', function () {
        // Create responses that always return IN_PROGRESS
        $responses = [
            new Response(200, [], json_encode(['id' => 'container_789'])),
        ];

        // Add 30 IN_PROGRESS responses (will timeout)
        for ($i = 0; $i < 30; $i++) {
            $responses[] = new Response(200, [], json_encode(['status_code' => 'IN_PROGRESS']));
        }

        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $igClient = new InstagramClient($client, $this->credentials);

        $result = $igClient->publishVideoPost(
            igUserId: 'ig_user_123',
            accessToken: 'test_token',
            videoUrl: 'https://example.com/video.mp4',
            caption: 'Test'
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toContain('processing failed or timed out');
    });
});

describe('InstagramClient::publishCarouselPost', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['instagram_content_publish'],
        );

        RateLimiter::clear('instagram_api_rate_limit:test_app_id');
    });

    it('publishes a carousel post successfully', function () {
        $mock = new MockHandler([
            // Create child 1
            new Response(200, [], json_encode(['id' => 'child_1'])),
            // Create child 2
            new Response(200, [], json_encode(['id' => 'child_2'])),
            // Create carousel container
            new Response(200, [], json_encode(['id' => 'carousel_123'])),
            // Publish carousel
            new Response(200, [], json_encode(['id' => 'media_202'])),
            // Get permalink
            new Response(200, [], json_encode(['permalink' => 'https://instagram.com/p/carousel'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $igClient = new InstagramClient($client, $this->credentials);

        $result = $igClient->publishCarouselPost(
            igUserId: 'ig_user_123',
            accessToken: 'test_token',
            items: [
                ['type' => 'IMAGE', 'url' => 'https://example.com/img1.jpg'],
                ['type' => 'IMAGE', 'url' => 'https://example.com/img2.jpg'],
            ],
            caption: 'Swipe to see more!'
        );

        expect($result['success'])->toBeTrue();
        expect($result['media_id'])->toBe('media_202');
        expect($result['permalink'])->toBe('https://instagram.com/p/carousel');
    });
});

describe('InstagramClient::publishStory', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['instagram_content_publish'],
        );

        RateLimiter::clear('instagram_api_rate_limit:test_app_id');
    });

    it('publishes an image story successfully', function () {
        $mock = new MockHandler([
            // Create story container
            new Response(200, [], json_encode(['id' => 'story_123'])),
            // Publish story
            new Response(200, [], json_encode(['id' => 'story_media_456'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $igClient = new InstagramClient($client, $this->credentials);

        $result = $igClient->publishStory(
            igUserId: 'ig_user_123',
            accessToken: 'test_token',
            mediaUrl: 'https://example.com/story.jpg',
            mediaType: 'IMAGE'
        );

        expect($result['success'])->toBeTrue();
        expect($result['media_id'])->toBe('story_media_456');
    });

    it('publishes a video story successfully', function () {
        $mock = new MockHandler([
            // Create story container
            new Response(200, [], json_encode(['id' => 'story_789'])),
            // Check status (finished)
            new Response(200, [], json_encode(['status_code' => 'FINISHED'])),
            // Publish story
            new Response(200, [], json_encode(['id' => 'story_media_101'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $igClient = new InstagramClient($client, $this->credentials);

        $result = $igClient->publishStory(
            igUserId: 'ig_user_123',
            accessToken: 'test_token',
            mediaUrl: 'https://example.com/story.mp4',
            mediaType: 'VIDEO'
        );

        expect($result['success'])->toBeTrue();
        expect($result['media_id'])->toBe('story_media_101');
    });
});

describe('InstagramClient::fetchMedia', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['instagram_basic'],
        );

        RateLimiter::clear('instagram_api_rate_limit:test_app_id');
    });

    it('fetches media successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    [
                        'id' => 'media_1',
                        'caption' => 'First post',
                        'media_type' => 'IMAGE',
                        'like_count' => 100,
                    ],
                    [
                        'id' => 'media_2',
                        'caption' => 'Second post',
                        'media_type' => 'VIDEO',
                        'like_count' => 200,
                    ],
                ],
                'paging' => ['next' => 'https://graph.facebook.com/next'],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $igClient = new InstagramClient($client, $this->credentials);

        $result = $igClient->fetchMedia(
            igUserId: 'ig_user_123',
            accessToken: 'test_token'
        );

        expect($result['success'])->toBeTrue();
        expect($result['media'])->toHaveCount(2);
        expect($result['media'][0]['caption'])->toBe('First post');
    });
});

describe('InstagramClient::fetchComments', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['instagram_basic'],
        );

        RateLimiter::clear('instagram_api_rate_limit:test_app_id');
    });

    it('fetches comments successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    [
                        'id' => 'comment_1',
                        'text' => 'Amazing!',
                        'username' => 'user1',
                        'like_count' => 5,
                    ],
                    [
                        'id' => 'comment_2',
                        'text' => 'Love this!',
                        'username' => 'user2',
                        'like_count' => 3,
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $igClient = new InstagramClient($client, $this->credentials);

        $result = $igClient->fetchComments(
            mediaId: 'media_123',
            accessToken: 'test_token'
        );

        expect($result['success'])->toBeTrue();
        expect($result['comments'])->toHaveCount(2);
        expect($result['comments'][0]['text'])->toBe('Amazing!');
    });
});

describe('InstagramClient::replyToComment', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['instagram_manage_comments'],
        );

        RateLimiter::clear('instagram_api_rate_limit:test_app_id');
    });

    it('replies to comment successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'reply_123'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $igClient = new InstagramClient($client, $this->credentials);

        $result = $igClient->replyToComment(
            commentId: 'comment_123',
            accessToken: 'test_token',
            message: 'Thank you!'
        );

        expect($result['success'])->toBeTrue();
        expect($result['comment_id'])->toBe('reply_123');
    });
});

describe('InstagramClient::getMediaInsights', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['instagram_manage_insights'],
        );

        RateLimiter::clear('instagram_api_rate_limit:test_app_id');
    });

    it('fetches media insights successfully', function () {
        $mock = new MockHandler([
            // Insights response
            new Response(200, [], json_encode([
                'data' => [
                    ['name' => 'impressions', 'values' => [['value' => 1500]]],
                    ['name' => 'reach', 'values' => [['value' => 1200]]],
                    ['name' => 'saved', 'values' => [['value' => 50]]],
                ],
            ])),
            // Media response for engagement
            new Response(200, [], json_encode([
                'like_count' => 150,
                'comments_count' => 25,
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $igClient = new InstagramClient($client, $this->credentials);

        $result = $igClient->getMediaInsights(
            mediaId: 'media_123',
            accessToken: 'test_token'
        );

        expect($result['success'])->toBeTrue();
        expect($result['insights']['impressions'])->toBe(1500);
        expect($result['insights']['reach'])->toBe(1200);
        expect($result['insights']['likes'])->toBe(150);
        expect($result['insights']['comments'])->toBe(25);
    });
});

describe('InstagramClient::getAccountInsights', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['instagram_manage_insights'],
        );

        RateLimiter::clear('instagram_api_rate_limit:test_app_id');
    });

    it('fetches account insights successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    [
                        'name' => 'impressions',
                        'title' => 'Impressions',
                        'values' => [['value' => 10000]],
                    ],
                    [
                        'name' => 'follower_count',
                        'title' => 'Follower Count',
                        'values' => [['value' => 5000]],
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $igClient = new InstagramClient($client, $this->credentials);

        $result = $igClient->getAccountInsights(
            igUserId: 'ig_user_123',
            accessToken: 'test_token'
        );

        expect($result['success'])->toBeTrue();
        expect($result['insights'])->toHaveKey('impressions');
        expect($result['insights']['follower_count']['values'])->toHaveCount(1);
    });
});

describe('InstagramClient rate limiting', function () {
    beforeEach(function () {
        $this->credentials = new PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'https://example.com/callback',
            apiVersion: 'v24.0',
            scopes: ['instagram_content_publish'],
        );

        RateLimiter::clear('instagram_api_rate_limit:test_app_id');
    });

    it('respects rate limits', function () {
        // Fill up the rate limit
        for ($i = 0; $i < 200; $i++) {
            RateLimiter::hit('instagram_api_rate_limit:test_app_id', 3600);
        }

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => '123'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $igClient = new InstagramClient($client, $this->credentials);

        $result = $igClient->publishImagePost(
            igUserId: 'ig_user_123',
            accessToken: 'test_token',
            imageUrl: 'https://example.com/image.jpg',
            caption: 'Test'
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toContain('Rate limit exceeded');
    });
});
