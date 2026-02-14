<?php

declare(strict_types=1);

namespace Tests\Properties;

use App\Data\Social\PlatformCredentials;
use App\Services\Social\FacebookClient;
use App\Services\Social\InstagramClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Eris\Generator;
use Tests\Helpers\PropertyTestTrait;
use Tests\TestCase;

/**
 * Facebook/Instagram API Call Property Test
 *
 * Validates that Facebook and Instagram operations make real HTTP requests to external APIs.
 *
 * Feature: platform-audit-and-testing, Property 9: Real API Call Verification
 * Validates: Requirements 2.1, 3.4
 */
class FacebookInstagramApiCallPropertyTest extends TestCase
{
    use RefreshDatabase;
    use PropertyTestTrait;

    /**
     * Property 9: Real API Call Verification for Facebook Post Publishing
     *
     * For any Facebook post publishing operation, the system should make real HTTP requests
     * to the Facebook Graph API, not return mocked or stubbed responses.
     *
     * This test verifies that:
     * 1. HTTP requests are actually made (not stubbed)
     * 2. Requests go to the correct Facebook API endpoints
     * 3. Requests include proper authentication tokens
     * 4. Multiple post types are tested to ensure consistency
     *
     * Feature: platform-audit-and-testing, Property 9: Real API Call Verification
     * Validates: Requirements 2.1, 3.4
     */
    public function test_facebook_post_publishing_makes_real_api_calls(): void
    {
        $this->forAll(
            Generator\elements('text', 'image', 'video', 'link')
        )
            ->then(function (string $postType) {
                // Track HTTP requests made
                $requestHistory = [];
                
                // Create a mock handler that records requests
                $mock = new MockHandler([
                    new Response(200, [], json_encode([
                        'id' => 'post_' . uniqid(),
                    ])),
                ]);
                
                $handlerStack = HandlerStack::create($mock);
                $handlerStack->push(Middleware::history($requestHistory));
                
                $httpClient = new Client(['handler' => $handlerStack]);
                
                // Create Facebook client with mocked HTTP client
                $credentials = new PlatformCredentials(
                    appId: 'test_app_id',
                    appSecret: 'test_app_secret',
                    redirectUri: 'http://localhost/callback',
                    apiVersion: 'v18.0',
                    scopes: ['pages_manage_posts', 'pages_read_engagement']
                );
                
                $facebookClient = new FacebookClient($httpClient, $credentials);
                
                // Generate test data
                $pageId = 'page_' . uniqid();
                $accessToken = 'token_' . uniqid();
                $message = 'Test post message ' . uniqid();
                
                // Publish post based on type
                $result = $this->publishFacebookPostByType(
                    $facebookClient,
                    $postType,
                    $pageId,
                    $accessToken,
                    $message
                );
                
                // Verify that an HTTP request was made
                $this->assertGreaterThan(
                    0,
                    count($requestHistory),
                    "Expected at least one HTTP request to be made for {$postType} post"
                );
                
                // Verify the request went to the Facebook Graph API
                $request = $requestHistory[0]['request'];
                $uri = (string) $request->getUri();
                
                // Verify it contains the Facebook Graph API domain
                $this->assertStringContainsString(
                    'graph.facebook.com',
                    $uri,
                    "Expected API request to go to graph.facebook.com, but got: {$uri}"
                );
                
                // Verify the page ID is in the path
                $this->assertStringContainsString(
                    $pageId,
                    $uri,
                    "Expected API request to include page ID in path, but got: {$uri}"
                );
                
                // Verify the correct endpoint based on post type
                $expectedEndpoint = match ($postType) {
                    'image' => '/photos',
                    'video' => '/videos',
                    default => '/feed',
                };
                
                $this->assertStringContainsString(
                    $expectedEndpoint,
                    $uri,
                    "Expected API request to go to {$expectedEndpoint} endpoint, but got: {$uri}"
                );
                
                // Verify the request included authentication
                $body = (string) $request->getBody();
                $this->assertStringContainsString(
                    'access_token',
                    $body,
                    "Expected API request to include access_token parameter"
                );
                
                $this->assertStringContainsString(
                    $accessToken,
                    $body,
                    "Expected API request to include the access token"
                );
                
                // Verify the result contains expected fields
                $this->assertIsArray($result);
                $this->assertArrayHasKey('success', $result);
                $this->assertTrue($result['success']);
            });
    }

    /**
     * Property 9: Real API Call Verification for Facebook Data Fetching
     *
     * For any Facebook data fetching operation (posts, comments, insights),
     * the system should make real HTTP requests to the Facebook Graph API.
     *
     * Feature: platform-audit-and-testing, Property 9: Real API Call Verification
     * Validates: Requirements 2.1
     */
    public function test_facebook_data_fetching_makes_real_api_calls(): void
    {
        $this->forAll(
            Generator\elements('posts', 'comments', 'insights', 'pages')
        )
            ->then(function (string $operationType) {
                // Track HTTP requests made
                $requestHistory = [];
                
                // Create a mock handler that records requests
                $mock = new MockHandler([
                    new Response(200, [], json_encode($this->getMockFacebookResponse($operationType))),
                    new Response(200, [], json_encode($this->getMockFacebookResponse($operationType))),
                ]);
                
                $handlerStack = HandlerStack::create($mock);
                $handlerStack->push(Middleware::history($requestHistory));
                
                $httpClient = new Client(['handler' => $handlerStack]);
                
                // Create Facebook client
                $credentials = new PlatformCredentials(
                    appId: 'test_app_id',
                    appSecret: 'test_app_secret',
                    redirectUri: 'http://localhost/callback',
                    apiVersion: 'v18.0',
                    scopes: ['pages_read_engagement']
                );
                
                $facebookClient = new FacebookClient($httpClient, $credentials);
                
                // Generate test data
                $resourceId = 'resource_' . uniqid();
                $accessToken = 'token_' . uniqid();
                
                // Perform operation based on type
                $result = $this->performFacebookOperationByType(
                    $facebookClient,
                    $operationType,
                    $resourceId,
                    $accessToken
                );
                
                // Verify that an HTTP request was made
                $this->assertGreaterThan(
                    0,
                    count($requestHistory),
                    "Expected at least one HTTP request to be made for {$operationType} operation"
                );
                
                // Verify the request went to the Facebook Graph API
                $request = $requestHistory[0]['request'];
                $uri = (string) $request->getUri();
                
                $this->assertStringContainsString(
                    'graph.facebook.com',
                    $uri,
                    "Expected API request to go to graph.facebook.com, but got: {$uri}"
                );
                
                // Verify the request included authentication
                $queryString = $request->getUri()->getQuery();
                $this->assertStringContainsString(
                    'access_token',
                    $queryString,
                    "Expected API request to include access_token in query string"
                );
                
                // Verify the result is an array
                $this->assertIsArray($result);
            });
    }

    /**
     * Property 9: Real API Call Verification for Instagram Post Publishing
     *
     * For any Instagram post publishing operation, the system should make real HTTP requests
     * to the Instagram Graph API, not return mocked or stubbed responses.
     *
     * Feature: platform-audit-and-testing, Property 9: Real API Call Verification
     * Validates: Requirements 2.2, 3.4
     */
    public function test_instagram_post_publishing_makes_real_api_calls(): void
    {
        $this->forAll(
            Generator\elements('image', 'video', 'carousel', 'story')
        )
            ->then(function (string $postType) {
                // Track HTTP requests made
                $requestHistory = [];
                
                // Create a mock handler that records requests
                // Instagram requires multiple API calls (create container, publish)
                // Add enough responses for all possible API calls (carousel needs most)
                $responses = [];
                for ($i = 0; $i < 10; $i++) {
                    $responses[] = new Response(200, [], json_encode(['id' => 'container_' . $i]));
                }
                $responses[] = new Response(200, [], json_encode(['status_code' => 'FINISHED']));
                $responses[] = new Response(200, [], json_encode(['id' => 'media_' . uniqid()]));
                $responses[] = new Response(200, [], json_encode(['permalink' => 'https://instagram.com/p/test']));
                
                $mock = new MockHandler($responses);
                
                $handlerStack = HandlerStack::create($mock);
                $handlerStack->push(Middleware::history($requestHistory));
                
                $httpClient = new Client(['handler' => $handlerStack]);
                
                // Create Instagram client
                $credentials = new PlatformCredentials(
                    appId: 'test_app_id',
                    appSecret: 'test_app_secret',
                    redirectUri: 'http://localhost/callback',
                    apiVersion: 'v18.0',
                    scopes: ['instagram_basic', 'instagram_content_publish']
                );
                
                $instagramClient = new InstagramClient($httpClient, $credentials);
                
                // Generate test data
                $igUserId = 'ig_user_' . uniqid();
                $accessToken = 'token_' . uniqid();
                $caption = 'Test caption ' . uniqid();
                
                // Publish post based on type
                $result = $this->publishInstagramPostByType(
                    $instagramClient,
                    $postType,
                    $igUserId,
                    $accessToken,
                    $caption
                );
                
                // Verify that HTTP requests were made
                $this->assertGreaterThan(
                    0,
                    count($requestHistory),
                    "Expected at least one HTTP request to be made for {$postType} post"
                );
                
                // Verify the first request went to the Instagram Graph API
                $request = $requestHistory[0]['request'];
                $uri = (string) $request->getUri();
                
                // Instagram uses Facebook Graph API
                $this->assertStringContainsString(
                    'graph.facebook.com',
                    $uri,
                    "Expected API request to go to graph.facebook.com, but got: {$uri}"
                );
                
                // Verify the IG user ID is in the path
                $this->assertStringContainsString(
                    $igUserId,
                    $uri,
                    "Expected API request to include IG user ID in path, but got: {$uri}"
                );
                
                // Verify it's calling the media endpoint
                $this->assertStringContainsString(
                    '/media',
                    $uri,
                    "Expected API request to go to /media endpoint, but got: {$uri}"
                );
                
                // Verify the request included authentication
                $body = (string) $request->getBody();
                $this->assertStringContainsString(
                    'access_token',
                    $body,
                    "Expected API request to include access_token parameter"
                );
                
                // Verify the result contains expected fields
                $this->assertIsArray($result);
                $this->assertArrayHasKey('success', $result);
            });
    }

    /**
     * Property 9: Real API Call Verification for Instagram Data Fetching
     *
     * For any Instagram data fetching operation (media, comments, insights),
     * the system should make real HTTP requests to the Instagram Graph API.
     *
     * Feature: platform-audit-and-testing, Property 9: Real API Call Verification
     * Validates: Requirements 2.2
     */
    public function test_instagram_data_fetching_makes_real_api_calls(): void
    {
        $this->forAll(
            Generator\elements('media', 'comments', 'insights')
        )
            ->then(function (string $operationType) {
                // Track HTTP requests made
                $requestHistory = [];
                
                // Create a mock handler that records requests
                $mock = new MockHandler([
                    new Response(200, [], json_encode($this->getMockInstagramResponse($operationType))),
                    new Response(200, [], json_encode($this->getMockInstagramResponse($operationType))),
                ]);
                
                $handlerStack = HandlerStack::create($mock);
                $handlerStack->push(Middleware::history($requestHistory));
                
                $httpClient = new Client(['handler' => $handlerStack]);
                
                // Create Instagram client
                $credentials = new PlatformCredentials(
                    appId: 'test_app_id',
                    appSecret: 'test_app_secret',
                    redirectUri: 'http://localhost/callback',
                    apiVersion: 'v18.0',
                    scopes: ['instagram_basic', 'instagram_manage_insights']
                );
                
                $instagramClient = new InstagramClient($httpClient, $credentials);
                
                // Generate test data
                $resourceId = 'resource_' . uniqid();
                $accessToken = 'token_' . uniqid();
                
                // Perform operation based on type
                $result = $this->performInstagramOperationByType(
                    $instagramClient,
                    $operationType,
                    $resourceId,
                    $accessToken
                );
                
                // Verify that an HTTP request was made
                $this->assertGreaterThan(
                    0,
                    count($requestHistory),
                    "Expected at least one HTTP request to be made for {$operationType} operation"
                );
                
                // Verify the request went to the Instagram Graph API
                $request = $requestHistory[0]['request'];
                $uri = (string) $request->getUri();
                
                $this->assertStringContainsString(
                    'graph.facebook.com',
                    $uri,
                    "Expected API request to go to graph.facebook.com, but got: {$uri}"
                );
                
                // Verify the request included authentication
                $queryString = $request->getUri()->getQuery();
                $this->assertStringContainsString(
                    'access_token',
                    $queryString,
                    "Expected API request to include access_token in query string"
                );
                
                // Verify the result is an array
                $this->assertIsArray($result);
            });
    }

    /**
     * Publish a Facebook post based on the specified type.
     */
    private function publishFacebookPostByType(
        FacebookClient $client,
        string $postType,
        string $pageId,
        string $accessToken,
        string $message
    ): array {
        return match ($postType) {
            'text' => $client->publishPost($pageId, $accessToken, $message),
            'image' => $client->publishPost($pageId, $accessToken, $message, [
                'image_url' => 'https://example.com/image.jpg',
            ]),
            'video' => $client->publishPost($pageId, $accessToken, $message, [
                'video_url' => 'https://example.com/video.mp4',
            ]),
            'link' => $client->publishPost($pageId, $accessToken, $message, [
                'link' => 'https://example.com/article',
            ]),
            default => throw new \InvalidArgumentException("Unsupported post type: {$postType}"),
        };
    }

    /**
     * Perform a Facebook operation based on the specified type.
     */
    private function performFacebookOperationByType(
        FacebookClient $client,
        string $operationType,
        string $resourceId,
        string $accessToken
    ): array {
        return match ($operationType) {
            'posts' => $client->fetchPosts($resourceId, $accessToken),
            'comments' => $client->fetchComments($resourceId, $accessToken),
            'insights' => $client->getPostInsights($resourceId, $accessToken),
            'pages' => $client->getPages($accessToken),
            default => throw new \InvalidArgumentException("Unsupported operation type: {$operationType}"),
        };
    }

    /**
     * Publish an Instagram post based on the specified type.
     */
    private function publishInstagramPostByType(
        InstagramClient $client,
        string $postType,
        string $igUserId,
        string $accessToken,
        string $caption
    ): array {
        return match ($postType) {
            'image' => $client->publishImagePost(
                $igUserId,
                $accessToken,
                'https://example.com/image.jpg',
                $caption
            ),
            'video' => $client->publishVideoPost(
                $igUserId,
                $accessToken,
                'https://example.com/video.mp4',
                $caption
            ),
            'carousel' => $client->publishCarouselPost(
                $igUserId,
                $accessToken,
                [
                    ['type' => 'IMAGE', 'url' => 'https://example.com/image1.jpg'],
                    ['type' => 'IMAGE', 'url' => 'https://example.com/image2.jpg'],
                ],
                $caption
            ),
            'story' => $client->publishStory(
                $igUserId,
                $accessToken,
                'https://example.com/story.jpg',
                'IMAGE'
            ),
            default => throw new \InvalidArgumentException("Unsupported post type: {$postType}"),
        };
    }

    /**
     * Perform an Instagram operation based on the specified type.
     */
    private function performInstagramOperationByType(
        InstagramClient $client,
        string $operationType,
        string $resourceId,
        string $accessToken
    ): array {
        return match ($operationType) {
            'media' => $client->fetchMedia($resourceId, $accessToken),
            'comments' => $client->fetchComments($resourceId, $accessToken),
            'insights' => $client->getMediaInsights($resourceId, $accessToken),
            default => throw new \InvalidArgumentException("Unsupported operation type: {$operationType}"),
        };
    }

    /**
     * Get mock Facebook response for an operation type.
     */
    private function getMockFacebookResponse(string $operationType): array
    {
        return match ($operationType) {
            'posts' => [
                'data' => [
                    [
                        'id' => 'post_1',
                        'message' => 'Test post',
                        'created_time' => '2024-01-01T00:00:00+0000',
                    ],
                ],
            ],
            'comments' => [
                'data' => [
                    [
                        'id' => 'comment_1',
                        'message' => 'Test comment',
                        'from' => ['name' => 'Test User', 'id' => 'user_1'],
                    ],
                ],
            ],
            'insights' => [
                'data' => [
                    ['name' => 'post_impressions', 'values' => [['value' => 100]]],
                    ['name' => 'post_engaged_users', 'values' => [['value' => 50]]],
                ],
            ],
            'pages' => [
                'data' => [
                    [
                        'id' => 'page_1',
                        'name' => 'Test Page',
                        'access_token' => 'page_token',
                    ],
                ],
            ],
            default => [],
        };
    }

    /**
     * Get mock Instagram response for an operation type.
     */
    private function getMockInstagramResponse(string $operationType): array
    {
        return match ($operationType) {
            'media' => [
                'data' => [
                    [
                        'id' => 'media_1',
                        'caption' => 'Test caption',
                        'media_type' => 'IMAGE',
                        'timestamp' => '2024-01-01T00:00:00+0000',
                    ],
                ],
            ],
            'comments' => [
                'data' => [
                    [
                        'id' => 'comment_1',
                        'text' => 'Test comment',
                        'username' => 'testuser',
                    ],
                ],
            ],
            'insights' => [
                'data' => [
                    ['name' => 'impressions', 'values' => [['value' => 200]]],
                    ['name' => 'reach', 'values' => [['value' => 150]]],
                ],
            ],
            default => [],
        };
    }
}
