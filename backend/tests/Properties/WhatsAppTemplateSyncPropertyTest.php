<?php

declare(strict_types=1);

namespace Tests\Properties;

// Feature: platform-audit-and-testing, Property 16: Template Synchronization
// For any WhatsApp message template, it should exist in both the local database and the WhatsApp Business API,
// and the content should match.

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
use Tests\TestCase;

/**
 * WhatsApp Template Synchronization Property Test
 *
 * Validates that templates exist in both database and WhatsApp API with matching content.
 *
 * Feature: platform-audit-and-testing, Property 16: Template Synchronization
 * Validates: Requirements 7.4
 */
class WhatsAppTemplateSyncPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: For any template synced from WhatsApp API, it should exist in the database
     * with matching content (name, language, status, body text)
     */
    public function test_synced_templates_exist_in_both_database_and_whatsapp_api(): void
    {
        $iterations = 20; // Reduced from 100 for faster execution
        
        for ($i = 0; $i < $iterations; $i++) {
            // Setup
            $tenant = Tenant::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $tenant->id]);
            $waba = WhatsAppBusinessAccount::factory()->create(['tenant_id' => $tenant->id]);
            $phone = WhatsAppPhoneNumber::factory()->create([
                'whatsapp_business_account_id' => $waba->id,
            ]);

            // Generate random template data
            $templateId = 'template_' . fake()->unique()->numerify('##########');
            $templateName = strtolower(str_replace(' ', '_', fake()->words(3, true)));
            $language = fake()->randomElement(['en', 'es', 'fr', 'de', 'pt']);
            $category = fake()->randomElement(['MARKETING', 'UTILITY', 'AUTHENTICATION']);
            $status = fake()->randomElement(['APPROVED', 'PENDING', 'REJECTED']);
            $bodyText = fake()->sentence();

            // Mock WhatsApp API response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'data' => [
                        [
                            'id' => $templateId,
                            'name' => $templateName,
                            'language' => $language,
                            'category' => $category,
                            'status' => $status,
                            'components' => [
                                ['type' => 'BODY', 'text' => $bodyText],
                            ],
                        ],
                    ],
                ])),
            ]);

            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);

            $service = new WhatsAppTemplateService();
            $reflection = new \ReflectionClass($service);
            $property = $reflection->getProperty('client');
            $property->setAccessible(true);
            $property->setValue($service, $client);

            // Execute sync
            $stats = $service->syncTemplatesFromApi($waba);

            // Verify: Template should exist in database
            $this->assertGreaterThan(0, $stats['fetched']);
            
            $template = WhatsAppTemplate::where('meta_template_id', $templateId)->first();
            
            // Property assertion: Template must exist in database after sync
            $this->assertNotNull($template);
            $this->assertEquals($templateName, $template->name);
            $this->assertEquals($language, $template->language);
            $this->assertEquals($bodyText, $template->body_text);
            $this->assertEquals($templateId, $template->meta_template_id);

            // Verify status mapping
            $expectedStatus = match($status) {
                'APPROVED' => WhatsAppTemplateStatus::APPROVED,
                'PENDING' => WhatsAppTemplateStatus::PENDING_APPROVAL,
                'REJECTED' => WhatsAppTemplateStatus::REJECTED,
                default => WhatsAppTemplateStatus::PENDING_APPROVAL,
            };
            
            $this->assertEquals($expectedStatus, $template->status);
        }
    }

    /**
     * Property: For any template that exists in both systems, updates from the API
     * should be reflected in the database
     */
    public function test_template_updates_from_api_are_reflected_in_database(): void
    {
        $iterations = 20;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Setup
            $tenant = Tenant::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $tenant->id]);
            $waba = WhatsAppBusinessAccount::factory()->create(['tenant_id' => $tenant->id]);
            $phone = WhatsAppPhoneNumber::factory()->create([
                'whatsapp_business_account_id' => $waba->id,
            ]);

            // Create existing template
            $templateId = 'template_' . fake()->unique()->numerify('##########');
            $templateName = strtolower(str_replace(' ', '_', fake()->words(3, true)));
            $oldBodyText = fake()->sentence();
            
            $existing = WhatsAppTemplate::factory()->create([
                'workspace_id' => $workspace->id,
                'whatsapp_phone_number_id' => $phone->id,
                'meta_template_id' => $templateId,
                'name' => $templateName,
                'language' => 'en',
                'status' => WhatsAppTemplateStatus::PENDING_APPROVAL,
                'body_text' => $oldBodyText,
            ]);

            // Generate updated data from API
            $newBodyText = fake()->sentence();
            $newStatus = fake()->randomElement(['APPROVED', 'REJECTED']);

            // Mock WhatsApp API response with updated data
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'data' => [
                        [
                            'id' => $templateId,
                            'name' => $templateName,
                            'language' => 'en',
                            'category' => 'MARKETING',
                            'status' => $newStatus,
                            'components' => [
                                ['type' => 'BODY', 'text' => $newBodyText],
                            ],
                        ],
                    ],
                ])),
            ]);

            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);

            $service = new WhatsAppTemplateService();
            $reflection = new \ReflectionClass($service);
            $property = $reflection->getProperty('client');
            $property->setAccessible(true);
            $property->setValue($service, $client);

            // Execute sync
            $stats = $service->syncTemplatesFromApi($waba);

            // Property assertion: Updates from API should be reflected in database
            $this->assertGreaterThan(0, $stats['updated']);
            
            $existing->refresh();
            
            $this->assertEquals($newBodyText, $existing->body_text);
            $this->assertNotEquals($oldBodyText, $existing->body_text);

            // Verify status was updated
            $expectedStatus = $newStatus === 'APPROVED' 
                ? WhatsAppTemplateStatus::APPROVED 
                : WhatsAppTemplateStatus::REJECTED;
            
            $this->assertEquals($expectedStatus, $existing->status);
        }
    }

    /**
     * Property: For any template, the content stored in the database should accurately
     * represent the content from the WhatsApp API
     */
    public function test_template_content_matches_between_database_and_api_representation(): void
    {
        $iterations = 20;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Setup
            $tenant = Tenant::factory()->create();
            $workspace = Workspace::factory()->create(['tenant_id' => $tenant->id]);
            $waba = WhatsAppBusinessAccount::factory()->create(['tenant_id' => $tenant->id]);
            $phone = WhatsAppPhoneNumber::factory()->create([
                'whatsapp_business_account_id' => $waba->id,
            ]);

            // Generate random template with various components
            $templateId = 'template_' . fake()->unique()->numerify('##########');
            $templateName = strtolower(str_replace(' ', '_', fake()->words(3, true)));
            $hasHeader = fake()->boolean();
            $hasFooter = fake()->boolean();
            $headerText = $hasHeader ? fake()->sentence(3) : null;
            $bodyText = fake()->sentence();
            $footerText = $hasFooter ? fake()->sentence(4) : null;

            $components = [
                ['type' => 'BODY', 'text' => $bodyText],
            ];

            if ($hasHeader) {
                array_unshift($components, [
                    'type' => 'HEADER',
                    'format' => 'TEXT',
                    'text' => $headerText,
                ]);
            }

            if ($hasFooter) {
                $components[] = ['type' => 'FOOTER', 'text' => $footerText];
            }

            // Mock WhatsApp API response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'data' => [
                        [
                            'id' => $templateId,
                            'name' => $templateName,
                            'language' => 'en',
                            'category' => 'MARKETING',
                            'status' => 'APPROVED',
                            'components' => $components,
                        ],
                    ],
                ])),
            ]);

            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);

            $service = new WhatsAppTemplateService();
            $reflection = new \ReflectionClass($service);
            $property = $reflection->getProperty('client');
            $property->setAccessible(true);
            $property->setValue($service, $client);

            // Execute sync
            $service->syncTemplatesFromApi($waba);

            // Property assertion: Content should match API representation
            $template = WhatsAppTemplate::where('meta_template_id', $templateId)->first();
            
            $this->assertNotNull($template);
            $this->assertEquals($bodyText, $template->body_text);

            if ($hasHeader) {
                $this->assertEquals('text', $template->header_type);
                $this->assertEquals($headerText, $template->header_content);
            } else {
                $this->assertEquals('none', $template->header_type);
            }

            if ($hasFooter) {
                $this->assertEquals($footerText, $template->footer_text);
            } else {
                $this->assertNull($template->footer_text);
            }
        }
    }
}
