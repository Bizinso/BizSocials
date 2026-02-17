<?php

declare(strict_types=1);

use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppBusinessAccount;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppPhoneNumber;
use App\Models\Workspace\Workspace;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Mockery;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::ADMIN,
    ]);
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->workspace->addMember($this->user, WorkspaceRole::ADMIN);

    // Create WhatsApp Business Account
    $this->waba = WhatsAppBusinessAccount::factory()->create([
        'tenant_id' => $this->tenant->id,
        'waba_id' => 'waba_test_123',
        'access_token_encrypted' => encrypt('test_token'),
    ]);

    // Create WhatsApp Phone Number
    $this->phone = WhatsAppPhoneNumber::factory()->create([
        'whatsapp_business_account_id' => $this->waba->id,
        'phone_number_id' => 'phone_test_123',
        'phone_number' => '+1234567890',
    ]);

    // Create WhatsApp Conversation
    $this->conversation = WhatsAppConversation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'whatsapp_phone_number_id' => $this->phone->id,
        'customer_phone' => '+0987654321',
        'last_customer_message_at' => now(),
        'conversation_expires_at' => now()->addHours(24),
        'is_within_service_window' => true,
    ]);
});

describe('POST /api/v1/workspaces/{workspace}/conversations/{conversation}/messages', function () {
    it('sends a text message successfully', function () {
        // Mock Guzzle HTTP client
        $mockResponse = Mockery::mock(\Psr\Http\Message\ResponseInterface::class);
        $mockResponse->shouldReceive('getBody->getContents')
            ->andReturn(json_encode([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.test123']],
            ]));

        $mockGuzzle = Mockery::mock(\GuzzleHttp\Client::class);
        $mockGuzzle->shouldReceive('post')
            ->once()
            ->andReturn($mockResponse);

        // Inject the mock into the service
        $mockService = new \App\Services\WhatsApp\WhatsAppMessagingService($mockGuzzle);
        $this->app->instance(\App\Services\WhatsApp\WhatsAppMessagingService::class, $mockService);

        Sanctum::actingAs($this->user);

        $response = $this->postJson(
            "/api/v1/workspaces/{$this->workspace->id}/conversations/{$this->conversation->id}/messages",
            [
                'type' => 'text',
                'content' => 'Hello from test',
            ]
        );

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'wamid',
                    'type',
                    'content_text',
                    'direction',
                    'status',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        // Verify message was stored in database
        $this->assertDatabaseHas('whatsapp_messages', [
            'conversation_id' => $this->conversation->id,
            'content_text' => 'Hello from test',
            'direction' => 'outbound',
        ]);
    });

    it('requires authentication', function () {
        $response = $this->postJson(
            "/api/v1/workspaces/{$this->workspace->id}/conversations/{$this->conversation->id}/messages",
            [
                'message' => 'Test',
            ]
        );

        $response->assertUnauthorized();
    });

    it('validates required fields', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(
            "/api/v1/workspaces/{$this->workspace->id}/conversations/{$this->conversation->id}/messages",
            []
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    });

    it('denies access for user from different tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->postJson(
            "/api/v1/workspaces/{$this->workspace->id}/conversations/{$this->conversation->id}/messages",
            [
                'message' => 'Test',
            ]
        );

        $response->assertNotFound();
    });

    it('prevents sending outside service window', function () {
        // Update conversation to be outside service window
        $this->conversation->update([
            'last_customer_message_at' => now()->subHours(25),
            'conversation_expires_at' => now()->subHour(),
            'is_within_service_window' => false,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson(
            "/api/v1/workspaces/{$this->workspace->id}/conversations/{$this->conversation->id}/messages",
            [
                'message' => 'Test',
            ]
        );

        $response->assertStatus(422);
    });

    it('updates conversation last_message_at timestamp', function () {
        // Mock Guzzle HTTP client
        $mockResponse = Mockery::mock(\Psr\Http\Message\ResponseInterface::class);
        $mockResponse->shouldReceive('getBody->getContents')
            ->andReturn(json_encode([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.test123']],
            ]));

        $mockGuzzle = Mockery::mock(\GuzzleHttp\Client::class);
        $mockGuzzle->shouldReceive('post')
            ->once()
            ->andReturn($mockResponse);

        // Inject the mock into the service
        $mockService = new \App\Services\WhatsApp\WhatsAppMessagingService($mockGuzzle);
        $this->app->instance(\App\Services\WhatsApp\WhatsAppMessagingService::class, $mockService);

        $originalTimestamp = $this->conversation->last_message_at;

        Sanctum::actingAs($this->user);

        $this->postJson(
            "/api/v1/workspaces/{$this->workspace->id}/conversations/{$this->conversation->id}/messages",
            [
                'type' => 'text',
                'content' => 'Test',
            ]
        );

        $this->conversation->refresh();
        expect($this->conversation->last_message_at)->not->toBe($originalTimestamp);
    });
});

describe('POST /api/v1/workspaces/{workspace}/conversations/{conversation}/messages/media', function () {
    it('sends a media message successfully', function () {
        // Mock Guzzle HTTP client
        $mockResponse = Mockery::mock(\Psr\Http\Message\ResponseInterface::class);
        $mockResponse->shouldReceive('getBody->getContents')
            ->andReturn(json_encode([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.media123']],
            ]));

        $mockGuzzle = Mockery::mock(\GuzzleHttp\Client::class);
        $mockGuzzle->shouldReceive('post')
            ->once()
            ->andReturn($mockResponse);

        // Inject the mock into the service
        $mockService = new \App\Services\WhatsApp\WhatsAppMessagingService($mockGuzzle);
        $this->app->instance(\App\Services\WhatsApp\WhatsAppMessagingService::class, $mockService);

        Sanctum::actingAs($this->user);

        $response = $this->postJson(
            "/api/v1/workspaces/{$this->workspace->id}/conversations/{$this->conversation->id}/messages/media",
            [
                'type' => 'image',
                'media_url' => 'https://example.com/image.jpg',
                'caption' => 'Check this out',
            ]
        );

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'wamid',
                    'type',
                    'media_url',
                ],
            ]);

        // Verify message was stored in database
        $this->assertDatabaseHas('whatsapp_messages', [
            'conversation_id' => $this->conversation->id,
            'type' => 'image',
            'media_url' => 'https://example.com/image.jpg',
        ]);
    });

    it('validates required fields for media messages', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(
            "/api/v1/workspaces/{$this->workspace->id}/conversations/{$this->conversation->id}/messages/media",
            []
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    });

    it('validates media_url when type is image', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(
            "/api/v1/workspaces/{$this->workspace->id}/conversations/{$this->conversation->id}/messages/media",
            [
                'type' => 'image',
                // missing media_url
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['media_url']);
    });
});

describe('GET /api/v1/workspaces/{workspace}/conversations/{conversation}/messages', function () {
    it('retrieves messages for a conversation', function () {
        // Create some messages
        \App\Models\WhatsApp\WhatsAppMessage::factory()->count(5)->create([
            'conversation_id' => $this->conversation->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(
            "/api/v1/workspaces/{$this->workspace->id}/conversations/{$this->conversation->id}/messages"
        );

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'wamid',
                        'type',
                        'content_text',
                        'direction',
                        'status',
                        'platform_timestamp',
                    ],
                ],
            ]);

        expect($response->json('data'))->toHaveCount(5);
    });

    it('paginates messages', function () {
        \App\Models\WhatsApp\WhatsAppMessage::factory()->count(30)->create([
            'conversation_id' => $this->conversation->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(
            "/api/v1/workspaces/{$this->workspace->id}/conversations/{$this->conversation->id}/messages?per_page=10"
        );

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(10);
    });

    it('requires authentication', function () {
        $response = $this->getJson(
            "/api/v1/workspaces/{$this->workspace->id}/conversations/{$this->conversation->id}/messages"
        );

        $response->assertUnauthorized();
    });

    it('orders messages by timestamp descending', function () {
        $message1 = \App\Models\WhatsApp\WhatsAppMessage::factory()->create([
            'conversation_id' => $this->conversation->id,
            'platform_timestamp' => now()->subHours(2),
        ]);

        $message2 = \App\Models\WhatsApp\WhatsAppMessage::factory()->create([
            'conversation_id' => $this->conversation->id,
            'platform_timestamp' => now()->subHour(),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(
            "/api/v1/workspaces/{$this->workspace->id}/conversations/{$this->conversation->id}/messages"
        );

        $response->assertOk();
        $messages = $response->json('data');
        
        // Most recent message should be first
        expect($messages[0]['id'])->toBe($message2->id);
        expect($messages[1]['id'])->toBe($message1->id);
    });
});

