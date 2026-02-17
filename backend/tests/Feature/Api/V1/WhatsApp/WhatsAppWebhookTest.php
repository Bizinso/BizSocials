<?php

declare(strict_types=1);

use App\Jobs\WhatsApp\ProcessWhatsAppWebhookJob;
use App\Models\WhatsApp\WebhookSubscription;
use App\Models\WhatsApp\WhatsAppBusinessAccount;
use App\Models\WhatsApp\WhatsAppPhoneNumber;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();

    // Create WhatsApp Business Account
    $this->waba = WhatsAppBusinessAccount::factory()->create([
        'waba_id' => 'waba_test_123',
        'webhook_verify_token' => 'test_verify_token_123',
    ]);

    // Create WhatsApp Phone Number
    $this->phone = WhatsAppPhoneNumber::factory()->create([
        'whatsapp_business_account_id' => $this->waba->id,
        'phone_number_id' => 'phone_test_123',
    ]);

    // Create webhook subscription
    $this->subscription = WebhookSubscription::create([
        'tenant_id' => $this->waba->tenant_id,
        'platform' => 'whatsapp',
        'platform_account_id' => $this->waba->waba_id,
        'verify_token' => $this->waba->webhook_verify_token,
        'subscribed_fields' => ['messages', 'message_status'],
        'is_active' => true,
    ]);
});

describe('GET /api/v1/webhooks/whatsapp', function () {
    it('verifies webhook subscription with correct token', function () {
        $response = $this->getJson('/api/v1/webhooks/whatsapp?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'test_verify_token_123',
            'hub_challenge' => 'challenge_string_123',
        ]));

        $response->assertOk();
        expect($response->getContent())->toBe('challenge_string_123');
    });

    it('rejects webhook verification with incorrect token', function () {
        $response = $this->getJson('/api/v1/webhooks/whatsapp?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong_token',
            'hub_challenge' => 'challenge_string_123',
        ]));

        $response->assertForbidden();
    });

    it('rejects webhook verification with incorrect mode', function () {
        $response = $this->getJson('/api/v1/webhooks/whatsapp?' . http_build_query([
            'hub_mode' => 'invalid_mode',
            'hub_verify_token' => 'test_verify_token_123',
            'hub_challenge' => 'challenge_string_123',
        ]));

        $response->assertForbidden();
    });

    it('requires all verification parameters', function () {
        $response = $this->getJson('/api/v1/webhooks/whatsapp?' . http_build_query([
            'hub_mode' => 'subscribe',
        ]));

        $response->assertForbidden();
    });
});

describe('POST /api/v1/webhooks/whatsapp', function () {
    it('accepts webhook payload and dispatches job', function () {
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'waba_test_123',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '+1234567890',
                                    'phone_number_id' => 'phone_test_123',
                                ],
                                'messages' => [
                                    [
                                        'from' => '+0987654321',
                                        'id' => 'wamid.test123',
                                        'timestamp' => '1234567890',
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'Hello',
                                        ],
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];

        // Mock signature verification
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), config('services.whatsapp.app_secret', ''));

        $response = $this->postJson(
            '/api/v1/webhooks/whatsapp',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk()
            ->assertJson(['status' => 'ok']);

        // Verify job was dispatched
        Queue::assertPushed(ProcessWhatsAppWebhookJob::class, function ($job) use ($payload) {
            return $job->payload === $payload;
        });
    });

    it('rejects webhook with invalid signature', function () {
        // Configure app secret for signature validation
        config(['services.whatsapp.app_secret' => 'test_app_secret']);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [],
        ];

        $response = $this->postJson(
            '/api/v1/webhooks/whatsapp',
            $payload,
            ['X-Hub-Signature-256' => 'sha256=invalid_signature']
        );

        $response->assertForbidden()
            ->assertJson(['error' => 'Invalid signature']);

        // Verify job was NOT dispatched
        Queue::assertNotPushed(ProcessWhatsAppWebhookJob::class);
    });

    it('accepts webhook without signature when app_secret is not configured', function () {
        config(['services.whatsapp.app_secret' => '']);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [],
        ];

        $response = $this->postJson('/api/v1/webhooks/whatsapp', $payload);

        $response->assertOk();

        Queue::assertPushed(ProcessWhatsAppWebhookJob::class);
    });

    it('handles message status updates', function () {
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'waba_test_123',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'phone_number_id' => 'phone_test_123',
                                ],
                                'statuses' => [
                                    [
                                        'id' => 'wamid.test123',
                                        'status' => 'delivered',
                                        'timestamp' => '1234567890',
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];

        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), config('services.whatsapp.app_secret', ''));

        $response = $this->postJson(
            '/api/v1/webhooks/whatsapp',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();

        Queue::assertPushed(ProcessWhatsAppWebhookJob::class);
    });

    it('handles multiple entries in webhook payload', function () {
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'waba_test_123',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => ['phone_number_id' => 'phone_test_123'],
                                'messages' => [
                                    ['from' => '+1111111111', 'id' => 'wamid.1', 'type' => 'text'],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
                [
                    'id' => 'waba_test_456',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => ['phone_number_id' => 'phone_test_456'],
                                'messages' => [
                                    ['from' => '+2222222222', 'id' => 'wamid.2', 'type' => 'text'],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];

        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), config('services.whatsapp.app_secret', ''));

        $response = $this->postJson(
            '/api/v1/webhooks/whatsapp',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();

        Queue::assertPushed(ProcessWhatsAppWebhookJob::class);
    });

    it('returns 200 even for empty payload', function () {
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [],
        ];

        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), config('services.whatsapp.app_secret', ''));

        $response = $this->postJson(
            '/api/v1/webhooks/whatsapp',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    });
});

