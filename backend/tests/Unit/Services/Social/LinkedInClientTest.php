<?php

declare(strict_types=1);

/**
 * LinkedInClient Unit Tests
 *
 * Tests for the LinkedIn API client service:
 * - Publishing posts to personal profiles and company pages
 * - Fetching posts and analytics
 * - Getting organization information
 * - Error handling and rate limiting
 *
 * @see \App\Services\Social\LinkedInClient
 */

use App\Services\Social\LinkedInClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\RateLimiter;

describe('LinkedInClient::publishPost', function () {
    beforeEach(function () {
        RateLimiter::clear('linkedin_api_rate_limit:urn:li:person:test123');
    });

    it('publishes a text post to personal profile successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'urn:li:share:123456'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->publishPost(
            accessToken: 'test_token',
            authorUrn: 'urn:li:person:test123',
            text: 'Hello LinkedIn!'
        );

        expect($result['success'])->toBeTrue();
        expect($result['post_id'])->toBe('urn:li:share:123456');
        expect($result['post_url'])->toContain('linkedin.com/feed/update');
        expect($requestHistory)->toHaveCount(1);

        $uri = (string) $requestHistory[0]['request']->getUri();
        expect($uri)->toContain('/ugcPosts');
    });

    it('publishes a post to company page successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'urn:li:share:789012'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->publishPost(
            accessToken: 'test_token',
            authorUrn: 'urn:li:organization:company123',
            text: 'Company announcement!'
        );

        expect($result['success'])->toBeTrue();
        expect($result['post_id'])->toBe('urn:li:share:789012');
    });

    it('publishes a post with media successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'urn:li:share:media123'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->publishPost(
            accessToken: 'test_token',
            authorUrn: 'urn:li:person:test123',
            text: 'Check out this image!',
            options: [
                'media' => [
                    [
                        'url' => 'https://example.com/image.jpg',
                        'title' => 'Sample Image',
                        'description' => 'A beautiful image',
                    ],
                ],
            ]
        );

        expect($result['success'])->toBeTrue();
        expect($result['post_id'])->toBe('urn:li:share:media123');

        $body = json_decode((string) $requestHistory[0]['request']->getBody(), true);
        expect($body['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'])->toBe('IMAGE');
    });

    it('publishes a post with article link successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'urn:li:share:article123'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->publishPost(
            accessToken: 'test_token',
            authorUrn: 'urn:li:person:test123',
            text: 'Read this article!',
            options: [
                'article_url' => 'https://example.com/article',
                'article_title' => 'Great Article',
                'article_description' => 'Must read',
            ]
        );

        expect($result['success'])->toBeTrue();
        expect($result['post_id'])->toBe('urn:li:share:article123');

        $body = json_decode((string) $requestHistory[0]['request']->getBody(), true);
        expect($body['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'])->toBe('ARTICLE');
    });

    it('handles API errors gracefully', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('POST', '/ugcPosts'),
                new Response(401, [], json_encode([
                    'message' => 'Invalid access token',
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->publishPost(
            accessToken: 'invalid_token',
            authorUrn: 'urn:li:person:test123',
            text: 'Test'
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Invalid access token');
    });

    it('respects rate limits', function () {
        // Fill up the rate limit
        for ($i = 0; $i < 100; $i++) {
            RateLimiter::hit('linkedin_api_rate_limit:urn:li:person:test123', 3600);
        }

        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'urn:li:share:123'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->publishPost(
            accessToken: 'test_token',
            authorUrn: 'urn:li:person:test123',
            text: 'Test'
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toContain('Rate limit exceeded');
    });
});

describe('LinkedInClient::fetchPosts', function () {
    beforeEach(function () {
        RateLimiter::clear('linkedin_api_rate_limit:urn:li:person:test123');
    });

    it('fetches posts successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'elements' => [
                    ['id' => 'urn:li:share:1', 'text' => 'Post 1'],
                    ['id' => 'urn:li:share:2', 'text' => 'Post 2'],
                ],
                'paging' => ['start' => 0, 'count' => 2],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->fetchPosts(
            accessToken: 'test_token',
            authorUrn: 'urn:li:person:test123'
        );

        expect($result['success'])->toBeTrue();
        expect($result['posts'])->toHaveCount(2);
        expect($result['posts'][0]['id'])->toBe('urn:li:share:1');
    });

    it('handles fetch errors', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('GET', '/ugcPosts'),
                new Response(403, [], json_encode([
                    'message' => 'Insufficient permissions',
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->fetchPosts(
            accessToken: 'test_token',
            authorUrn: 'urn:li:person:test123'
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Insufficient permissions');
    });
});

describe('LinkedInClient::getPostAnalytics', function () {
    beforeEach(function () {
        RateLimiter::clear('linkedin_api_rate_limit:urn:li:share:test123');
    });

    it('fetches post analytics successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'elements' => [
                    [
                        'totalShareStatistics' => [
                            'impressionCount' => 1000,
                            'uniqueImpressionsCount' => 800,
                            'clickCount' => 50,
                            'likeCount' => 25,
                            'commentCount' => 10,
                            'shareCount' => 5,
                            'engagement' => 90,
                        ],
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->getPostAnalytics(
            accessToken: 'test_token',
            postUrn: 'urn:li:share:test123'
        );

        expect($result['success'])->toBeTrue();
        expect($result['analytics']['impressions'])->toBe(1000);
        expect($result['analytics']['likes'])->toBe(25);
        expect($result['analytics']['comments'])->toBe(10);
        expect($result['analytics']['shares'])->toBe(5);
    });

    it('handles analytics errors', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('GET', '/organizationalEntityShareStatistics'),
                new Response(404, [], json_encode([
                    'message' => 'Post not found',
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->getPostAnalytics(
            accessToken: 'test_token',
            postUrn: 'urn:li:share:test123'
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Post not found');
    });
});

describe('LinkedInClient::getOrganizationAnalytics', function () {
    beforeEach(function () {
        RateLimiter::clear('linkedin_api_rate_limit:urn:li:organization:test123');
    });

    it('fetches organization analytics successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'elements' => [
                    [
                        'totalPageStatistics' => [
                            'views' => [
                                'allPageViews' => ['pageViews' => 5000],
                            ],
                        ],
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->getOrganizationAnalytics(
            accessToken: 'test_token',
            organizationUrn: 'urn:li:organization:test123'
        );

        expect($result['success'])->toBeTrue();
        expect($result['analytics'])->toBeArray();
    });
});

describe('LinkedInClient::getFollowerStatistics', function () {
    beforeEach(function () {
        RateLimiter::clear('linkedin_api_rate_limit:urn:li:organization:test123');
    });

    it('fetches follower statistics successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'elements' => [
                    ['firstDegreeSize' => 1500],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->getFollowerStatistics(
            accessToken: 'test_token',
            organizationUrn: 'urn:li:organization:test123'
        );

        expect($result['success'])->toBeTrue();
        expect($result['followers']['total'])->toBe(1500);
    });
});

describe('LinkedInClient::getProfile', function () {
    beforeEach(function () {
        RateLimiter::clear('linkedin_api_rate_limit:profile');
    });

    it('fetches user profile successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'sub' => 'user123',
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'picture' => 'https://example.com/photo.jpg',
                'locale' => 'en_US',
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->getProfile(accessToken: 'test_token');

        expect($result['success'])->toBeTrue();
        expect($result['profile']['id'])->toBe('user123');
        expect($result['profile']['name'])->toBe('John Doe');
        expect($result['profile']['email'])->toBe('john@example.com');
    });

    it('handles profile fetch errors', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('GET', '/userinfo'),
                new Response(401, [], json_encode([
                    'error' => 'Unauthorized',
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->getProfile(accessToken: 'invalid_token');

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toContain('Unauthorized');
    });
});

describe('LinkedInClient::getOrganizations', function () {
    beforeEach(function () {
        RateLimiter::clear('linkedin_api_rate_limit:organizations');
    });

    it('fetches user organizations successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'elements' => [
                    [
                        'organizationalTarget' => 'urn:li:organization:123',
                        'organizationalTarget~' => [
                            'localizedName' => 'My Company',
                            'logoV2' => [
                                'original' => 'https://example.com/logo.jpg',
                            ],
                        ],
                    ],
                    [
                        'organizationalTarget' => 'urn:li:organization:456',
                        'organizationalTarget~' => [
                            'localizedName' => 'Another Company',
                        ],
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->getOrganizations(accessToken: 'test_token');

        expect($result['success'])->toBeTrue();
        expect($result['organizations'])->toHaveCount(2);
        expect($result['organizations'][0]['name'])->toBe('My Company');
        expect($result['organizations'][0]['id'])->toBe('urn:li:organization:123');
    });

    it('handles empty organizations list', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['elements' => []])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->getOrganizations(accessToken: 'test_token');

        expect($result['success'])->toBeTrue();
        expect($result['organizations'])->toBeEmpty();
    });
});

describe('LinkedInClient::deletePost', function () {
    beforeEach(function () {
        RateLimiter::clear('linkedin_api_rate_limit:urn:li:share:test123');
    });

    it('deletes post successfully', function () {
        $mock = new MockHandler([
            new Response(204, []),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->deletePost(
            accessToken: 'test_token',
            postUrn: 'urn:li:share:test123'
        );

        expect($result['success'])->toBeTrue();
    });

    it('handles delete errors', function () {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'API Error',
                new \GuzzleHttp\Psr7\Request('DELETE', '/ugcPosts/urn:li:share:test123'),
                new Response(404, [], json_encode([
                    'message' => 'Post not found',
                ]))
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $linkedInClient = new LinkedInClient($client);

        $result = $linkedInClient->deletePost(
            accessToken: 'test_token',
            postUrn: 'urn:li:share:test123'
        );

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Post not found');
    });
});

