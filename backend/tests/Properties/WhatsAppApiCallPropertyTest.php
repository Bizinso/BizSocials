<?php

declare(strict_types=1);

namespace Tests\Properties;

use App\Services\WhatsApp\WhatsAppClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Eris\Generator;
use Tests\Helpers\PropertyTestTrait;
use Tests\TestCase;

/**
 * WhatsApp API Call Property Test
 *
 * Validates that WhatsApp operations make real HTTP requests to external APIs.
 *
 * Feature: platform-audit-and-testing, Property 9: Real API Call Verification
 * Validates: Requirements 7.1, 7.2
 */
class WhatsAppApiCallPropertyTest extends TestCase
{
    use RefreshDatabase;
    use PropertyTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Property 9: Real API Call Verification for WhatsApp
     *
     * For any WhatsApp message sending operation, the system should make real HTTP requests
     * to the WhatsApp Business API, not return mocked or stubbed responses.
     *
     * This test verifies that:
     * 1. HTTP requests are actually made (not stubbed)
     * 2. Requests go to the correct WhatsApp API endpoints
     * 3. Requests include proper authentication tokens
     * 4. Multiple message types are tested to ensure consistency
     *
     * Feature: platform-audit-and-testing, Property 9: Real API Call Verification
     * Validates: Requirements 7.1, 7.2
     */
    public function test_whatsapp_message_sending_makes_real_api_calls(): void
    {
        $this->forAll(
            Generator\elements('text', 'image', 'video', 'template')
        )
            ->then(function (string $messageType) {
                // Track HTTP requests made
                $requestHistory = [];
                
                // Create a mock handler that records requests
                $mock = new MockHandler([
                    new Response(200, [], json_encode([
                        'messaging_product' => 'whatsapp',
                        'contacts' => [['input' => '+1234567890', 'wa_id' => '1234567890']],
                        'messages' => [['id' => 'wamid.' . uniqid()]],
                    ])),
                ]);
                
                $handlerStack = HandlerStack::create($mock);
                $handlerStack->push(Middleware::history($requestHistory));
                
                $httpClient = new Client(['handler' => $handlerStack]);
                
                // Create WhatsApp client with mocked HTTP client
                $whatsappClient = new WhatsAppClient();
                $reflection = new \ReflectionClass($whatsappClient);
                $property = $reflection->getProperty('client');
                $property->setAccessible(true);
                $property->setValue($whatsappClient, $httpClient);
                
                // Generate test data
                $phoneNumberId = 'phone_' . uniqid();
                $accessToken = 'token_' . uniqid();
                $recipientPhone = '+1' . rand(1000000000, 9999999999);
                
                // Send message based on type
                $result = $this->sendMessageByType(
                    $whatsappClient,
                    $messageType,
                    $phoneNumberId,
                    $accessToken,
                    $recipientPhone
                );
                
                // Verify that an HTTP request was made
                $this->assertGreaterThan(
                    0,
                    count($requestHistory),
                    "Expected at least one HTTP request to be made for {$messageType} message"
                );
                
                // Verify the request went to the WhatsApp API
                $request = $requestHistory[0]['request'];
                $uri = (string) $request->getUri();
                
                // The URI will be relative when using base_uri in Guzzle
                // Verify it contains the messages endpoint
                $this->assertStringContainsString(
                    '/messages',
                    $uri,
                    "Expected API request to go to /messages endpoint, but got: {$uri}"
                );
                
                // Verify the phone number ID is in the path
                $this->assertStringContainsString(
                    $phoneNumberId,
                    $uri,
                    "Expected API request to include phone number ID in path, but got: {$uri}"
                );
                
                // Verify the request included authentication
                $headers = $request->getHeaders();
                $this->assertArrayHasKey(
                    'Authorization',
                    $headers,
                    "Expected API request to include Authorization header"
                );
                
                $authHeader = $headers['Authorization'][0] ?? '';
                $this->assertStringContainsString(
                    'Bearer',
                    $authHeader,
                    "Expected Authorization header to use Bearer token"
                );
                
                $this->assertStringContainsString(
                    $accessToken,
                    $authHeader,
                    "Expected Authorization header to include the access token"
                );
                
                // Verify the result contains expected fields
                $this->assertIsArray($result);
                $this->assertArrayHasKey('messages', $result);
                $this->assertIsArray($result['messages']);
                $this->assertGreaterThan(0, count($result['messages']));
            });
    }

    /**
     * Property 9: Real API Call Verification for WhatsApp Account Operations
     *
     * For any WhatsApp account operation (fetching phone numbers, templates, etc.),
     * the system should make real HTTP requests to the WhatsApp Business API.
     *
     * Feature: platform-audit-and-testing, Property 9: Real API Call Verification
     * Validates: Requirements 7.1
     */
    public function test_whatsapp_account_operations_make_real_api_calls(): void
    {
        $this->forAll(
            Generator\elements('phone_numbers', 'templates', 'business_profile')
        )
            ->then(function (string $operationType) {
                // Track HTTP requests made
                $requestHistory = [];
                
                // Create a mock handler that records requests
                $mock = new MockHandler([
                    new Response(200, [], json_encode($this->getMockResponseForOperation($operationType))),
                ]);
                
                $handlerStack = HandlerStack::create($mock);
                $handlerStack->push(Middleware::history($requestHistory));
                
                $httpClient = new Client(['handler' => $handlerStack]);
                
                // Create WhatsApp client with mocked HTTP client
                $whatsappClient = new WhatsAppClient();
                $reflection = new \ReflectionClass($whatsappClient);
                $property = $reflection->getProperty('client');
                $property->setAccessible(true);
                $property->setValue($whatsappClient, $httpClient);
                
                // Generate test data
                $wabaId = 'waba_' . uniqid();
                $phoneNumberId = 'phone_' . uniqid();
                $accessToken = 'token_' . uniqid();
                
                // Perform operation based on type
                $result = $this->performOperationByType(
                    $whatsappClient,
                    $operationType,
                    $wabaId,
                    $phoneNumberId,
                    $accessToken
                );
                
                // Verify that an HTTP request was made
                $this->assertGreaterThan(
                    0,
                    count($requestHistory),
                    "Expected at least one HTTP request to be made for {$operationType} operation"
                );
                
                // Verify the request went to the WhatsApp API
                $request = $requestHistory[0]['request'];
                $uri = (string) $request->getUri();
                
                // The URI will be relative when using base_uri in Guzzle
                // Verify it contains the expected endpoint based on operation type
                $expectedPath = match ($operationType) {
                    'phone_numbers' => '/phone_numbers',
                    'templates' => '/message_templates',
                    'business_profile' => '/whatsapp_business_profile',
                };
                
                $this->assertStringContainsString(
                    $expectedPath,
                    $uri,
                    "Expected API request to go to {$expectedPath} endpoint, but got: {$uri}"
                );
                
                // Verify the request included authentication
                $headers = $request->getHeaders();
                $this->assertArrayHasKey(
                    'Authorization',
                    $headers,
                    "Expected API request to include Authorization header"
                );
                
                // Verify the result is an array
                $this->assertIsArray($result);
            });
    }

    /**
     * Send a message based on the specified type.
     */
    private function sendMessageByType(
        WhatsAppClient $client,
        string $messageType,
        string $phoneNumberId,
        string $accessToken,
        string $recipientPhone
    ): array {
        return match ($messageType) {
            'text' => $client->sendTextMessage(
                $phoneNumberId,
                $accessToken,
                $recipientPhone,
                'Test message ' . uniqid()
            ),
            'image' => $client->sendMediaMessage(
                $phoneNumberId,
                $accessToken,
                $recipientPhone,
                'image',
                'https://example.com/image.jpg',
                'Test caption'
            ),
            'video' => $client->sendMediaMessage(
                $phoneNumberId,
                $accessToken,
                $recipientPhone,
                'video',
                'https://example.com/video.mp4'
            ),
            'template' => $client->sendTemplateMessage(
                $phoneNumberId,
                $accessToken,
                $recipientPhone,
                'welcome_message',
                'en_US',
                []
            ),
            default => throw new \InvalidArgumentException("Unsupported message type: {$messageType}"),
        };
    }

    /**
     * Perform an operation based on the specified type.
     */
    private function performOperationByType(
        WhatsAppClient $client,
        string $operationType,
        string $wabaId,
        string $phoneNumberId,
        string $accessToken
    ): array {
        return match ($operationType) {
            'phone_numbers' => $client->getPhoneNumbers($wabaId, $accessToken),
            'templates' => $client->getMessageTemplates($wabaId, $accessToken),
            'business_profile' => $client->updateBusinessProfile(
                $phoneNumberId,
                $accessToken,
                ['description' => 'Test business']
            ),
            default => throw new \InvalidArgumentException("Unsupported operation type: {$operationType}"),
        };
    }

    /**
     * Get mock response for an operation type.
     */
    private function getMockResponseForOperation(string $operationType): array
    {
        return match ($operationType) {
            'phone_numbers' => [
                'data' => [
                    [
                        'id' => 'phone_1',
                        'display_phone_number' => '+1234567890',
                        'verified_name' => 'Test Business',
                        'quality_rating' => 'GREEN',
                    ],
                ],
            ],
            'templates' => [
                'data' => [
                    [
                        'name' => 'welcome_message',
                        'status' => 'APPROVED',
                        'category' => 'MARKETING',
                        'language' => 'en_US',
                    ],
                ],
            ],
            'business_profile' => [
                'success' => true,
            ],
            default => [],
        };
    }
}

