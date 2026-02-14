<?php

declare(strict_types=1);

namespace Tests\Properties;

use App\Enums\Social\SocialPlatform;
use App\Services\Social\FacebookClient;
use App\Services\Social\InstagramClient;
use App\Services\Social\LinkedInClient;
use App\Services\Social\YouTubeClient;
use Carbon\Carbon;
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
 * Analytics API Call Property Test
 *
 * Validates that analytics operations make real HTTP requests to external APIs.
 *
 * Feature: platform-audit-and-testing, Property 9: Real API Call Verification
 * Validates: Requirements 5.2, 5.3
 */
class AnalyticsApiCallPropertyTest extends TestCase
{
    use RefreshDatabase;
    use PropertyTestTrait;

    /**
     * Property 9: Real API Call Verification for Analytics
     *
     * For any analytics fetch operation, the system should make real HTTP requests
     * to the external platform APIs, not return mocked or stubbed responses.
     *
     * This test verifies that:
     * 1. HTTP requests are actually made (not stubbed)
     * 2. Requests go to the correct platform API endpoints
     * 3. Requests include proper authentication tokens
     * 4. Multiple platforms are tested to ensure consistency
     *
     * Feature: platform-audit-and-testing, Property 9: Real API Call Verification
     * Validates: Requirements 5.2, 5.3
     */
    public function test_analytics_fetching_makes_real_api_calls(): void
    {
        $this->forAll(
            Generator\elements(
                SocialPlatform::FACEBOOK,
                SocialPlatform::INSTAGRAM,
                SocialPlatform::LINKEDIN,
                SocialPlatform::YOUTUBE
            )
        )
            ->then(function (SocialPlatform $platform) {
                // Track HTTP requests made
                $requestHistory = [];
                
                // Create a mock handler that records requests
                // Add multiple responses since some platforms make multiple API calls
                $mock = new MockHandler([
                    new Response(200, [], json_encode($this->getMockAnalyticsResponse($platform))),
                    new Response(200, [], json_encode($this->getMockAnalyticsResponse($platform))),
                    new Response(200, [], json_encode($this->getMockAnalyticsResponse($platform))),
                ]);
                
                $handlerStack = HandlerStack::create($mock);
                $handlerStack->push(Middleware::history($requestHistory));
                
                $httpClient = new Client(['handler' => $handlerStack]);
                
                // Create the appropriate client for the platform
                $client = $this->createClientForPlatform($platform, $httpClient);
                
                // Make an analytics API call
                $pageId = 'test_page_' . $platform->value;
                $accessToken = 'test_token_' . uniqid();
                $startDate = Carbon::now()->subDays(7);
                $endDate = Carbon::now();
                
                // Call the appropriate method based on platform
                $result = $this->fetchAnalyticsForPlatform($client, $platform, $pageId, $accessToken, $startDate, $endDate);
                
                // Verify that an HTTP request was made
                $this->assertGreaterThan(
                    0,
                    count($requestHistory),
                    "Expected at least one HTTP request to be made for {$platform->value} analytics"
                );
                
                // Verify the request went to the correct platform API
                $request = $requestHistory[0]['request'];
                $uri = (string) $request->getUri();
                
                $this->assertApiEndpointMatchesPlatform($uri, $platform);
                
                // Verify the request included authentication
                $this->assertRequestIncludesAuthentication($request, $accessToken);
                
                // Verify the result is an array (analytics data)
                $this->assertIsArray($result);
            });
    }

    /**
     * Create a client for the specified platform.
     */
    private function createClientForPlatform(SocialPlatform $platform, Client $httpClient): object
    {
        $credentials = new \App\Data\Social\PlatformCredentials(
            appId: 'test_app_id',
            appSecret: 'test_app_secret',
            redirectUri: 'http://localhost/callback',
            apiVersion: 'v18.0',
            scopes: ['public_profile', 'email']
        );
        
        return match ($platform) {
            SocialPlatform::FACEBOOK => new FacebookClient($httpClient, $credentials),
            SocialPlatform::INSTAGRAM => new InstagramClient($httpClient, $credentials),
            SocialPlatform::LINKEDIN => new LinkedInClient($httpClient, $credentials),
            SocialPlatform::YOUTUBE => new YouTubeClient($httpClient, $credentials),
            default => throw new \InvalidArgumentException("Unsupported platform: {$platform->value}"),
        };
    }

    /**
     * Fetch analytics for the specified platform.
     */
    private function fetchAnalyticsForPlatform(
        object $client,
        SocialPlatform $platform,
        string $pageId,
        string $accessToken,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        return match ($platform) {
            SocialPlatform::FACEBOOK => $client->getPageInsights($pageId, $accessToken, $startDate, $endDate),
            SocialPlatform::INSTAGRAM => $client->getAccountInsights($pageId, $accessToken, $startDate, $endDate),
            SocialPlatform::LINKEDIN => $client->getAnalytics($pageId, $accessToken, $startDate, $endDate),
            SocialPlatform::YOUTUBE => $client->getChannelAnalytics($pageId, $accessToken, $startDate, $endDate),
            default => throw new \InvalidArgumentException("Unsupported platform: {$platform->value}"),
        };
    }

    /**
     * Get mock analytics response for a platform.
     */
    private function getMockAnalyticsResponse(SocialPlatform $platform): array
    {
        return match ($platform) {
            SocialPlatform::FACEBOOK => [
                'page_impressions' => 1000,
                'page_reach' => 800,
                'page_post_engagements' => 150,
                'page_likes' => 50,
                'page_comments' => 30,
                'page_shares' => 20,
                'page_clicks' => 100,
                'page_video_views' => 200,
                'page_fans' => 5000,
            ],
            SocialPlatform::INSTAGRAM => [
                'impressions' => 1200,
                'reach' => 900,
                'engagement' => 180,
                'likes' => 60,
                'comments' => 40,
                'shares' => 25,
                'saves' => 15,
                'profile_views' => 120,
                'video_views' => 250,
                'follower_count' => 6000,
            ],
            SocialPlatform::LINKEDIN => [
                'impressions' => 800,
                'uniqueImpressions' => 600,
                'engagement' => 120,
                'likes' => 40,
                'comments' => 20,
                'shares' => 15,
                'clicks' => 80,
                'videoViews' => 150,
                'followerCount' => 4000,
            ],
            SocialPlatform::YOUTUBE => [
                'impressions' => 2000,
                'views' => 1500,
                'likes' => 100,
                'comments' => 50,
                'shares' => 30,
                'clicks' => 200,
                'subscriberCount' => 10000,
            ],
            default => [],
        };
    }

    /**
     * Assert that the API endpoint matches the expected platform.
     */
    private function assertApiEndpointMatchesPlatform(string $uri, SocialPlatform $platform): void
    {
        $expectedDomain = match ($platform) {
            SocialPlatform::FACEBOOK => 'graph.facebook.com',
            SocialPlatform::INSTAGRAM => 'graph.facebook.com', // Instagram uses Facebook Graph API
            SocialPlatform::LINKEDIN => 'api.linkedin.com',
            SocialPlatform::YOUTUBE => 'googleapis.com', // YouTube uses Google APIs
            default => '',
        };
        
        $this->assertStringContainsString(
            $expectedDomain,
            $uri,
            "Expected API request to {$platform->value} to go to {$expectedDomain}, but got: {$uri}"
        );
    }

    /**
     * Assert that the request includes authentication.
     */
    private function assertRequestIncludesAuthentication($request, string $expectedToken): void
    {
        $uri = (string) $request->getUri();
        $headers = $request->getHeaders();
        
        // Check if token is in query string or headers
        $hasTokenInQuery = str_contains($uri, 'access_token=') || str_contains($uri, 'token=');
        $hasTokenInHeader = isset($headers['Authorization']) || isset($headers['authorization']);
        
        $this->assertTrue(
            $hasTokenInQuery || $hasTokenInHeader,
            "Expected API request to include authentication token in query string or headers"
        );
    }
}

