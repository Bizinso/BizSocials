<?php

declare(strict_types=1);

use App\Enums\WhatsApp\WhatsAppTemplateStatus;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppBusinessAccount;
use App\Models\WhatsApp\WhatsAppPhoneNumber;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Models\Workspace\Workspace;
use App\Services\WhatsApp\WhatsAppTemplateService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->workspace = Workspace::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    
    // Add user to workspace using factory
    \App\Models\Workspace\WorkspaceMembership::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'role' => 'admin',
        'joined_at' => now(),
    ]);
    
    $this->waba = WhatsAppBusinessAccount::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->phone = WhatsAppPhoneNumber::factory()->create([
        'whatsapp_business_account_id' => $this->waba->id,
    ]);
    
    $this->actingAs($this->user);
});

describe('GET /api/v1/workspaces/{workspace}/whatsapp-templates', function () {
    it('retrieves list of templates for workspace', function () {
        WhatsAppTemplate::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'whatsapp_phone_number_id' => $this->phone->id,
        ]);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/whatsapp-templates");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'language',
                        'category',
                        'status',
                        'body_text',
                    ],
                ],
            ]);

        expect($response->json('data'))->toHaveCount(3);
    });

    it('filters templates by status', function () {
        WhatsAppTemplate::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'whatsapp_phone_number_id' => $this->phone->id,
            'status' => WhatsAppTemplateStatus::APPROVED,
        ]);

        WhatsAppTemplate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'whatsapp_phone_number_id' => $this->phone->id,
            'status' => WhatsAppTemplateStatus::DRAFT,
        ]);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/whatsapp-templates?status=approved");

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(2);
    });
});

describe('POST /api/v1/workspaces/{workspace}/whatsapp-templates/sync-all', function () {
    it('syncs templates from WhatsApp Business API', function () {
        // Mock the WhatsApp API response
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    [
                        'id' => 'template_123',
                        'name' => 'welcome_message',
                        'language' => 'en',
                        'category' => 'MARKETING',
                        'status' => 'APPROVED',
                        'components' => [
                            ['type' => 'BODY', 'text' => 'Welcome to our service!'],
                        ],
                    ],
                ],
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Inject the mock into the service
        $mockService = new \App\Services\WhatsApp\WhatsAppTemplateService($client);
        $this->app->instance(\App\Services\WhatsApp\WhatsAppTemplateService::class, $mockService);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/whatsapp-templates/sync-all", [
            'waba_id' => $this->waba->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'message',
                    'stats' => [
                        'fetched',
                        'created',
                        'updated',
                        'unchanged',
                    ],
                ],
            ]);

        expect($response->json('data.stats.fetched'))->toBe(1)
            ->and($response->json('data.stats.created'))->toBe(1);

        // Verify template was created in database
        $this->assertDatabaseHas('whatsapp_templates', [
            'workspace_id' => $this->workspace->id,
            'name' => 'welcome_message',
            'meta_template_id' => 'template_123',
        ]);
    });

    it('requires waba_id parameter', function () {
        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/whatsapp-templates/sync-all", []);

        $response->assertStatus(422);
    });
});

describe('POST /api/v1/workspaces/{workspace}/whatsapp-templates/{template}/send', function () {
    it('sends a template message', function () {
        $template = WhatsAppTemplate::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'whatsapp_phone_number_id' => $this->phone->id,
            'name' => 'test_template',
            'body_text' => 'Hello {{1}}!',
        ]);

        // Mock the WhatsApp API response
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'messages' => [
                    ['id' => 'wamid.123456'],
                ],
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Inject the mock into the service
        $mockService = new \App\Services\WhatsApp\WhatsAppMessagingService($client);
        $this->app->instance(\App\Services\WhatsApp\WhatsAppMessagingService::class, $mockService);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/whatsapp-templates/{$template->id}/send", [
            'recipient_phone' => '+1234567890',
            'parameters' => ['1' => 'John'],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'message',
                    'message_id',
                    'wamid',
                ],
            ]);

        // Verify message was created in database
        $this->assertDatabaseHas('whatsapp_messages', [
            'wamid' => 'wamid.123456',
            'type' => 'template',
        ]);

        // Verify template usage was incremented
        $template->refresh();
        expect($template->usage_count)->toBe(1);
    });

    it('validates required fields', function () {
        $template = WhatsAppTemplate::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'whatsapp_phone_number_id' => $this->phone->id,
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/whatsapp-templates/{$template->id}/send", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['recipient_phone']);
    });

    it('rejects sending non-approved templates', function () {
        $template = WhatsAppTemplate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'whatsapp_phone_number_id' => $this->phone->id,
            'status' => WhatsAppTemplateStatus::DRAFT,
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/whatsapp-templates/{$template->id}/send", [
            'recipient_phone' => '+1234567890',
        ]);

        $response->assertStatus(422);
    });
});
