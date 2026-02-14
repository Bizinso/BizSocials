<?php

declare(strict_types=1);

use App\Enums\WhatsApp\WhatsAppTemplateCategory;
use App\Enums\WhatsApp\WhatsAppTemplateStatus;
use App\Models\Tenant\Tenant;
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
    $this->waba = WhatsAppBusinessAccount::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->phone = WhatsAppPhoneNumber::factory()->create([
        'whatsapp_business_account_id' => $this->waba->id,
    ]);
});

describe('WhatsAppTemplateService::syncTemplatesFromApi', function () {
    it('syncs templates from WhatsApp API to database', function () {
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
                            ['type' => 'BODY', 'text' => 'Welcome {{1}}!'],
                        ],
                    ],
                    [
                        'id' => 'template_456',
                        'name' => 'order_confirmation',
                        'language' => 'en',
                        'category' => 'UTILITY',
                        'status' => 'PENDING',
                        'components' => [
                            ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Order Update'],
                            ['type' => 'BODY', 'text' => 'Your order {{1}} is confirmed.'],
                            ['type' => 'FOOTER', 'text' => 'Thank you!'],
                        ],
                    ],
                ],
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $service = new WhatsAppTemplateService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($service, $client);

        $stats = $service->syncTemplatesFromApi($this->waba);

        expect($stats['fetched'])->toBe(2)
            ->and($stats['created'])->toBe(2)
            ->and($stats['updated'])->toBe(0)
            ->and($stats['unchanged'])->toBe(0);

        $templates = WhatsAppTemplate::all();
        expect($templates)->toHaveCount(2);

        $template1 = $templates->firstWhere('name', 'welcome_message');
        expect($template1->meta_template_id)->toBe('template_123')
            ->and($template1->status)->toBe(WhatsAppTemplateStatus::APPROVED)
            ->and($template1->body_text)->toBe('Welcome {{1}}!')
            ->and($template1->approved_at)->not->toBeNull();

        $template2 = $templates->firstWhere('name', 'order_confirmation');
        expect($template2->meta_template_id)->toBe('template_456')
            ->and($template2->status)->toBe(WhatsAppTemplateStatus::PENDING_APPROVAL)
            ->and($template2->header_type)->toBe('text')
            ->and($template2->header_content)->toBe('Order Update')
            ->and($template2->body_text)->toBe('Your order {{1}} is confirmed.')
            ->and($template2->footer_text)->toBe('Thank you!');
    });

    it('updates existing templates when syncing', function () {
        $existing = WhatsAppTemplate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'whatsapp_phone_number_id' => $this->phone->id,
            'meta_template_id' => 'template_123',
            'name' => 'welcome_message',
            'language' => 'en',
            'status' => WhatsAppTemplateStatus::PENDING_APPROVAL,
            'body_text' => 'Old text',
        ]);

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
                            ['type' => 'BODY', 'text' => 'Welcome {{1}}! New version'],
                        ],
                    ],
                ],
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $service = new WhatsAppTemplateService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($service, $client);

        $stats = $service->syncTemplatesFromApi($this->waba);

        expect($stats['fetched'])->toBe(1)
            ->and($stats['created'])->toBe(0)
            ->and($stats['updated'])->toBe(1)
            ->and($stats['unchanged'])->toBe(0);

        $existing->refresh();
        expect($existing->status)->toBe(WhatsAppTemplateStatus::APPROVED)
            ->and($existing->body_text)->toBe('Welcome {{1}}! New version')
            ->and($existing->approved_at)->not->toBeNull();
    });

    it('marks templates as unchanged when no changes detected', function () {
        WhatsAppTemplate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'whatsapp_phone_number_id' => $this->phone->id,
            'meta_template_id' => 'template_123',
            'name' => 'welcome_message',
            'language' => 'en',
            'status' => WhatsAppTemplateStatus::APPROVED,
            'body_text' => 'Welcome {{1}}!',
            'category' => WhatsAppTemplateCategory::MARKETING,
            'header_type' => 'none',
            'approved_at' => now()->subDays(7),
        ]);

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
                            ['type' => 'BODY', 'text' => 'Welcome {{1}}!'],
                        ],
                    ],
                ],
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $service = new WhatsAppTemplateService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($service, $client);

        $stats = $service->syncTemplatesFromApi($this->waba);

        expect($stats['fetched'])->toBe(1)
            ->and($stats['created'])->toBe(0)
            ->and($stats['updated'])->toBe(0)
            ->and($stats['unchanged'])->toBe(1);
    });
});

describe('WhatsAppTemplateService::buildSendComponents', function () {
    it('builds components with body parameters', function () {
        $template = WhatsAppTemplate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'whatsapp_phone_number_id' => $this->phone->id,
            'body_text' => 'Hello {{1}}, your order {{2}} is ready!',
            'header_type' => 'none',
        ]);

        $service = new WhatsAppTemplateService();
        $components = $service->buildSendComponents($template, [
            '1' => 'John',
            '2' => '#12345',
        ]);

        expect($components)->toHaveCount(1)
            ->and($components[0]['type'])->toBe('body')
            ->and($components[0]['parameters'])->toHaveCount(2)
            ->and($components[0]['parameters'][0])->toBe(['type' => 'text', 'text' => 'John'])
            ->and($components[0]['parameters'][1])->toBe(['type' => 'text', 'text' => '#12345']);
    });

    it('builds components with header and body parameters', function () {
        $template = WhatsAppTemplate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'whatsapp_phone_number_id' => $this->phone->id,
            'header_type' => 'text',
            'header_content' => 'Welcome {{1}}',
            'body_text' => 'Your account {{1}} is active.',
        ]);

        $service = new WhatsAppTemplateService();
        $components = $service->buildSendComponents($template, [
            '1' => 'Alice',
        ]);

        expect($components)->toHaveCount(2)
            ->and($components[0]['type'])->toBe('header')
            ->and($components[0]['parameters'][0]['text'])->toBe('Alice')
            ->and($components[1]['type'])->toBe('body')
            ->and($components[1]['parameters'][0]['text'])->toBe('Alice');
    });

    it('builds components with media header', function () {
        $template = WhatsAppTemplate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'whatsapp_phone_number_id' => $this->phone->id,
            'header_type' => 'image',
            'body_text' => 'Check out this image!',
        ]);

        $service = new WhatsAppTemplateService();
        $components = $service->buildSendComponents($template, [
            'header_media' => 'https://example.com/image.jpg',
        ]);

        expect($components)->toHaveCount(1)
            ->and($components[0]['type'])->toBe('header')
            ->and($components[0]['parameters'][0]['type'])->toBe('image')
            ->and($components[0]['parameters'][0]['image']['link'])->toBe('https://example.com/image.jpg');
    });

    it('returns empty array when no parameters needed', function () {
        $template = WhatsAppTemplate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'whatsapp_phone_number_id' => $this->phone->id,
            'body_text' => 'Static message with no variables',
            'header_type' => 'none',
        ]);

        $service = new WhatsAppTemplateService();
        $components = $service->buildSendComponents($template, []);

        expect($components)->toBeEmpty();
    });
});

describe('WhatsAppTemplateService::sendTemplate', function () {
    it('throws exception when template is not approved', function () {
        $template = WhatsAppTemplate::factory()->create([
            'workspace_id' => $this->workspace->id,
            'whatsapp_phone_number_id' => $this->phone->id,
            'status' => WhatsAppTemplateStatus::DRAFT,
        ]);

        $service = new WhatsAppTemplateService();

        expect(fn() => $service->sendTemplate($template, '+1234567890', []))
            ->toThrow(RuntimeException::class, 'Template cannot be sent');
    });
});
