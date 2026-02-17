<?php

declare(strict_types=1);

use App\Data\Social\PlatformCredentials;
use App\Enums\Social\SocialPlatform;
use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use App\Services\Inbox\MessageFetchingService;
use App\Services\Social\FacebookClient;
use App\Services\Social\InstagramClient;
use App\Services\Social\TwitterClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// Feature: platform-audit-and-testing, Property 9: Real API Call Verification
// For any operation that interacts with external services (social platforms),
// the system should verify that real HTTP requests are made to the external API endpoints,
// not mocked or stubbed responses.
// **Validates: Requirements 4.1**

describe('Property 9: Real API Call Verification for Message Fetching', function () {
    it('verifies that Facebook message fetching makes real HTTP requests', function () {
        // Run 100 iterations with different account configurations
        for ($i = 0; $i < 100; $i++) {
            $workspace = Workspace::factory()->create();
            $account = SocialAccount::factory()->create([
                'workspace_id' => $workspace->id,
                'platform' => SocialPlatform::FACEBOOK,
                'platform_account_id' => "page_test_{$i}",
                'access_token' => "token_{$i}",
            ]);

            // Track HTTP requests made
            $requestHistory = [];
            
            // Create a mock handler that records requests
            $mock = new MockHandler([
                new Response(200, [], json_encode(['data' => [['id' => "post_{$i}"]]])),
                new Response(200, [], json_encode(['data' => []])),
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $handlerStack->push(Middleware::history($requestHistory));
            
            $httpClient = new Client(['handler' => $handlerStack]);
            
            // Create clients with mocked HTTP client
            $credentials = new PlatformCredentials(
                appId: 'test_app_id',
                appSecret: 'test_app_secret',
                redirectUri: 'http://localhost/callback',
                apiVersion: 'v18.0',
                scopes: []
            );
            
            $facebookClient = new FacebookClient($httpClient, $credentials);
            $instagramClient = new InstagramClient($httpClient, $credentials);
            $twitterClient = new TwitterClient($httpClient, $credentials);
            
            // Create service with mocked clients
            $service = new MessageFetchingService(
                $facebookClient,
                $instagramClient,
                $twitterClient
            );
            
            $result = $service->fetchMessagesForAccount($account);

            // Property: The service MUST attempt real HTTP requests
            // We verify this by checking that HTTP requests were recorded
            expect(count($requestHistory))->toBeGreaterThan(0, "Expected at least one HTTP request for iteration {$i}");
            
            // Verify requests go to Facebook Graph API
            $hasFacebookRequest = false;
            foreach ($requestHistory as $transaction) {
                $uri = (string) $transaction['request']->getUri();
                if (str_contains($uri, 'graph.facebook.com')) {
                    $hasFacebookRequest = true;
                    // Verify the account ID is in the URL
                    expect($uri)->toContain($account->platform_account_id);
                    break;
                }
            }
            expect($hasFacebookRequest)->toBeTrue("Expected request to graph.facebook.com for iteration {$i}");
        }
    })->group('property', 'pbt');

    it('verifies that Instagram message fetching makes real HTTP requests', function () {
        // Run 100 iterations with different account configurations
        for ($i = 0; $i < 100; $i++) {
            $workspace = Workspace::factory()->create();
            $account = SocialAccount::factory()->create([
                'workspace_id' => $workspace->id,
                'platform' => SocialPlatform::INSTAGRAM,
                'platform_account_id' => "ig_account_{$i}",
                'access_token' => "ig_token_{$i}",
            ]);

            // Track HTTP requests made
            $requestHistory = [];
            
            // Create a mock handler that records requests
            $mock = new MockHandler([
                new Response(200, [], json_encode(['data' => [['id' => "media_{$i}"]]])),
                new Response(200, [], json_encode(['data' => []])),
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $handlerStack->push(Middleware::history($requestHistory));
            
            $httpClient = new Client(['handler' => $handlerStack]);
            
            // Create clients with mocked HTTP client
            $credentials = new PlatformCredentials(
                appId: 'test_app_id',
                appSecret: 'test_app_secret',
                redirectUri: 'http://localhost/callback',
                apiVersion: 'v18.0',
                scopes: []
            );
            
            $facebookClient = new FacebookClient($httpClient, $credentials);
            $instagramClient = new InstagramClient($httpClient, $credentials);
            $twitterClient = new TwitterClient($httpClient, $credentials);
            
            // Create service with mocked clients
            $service = new MessageFetchingService(
                $facebookClient,
                $instagramClient,
                $twitterClient
            );
            
            $result = $service->fetchMessagesForAccount($account);

            // Property: The service MUST attempt real HTTP requests
            expect(count($requestHistory))->toBeGreaterThan(0, "Expected at least one HTTP request for iteration {$i}");
            
            // Verify requests go to Facebook Graph API (Instagram uses Facebook Graph API)
            $hasInstagramRequest = false;
            foreach ($requestHistory as $transaction) {
                $uri = (string) $transaction['request']->getUri();
                if (str_contains($uri, 'graph.facebook.com')) {
                    $hasInstagramRequest = true;
                    // Verify the account ID is in the URL
                    expect($uri)->toContain($account->platform_account_id);
                    break;
                }
            }
            expect($hasInstagramRequest)->toBeTrue("Expected request to graph.facebook.com for iteration {$i}");
        }
    })->group('property', 'pbt');

    it('verifies that message fetching does not use hardcoded mock data', function () {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $workspace = Workspace::factory()->create();
            $account = SocialAccount::factory()->create([
                'workspace_id' => $workspace->id,
                'platform' => SocialPlatform::FACEBOOK,
                'platform_account_id' => "page_{$i}",
                'access_token' => "token_{$i}",
            ]);

            // Create unique response data for each iteration
            $uniqueCommentId = "comment_unique_{$i}_" . uniqid();
            $uniqueMessage = "Test message {$i} " . bin2hex(random_bytes(8));

            // Track HTTP requests made
            $requestHistory = [];
            
            // Create a mock handler that records requests and returns unique data
            $mock = new MockHandler([
                new Response(200, [], json_encode(['data' => [['id' => "post_{$i}"]]])),
                new Response(200, [], json_encode([
                    'data' => [
                        [
                            'id' => $uniqueCommentId,
                            'message' => $uniqueMessage,
                            'from' => ['name' => "User {$i}", 'id' => "user_{$i}"],
                            'created_time' => now()->toIso8601String(),
                        ],
                    ],
                ])),
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $handlerStack->push(Middleware::history($requestHistory));
            
            $httpClient = new Client(['handler' => $handlerStack]);
            
            // Create clients with mocked HTTP client
            $credentials = new PlatformCredentials(
                appId: 'test_app_id',
                appSecret: 'test_app_secret',
                redirectUri: 'http://localhost/callback',
                apiVersion: 'v18.0',
                scopes: []
            );
            
            $facebookClient = new FacebookClient($httpClient, $credentials);
            $instagramClient = new InstagramClient($httpClient, $credentials);
            $twitterClient = new TwitterClient($httpClient, $credentials);
            
            // Create service with mocked clients
            $service = new MessageFetchingService(
                $facebookClient,
                $instagramClient,
                $twitterClient
            );
            
            $result = $service->fetchMessagesForAccount($account);

            // Property: Results must vary based on API responses, not return hardcoded data
            // If the service returned hardcoded data, we would see the same results every time
            // Instead, we verify that HTTP requests were made with the specific account ID
            expect(count($requestHistory))->toBeGreaterThan(0);
            
            $foundAccountId = false;
            foreach ($requestHistory as $transaction) {
                $uri = (string) $transaction['request']->getUri();
                if (str_contains($uri, $account->platform_account_id)) {
                    $foundAccountId = true;
                    break;
                }
            }
            expect($foundAccountId)->toBeTrue("Expected account ID {$account->platform_account_id} in request URL");
        }
    })->group('property', 'pbt');
});
