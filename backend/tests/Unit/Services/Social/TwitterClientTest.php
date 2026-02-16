<?php

declare(strict_types=1);

/**
 * TwitterClient Unit Tests
 *
 * Tests for the Twitter API v2 client service:
 * - Posting tweets with and without media
 * - Fetching timeline and analytics
 * - Getting follower count and metrics
 * - Media upload functionality
 * - Error handling
 *
 * @see \App\Services\Social\TwitterClient
 */

use App\Data\Social\PlatformCredentials;
use App\Services\Social\TwitterClient;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

// Helper function to create test credentials
function createTwitterTestCredentials(): PlatformCredentials
{
    return new PlatformCredentials(
        appId: 'test_app_id',
        appSecret: 'test_app_secret',
        redirectUri: 'https://example.com/callback',
        apiVersion: 'v2',
        scopes: ['tweet.read', 'tweet.write', 'users.read'],
    );
}

describe('TwitterClient::postTweet', function () {
    it('posts a text tweet successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => ['id' => '1234567890'],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $twitterClient = new TwitterClient($client, createTwitterTestCredentials());

        $result = $twitterClient->postTweet(
            accessToken: 'test_token',
            text: 'Hello Twitter!'
        );

        expect($result['success'])->toBeTrue();
        expect($result['tweet_id'])->toBe('1234567890');
        expect($requestHistory)->toHaveCount(1);

        $uri = (string) $requestHistory[0]['request']->getUri();
        expect($uri)->toContain('/tweets');

        $body = json_decode((string) $requestHistory[0]['request']->getBody(), true);
        expect($body['text'])->toBe('Hello Twitter!');
    });

    it('posts a tweet with media successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => ['id' => '9876543210'],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $twitterClient = new TwitterClient($client, createTwitterTestCredentials());

        $result = $twitterClient->postTweet(
            accessToken: 'test_token',
            text: 'Check out this image!',
            mediaIds: ['media_123', 'media_456']
        );

        expect($result['success'])->toBeTrue();
        expect($result['tweet_id'])->toBe('9876543210');

        $body = json_decode((string) $requestHistory[0]['request']->getBody(), true);
        expect($body['media']['media_ids'])->toBe(['media_123', 'media_456']);
    });

    it('handles API errors gracefully', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('POST', '/tweets'),
                new Response(401, [], json_encode([
                    'detail' => 'Invalid access token',
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $twitterClient = new TwitterClient($client, createTwitterTestCredentials());

        $result = $twitterClient->postTweet(
            accessToken: 'invalid_token',
            text: 'Test'
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBeString();
    });
});

describe('TwitterClient::getAnalytics', function () {
    it('fetches analytics successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    [
                        'id' => 'tweet1',
                        'text' => 'First tweet',
                        'created_at' => '2024-01-01T10:00:00Z',
                        'public_metrics' => [
                            'like_count' => 10,
                            'retweet_count' => 5,
                            'reply_count' => 3,
                            'quote_count' => 2,
                        ],
                    ],
                    [
                        'id' => 'tweet2',
                        'text' => 'Second tweet',
                        'created_at' => '2024-01-02T10:00:00Z',
                        'public_metrics' => [
                            'like_count' => 20,
                            'retweet_count' => 10,
                            'reply_count' => 5,
                            'quote_count' => 3,
                        ],
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $twitterClient = new TwitterClient($client, createTwitterTestCredentials());

        $result = $twitterClient->getAnalytics(
            accountId: 'user123',
            accessToken: 'test_token',
            startDate: Carbon::parse('2024-01-01'),
            endDate: Carbon::parse('2024-01-31')
        );

        expect($result['total_tweets'])->toBe(2);
        expect($result['total_likes'])->toBe(30);
        expect($result['total_retweets'])->toBe(15);
        expect($result['total_replies'])->toBe(8);
        expect($result['total_quotes'])->toBe(5);
        expect($result['total_engagements'])->toBe(58);
    });

    it('handles analytics errors', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('GET', '/users/user123/tweets'),
                new Response(403, [], json_encode([
                    'detail' => 'Insufficient permissions',
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $twitterClient = new TwitterClient($client, createTwitterTestCredentials());

        $result = $twitterClient->getAnalytics(
            accountId: 'user123',
            accessToken: 'test_token',
            startDate: Carbon::parse('2024-01-01'),
            endDate: Carbon::parse('2024-01-31')
        );

        expect($result)->toHaveKey('error');
        expect($result['total_tweets'])->toBe(0);
    });
});

describe('TwitterClient::getFollowerCount', function () {
    it('fetches follower count successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    'id' => 'user123',
                    'public_metrics' => [
                        'followers_count' => 1500,
                        'following_count' => 300,
                        'tweet_count' => 500,
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $twitterClient = new TwitterClient($client, createTwitterTestCredentials());

        $result = $twitterClient->getFollowerCount(
            accountId: 'user123',
            accessToken: 'test_token'
        );

        expect($result)->toBe(1500);
    });

    it('returns 0 on error', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('GET', '/users/user123'),
                new Response(404, [])
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $twitterClient = new TwitterClient($client, createTwitterTestCredentials());

        $result = $twitterClient->getFollowerCount(
            accountId: 'user123',
            accessToken: 'test_token'
        );

        expect($result)->toBe(0);
    });
});

describe('TwitterClient::getTimeline', function () {
    it('fetches timeline successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    [
                        'id' => 'tweet1',
                        'text' => 'Latest tweet',
                        'created_at' => '2024-01-15T10:00:00Z',
                        'public_metrics' => [
                            'like_count' => 50,
                            'retweet_count' => 10,
                        ],
                    ],
                ],
                'meta' => [
                    'result_count' => 1,
                    'newest_id' => 'tweet1',
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $twitterClient = new TwitterClient($client, createTwitterTestCredentials());

        $result = $twitterClient->getTimeline(
            accountId: 'user123',
            accessToken: 'test_token',
            maxResults: 10
        );

        expect($result['success'])->toBeTrue();
        expect($result['tweets'])->toHaveCount(1);
        expect($result['tweets'][0]['id'])->toBe('tweet1');
        expect($result['meta']['result_count'])->toBe(1);
    });

    it('handles timeline errors', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('GET', '/users/user123/tweets'),
                new Response(401, [])
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $twitterClient = new TwitterClient($client, createTwitterTestCredentials());

        $result = $twitterClient->getTimeline(
            accountId: 'user123',
            accessToken: 'test_token'
        );

        expect($result['success'])->toBeFalse();
        expect($result['tweets'])->toBeEmpty();
    });
});

describe('TwitterClient::getTweetMetrics', function () {
    it('fetches tweet metrics successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    'id' => 'tweet123',
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
                        'user_profile_clicks' => 50,
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $twitterClient = new TwitterClient($client, createTwitterTestCredentials());

        $result = $twitterClient->getTweetMetrics(
            accessToken: 'test_token',
            tweetId: 'tweet123'
        );

        expect($result['success'])->toBeTrue();
        expect($result['metrics']['impressions'])->toBe(5000);
        expect($result['metrics']['likes'])->toBe(100);
        expect($result['metrics']['retweets'])->toBe(50);
        expect($result['metrics']['replies'])->toBe(25);
        expect($result['metrics']['quotes'])->toBe(10);
        expect($result['metrics']['bookmarks'])->toBe(15);
        expect($result['metrics']['url_clicks'])->toBe(200);
        expect($result['metrics']['profile_clicks'])->toBe(50);
    });

    it('handles metrics errors', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('GET', '/tweets/tweet123'),
                new Response(404, [])
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $twitterClient = new TwitterClient($client, createTwitterTestCredentials());

        $result = $twitterClient->getTweetMetrics(
            accessToken: 'test_token',
            tweetId: 'tweet123'
        );

        expect($result['success'])->toBeFalse();
        expect($result['metrics'])->toBeEmpty();
    });
});
