<?php

declare(strict_types=1);

/**
 * WhatsAppClient Unit Tests
 *
 * Tests for the WhatsApp Business API client service:
 * - Sending text, media, and template messages
 * - Webhook processing
 * - Error handling and rate limiting
 * - Authentication
 *
 * @see \App\Services\WhatsApp\WhatsAppClient
 */

use App\Exceptions\WhatsAppApiException;
use App\Exceptions\WhatsAppAuthenticationException;
use App\Exceptions\WhatsAppRateLimitException;
use App\Exceptions\WhatsAppServerException;
use App\Exceptions\WhatsAppValidationException;
use App\Services\WhatsApp\WhatsAppClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;

describe('WhatsAppClient::sendTextMessage', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('sends a text message successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '+1234567890', 'wa_id' => '1234567890']],
                'messages' => [['id' => 'wamid.123456']],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $whatsappClient = new WhatsAppClient();
        $reflection = new ReflectionClass($whatsappClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($whatsappClient, $client);

        $result = $whatsappClient->sendTextMessage(
            phoneNumberId: 'phone_123',
            accessToken: 'test_token',
            recipientPhone: '+1234567890',
            text: 'Hello from WhatsApp!'
        );

        expect($result)->toHaveKey('messages');
        expect($result['messages'][0]['id'])->toBe('wamid.123456');
        expect($requestHistory)->toHaveCount(1);

        $uri = (string) $requestHistory[0]['request']->getUri();
        expect($uri)->toContain('phone_123/messages');

        $body = json_decode((string) $requestHistory[0]['request']->getBody(), true);
        expect($body['type'])->toBe('text');
        expect($body['text']['body'])->toBe('Hello from WhatsApp!');
    });

    it('handles authentication errors', function () {
        $mock = new MockHandler([
            new Response(401, [], json_encode([
                'error' => [
                    'message' => 'Invalid OAuth access token',
                    'type' => 'OAuthException',
                    'code' => 190,
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $whatsappClient = new WhatsAppClient();
        $reflection = new ReflectionClass($whatsappClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($whatsappClient, $client);

        try {
            $whatsappClient->sendTextMessage(
                phoneNumberId: 'phone_123',
                accessToken: 'invalid_token',
                recipientPhone: '+1234567890',
                text: 'Test'
            );
            expect(false)->toBeTrue(); // Should not reach here
        } catch (WhatsAppAuthenticationException $e) {
            expect($e)->toBeInstanceOf(WhatsAppAuthenticationException::class);
        }
    });

    it('enforces rate limiting', function () {
        // Fill up the rate limit
        Cache::put('whatsapp_rate_limit:phone_123', 80, now()->addMinutes(1));

        $mock = new MockHandler([
            new Response(200, [], json_encode(['messages' => [['id' => 'wamid.123']]])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $whatsappClient = new WhatsAppClient();
        $reflection = new ReflectionClass($whatsappClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($whatsappClient, $client);

        try {
            $whatsappClient->sendTextMessage(
                phoneNumberId: 'phone_123',
                accessToken: 'test_token',
                recipientPhone: '+1234567890',
                text: 'Test'
            );
            expect(false)->toBeTrue(); // Should not reach here
        } catch (WhatsAppRateLimitException $e) {
            expect($e)->toBeInstanceOf(WhatsAppRateLimitException::class);
        }
    });
});

describe('WhatsAppClient::sendMediaMessage', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('sends an image message successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.image123']],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $whatsappClient = new WhatsAppClient();
        $reflection = new ReflectionClass($whatsappClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($whatsappClient, $client);

        $result = $whatsappClient->sendMediaMessage(
            phoneNumberId: 'phone_123',
            accessToken: 'test_token',
            recipientPhone: '+1234567890',
            mediaType: 'image',
            mediaUrl: 'https://example.com/image.jpg',
            caption: 'Check this out!'
        );

        expect($result['messages'][0]['id'])->toBe('wamid.image123');

        $body = json_decode((string) $requestHistory[0]['request']->getBody(), true);
        expect($body['type'])->toBe('image');
        expect($body['image']['link'])->toBe('https://example.com/image.jpg');
        expect($body['image']['caption'])->toBe('Check this out!');
    });

    it('sends a video message without caption', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'messages' => [['id' => 'wamid.video456']],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $whatsappClient = new WhatsAppClient();
        $reflection = new ReflectionClass($whatsappClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($whatsappClient, $client);

        $result = $whatsappClient->sendMediaMessage(
            phoneNumberId: 'phone_123',
            accessToken: 'test_token',
            recipientPhone: '+1234567890',
            mediaType: 'video',
            mediaUrl: 'https://example.com/video.mp4'
        );

        expect($result['messages'][0]['id'])->toBe('wamid.video456');
    });
});

describe('WhatsAppClient::sendTemplateMessage', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('sends a template message successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.template789']],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $whatsappClient = new WhatsAppClient();
        $reflection = new ReflectionClass($whatsappClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($whatsappClient, $client);

        $result = $whatsappClient->sendTemplateMessage(
            phoneNumberId: 'phone_123',
            accessToken: 'test_token',
            recipientPhone: '+1234567890',
            templateName: 'welcome_message',
            languageCode: 'en_US',
            components: [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => 'John'],
                    ],
                ],
            ]
        );

        expect($result['messages'][0]['id'])->toBe('wamid.template789');

        $body = json_decode((string) $requestHistory[0]['request']->getBody(), true);
        expect($body['type'])->toBe('template');
        expect($body['template']['name'])->toBe('welcome_message');
        expect($body['template']['language']['code'])->toBe('en_US');
    });

    it('handles validation errors', function () {
        $mock = new MockHandler([
            new Response(400, [], json_encode([
                'error' => [
                    'message' => 'Invalid template name',
                    'type' => 'ValidationError',
                    'code' => 100,
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $whatsappClient = new WhatsAppClient();
        $reflection = new ReflectionClass($whatsappClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($whatsappClient, $client);

        try {
            $whatsappClient->sendTemplateMessage(
                phoneNumberId: 'phone_123',
                accessToken: 'test_token',
                recipientPhone: '+1234567890',
                templateName: 'invalid_template',
                languageCode: 'en_US'
            );
            expect(false)->toBeTrue(); // Should not reach here
        } catch (WhatsAppValidationException $e) {
            expect($e)->toBeInstanceOf(WhatsAppValidationException::class);
        }
    });
});

describe('WhatsAppClient::markMessageAsRead', function () {
    it('marks a message as read successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $whatsappClient = new WhatsAppClient();
        $reflection = new ReflectionClass($whatsappClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($whatsappClient, $client);

        $result = $whatsappClient->markMessageAsRead(
            phoneNumberId: 'phone_123',
            accessToken: 'test_token',
            messageId: 'wamid.123456'
        );

        expect($result)->toHaveKey('success');

        $body = json_decode((string) $requestHistory[0]['request']->getBody(), true);
        expect($body['status'])->toBe('read');
        expect($body['message_id'])->toBe('wamid.123456');
    });
});

describe('WhatsAppClient::getPhoneNumbers', function () {
    it('fetches phone numbers successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    [
                        'id' => 'phone_1',
                        'display_phone_number' => '+1234567890',
                        'verified_name' => 'My Business',
                        'quality_rating' => 'GREEN',
                    ],
                    [
                        'id' => 'phone_2',
                        'display_phone_number' => '+0987654321',
                        'verified_name' => 'My Store',
                        'quality_rating' => 'GREEN',
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $whatsappClient = new WhatsAppClient();
        $reflection = new ReflectionClass($whatsappClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($whatsappClient, $client);

        $result = $whatsappClient->getPhoneNumbers(
            wabaId: 'waba_123',
            accessToken: 'test_token'
        );

        expect($result['data'])->toHaveCount(2);
        expect($result['data'][0]['display_phone_number'])->toBe('+1234567890');
    });
});

describe('WhatsAppClient::getMessageTemplates', function () {
    it('fetches message templates successfully', function () {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    [
                        'name' => 'welcome_message',
                        'status' => 'APPROVED',
                        'category' => 'MARKETING',
                        'language' => 'en_US',
                    ],
                    [
                        'name' => 'order_confirmation',
                        'status' => 'APPROVED',
                        'category' => 'UTILITY',
                        'language' => 'en_US',
                    ],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $whatsappClient = new WhatsAppClient();
        $reflection = new ReflectionClass($whatsappClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($whatsappClient, $client);

        $result = $whatsappClient->getMessageTemplates(
            wabaId: 'waba_123',
            accessToken: 'test_token'
        );

        expect($result['data'])->toHaveCount(2);
        expect($result['data'][0]['name'])->toBe('welcome_message');
        expect($result['data'][0]['status'])->toBe('APPROVED');
    });
});

describe('WhatsAppClient::updateBusinessProfile', function () {
    it('updates business profile successfully', function () {
        $requestHistory = [];
        $history = Middleware::history($requestHistory);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $whatsappClient = new WhatsAppClient();
        $reflection = new ReflectionClass($whatsappClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($whatsappClient, $client);

        $result = $whatsappClient->updateBusinessProfile(
            phoneNumberId: 'phone_123',
            accessToken: 'test_token',
            profile: [
                'description' => 'We are a great business',
                'address' => '123 Main St',
                'website' => 'https://example.com',
                'email' => 'support@example.com',
            ]
        );

        expect($result)->toHaveKey('success');

        $body = json_decode((string) $requestHistory[0]['request']->getBody(), true);
        expect($body['description'])->toBe('We are a great business');
        expect($body['websites'])->toBe(['https://example.com']);
    });
});

describe('WhatsAppClient::getRateLimitStatus', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('returns correct rate limit status', function () {
        $whatsappClient = new WhatsAppClient();

        // Make some requests
        for ($i = 0; $i < 10; $i++) {
            Cache::increment('whatsapp_rate_limit:phone_123');
        }

        $status = $whatsappClient->getRateLimitStatus('phone_123');

        expect($status['limit'])->toBe(80);
        expect($status['used'])->toBe(10);
        expect($status['remaining'])->toBe(70);
        expect($status['window_seconds'])->toBe(60);
    });

    it('returns zero remaining when limit is reached', function () {
        $whatsappClient = new WhatsAppClient();

        // Fill up the rate limit
        for ($i = 0; $i < 85; $i++) {
            Cache::increment('whatsapp_rate_limit:phone_123');
        }

        $status = $whatsappClient->getRateLimitStatus('phone_123');

        expect($status['remaining'])->toBe(0);
        expect($status['used'])->toBe(85);
    });
});

describe('WhatsAppClient::error handling', function () {
    it('handles server errors', function () {
        $mock = new MockHandler([
            new Response(500, [], json_encode([
                'error' => [
                    'message' => 'Internal server error',
                    'type' => 'ServerError',
                    'code' => 500,
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $whatsappClient = new WhatsAppClient();
        $reflection = new ReflectionClass($whatsappClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($whatsappClient, $client);

        try {
            $whatsappClient->getPhoneNumbers(
                wabaId: 'waba_123',
                accessToken: 'test_token'
            );
            expect(false)->toBeTrue(); // Should not reach here
        } catch (WhatsAppServerException $e) {
            expect($e)->toBeInstanceOf(WhatsAppServerException::class);
        }
    });

    it('handles rate limit errors from API', function () {
        $mock = new MockHandler([
            new Response(429, [], json_encode([
                'error' => [
                    'message' => 'Too many requests',
                    'type' => 'RateLimitError',
                    'code' => 429,
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $whatsappClient = new WhatsAppClient();
        $reflection = new ReflectionClass($whatsappClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($whatsappClient, $client);

        Cache::flush();

        try {
            $whatsappClient->sendTextMessage(
                phoneNumberId: 'phone_123',
                accessToken: 'test_token',
                recipientPhone: '+1234567890',
                text: 'Test'
            );
            expect(false)->toBeTrue(); // Should not reach here
        } catch (WhatsAppRateLimitException $e) {
            expect($e)->toBeInstanceOf(WhatsAppRateLimitException::class);
        }
    });
});

