<?php

declare(strict_types=1);

namespace Tests\Properties;

use App\Enums\WhatsApp\WhatsAppMessageDirection;
use App\Enums\WhatsApp\WhatsAppMessageStatus;
use App\Models\WhatsApp\WhatsAppBusinessAccount;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Models\WhatsApp\WhatsAppPhoneNumber;
use App\Models\Workspace\Workspace;
use App\Services\WhatsApp\WhatsAppConversationService;
use App\Services\WhatsApp\WhatsAppMessagingService;
use App\Services\WhatsApp\WhatsAppWebhookService;
use Eris\Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\PropertyTestTrait;
use Tests\TestCase;

/**
 * WhatsApp Webhook Processing Property Test
 *
 * Validates that WhatsApp webhook processing updates the database correctly.
 *
 * Feature: platform-audit-and-testing, Property 21: Webhook Processing
 * Validates: Requirements 7.3
 */
class WhatsAppWebhookPropertyTest extends TestCase
{
    use RefreshDatabase;
    use PropertyTestTrait;

    /**
     * Property 21: Webhook Processing
     *
     * For any valid webhook payload from WhatsApp, the webhook handler should process it,
     * update the relevant message/conversation status in the database, and return a success response.
     *
     * This test verifies that:
     * 1. Webhook payloads are processed without errors
     * 2. Database records are created or updated
     * 3. Message status updates are persisted
     * 4. Conversation metadata is updated
     *
     * Feature: platform-audit-and-testing, Property 21: Webhook Processing
     * Validates: Requirements 7.3
     */
    public function test_webhook_processing_updates_database(): void
    {
        $this->forAll(
            Generator\elements('text', 'image', 'video', 'location'),
            Generator\choose(1000000000, 9999999999)
        )
            ->then(function (string $messageType, int $phoneNumber) {
                $messageContent = 'Test message ' . uniqid();
                
                // Set up test data
                $workspace = Workspace::factory()->create();
                $tenant = $workspace->tenant;
                
                // Create a user in the tenant to satisfy foreign key constraint
                \App\Models\User::factory()->create([
                    'tenant_id' => $tenant->id,
                ]);
                
                $waba = WhatsAppBusinessAccount::factory()->create([
                    'tenant_id' => $tenant->id,
                ]);
                $phone = WhatsAppPhoneNumber::factory()->create([
                    'whatsapp_business_account_id' => $waba->id,
                    'phone_number_id' => 'phone_test_' . uniqid(),
                ]);
                
                // Create services
                $conversationService = app(WhatsAppConversationService::class);
                $messagingService = app(WhatsAppMessagingService::class);
                $webhookService = new WhatsAppWebhookService($conversationService, $messagingService);
                
                // Generate webhook payload
                $wamid = 'wamid.test_' . uniqid();
                $customerPhone = '+1' . $phoneNumber;
                $timestamp = now()->timestamp;
                
                $messageData = $this->generateMessageData(
                    $messageType,
                    $wamid,
                    $customerPhone,
                    $timestamp,
                    $messageContent
                );
                
                $metadata = [
                    'phone_number_id' => $phone->phone_number_id,
                ];
                
                $contacts = [
                    [
                        'wa_id' => $customerPhone,
                        'profile' => ['name' => 'Test Customer ' . uniqid()],
                    ],
                ];
                
                // Count messages before processing
                $messageCountBefore = WhatsAppMessage::count();
                $conversationCountBefore = WhatsAppConversation::count();
                
                // Process the webhook
                $message = $webhookService->processInboundMessage($messageData, $metadata, $contacts);
                
                // Verify database was updated
                $this->assertInstanceOf(
                    WhatsAppMessage::class,
                    $message,
                    'Expected processInboundMessage to return a WhatsAppMessage instance'
                );
                
                // Verify message was created in database
                $messageCountAfter = WhatsAppMessage::count();
                $this->assertGreaterThan(
                    $messageCountBefore,
                    $messageCountAfter,
                    'Expected a new message to be created in the database'
                );
                
                // Verify conversation was created or updated
                $conversationCountAfter = WhatsAppConversation::count();
                $this->assertGreaterThanOrEqual(
                    $conversationCountBefore,
                    $conversationCountAfter,
                    'Expected conversation count to stay same or increase'
                );
                
                // Verify message properties
                $this->assertDatabaseHas('whatsapp_messages', [
                    'wamid' => $wamid,
                    'direction' => WhatsAppMessageDirection::INBOUND->value,
                    'status' => WhatsAppMessageStatus::DELIVERED->value,
                ]);
                
                // Verify conversation was updated with service window
                $conversation = $message->conversation;
                $this->assertNotNull(
                    $conversation->last_customer_message_at,
                    'Expected conversation last_customer_message_at to be set'
                );
                
                $this->assertTrue(
                    $conversation->is_within_service_window,
                    'Expected conversation to be within service window after receiving message'
                );
                
                // Verify message count was incremented
                $this->assertGreaterThan(
                    0,
                    $conversation->message_count,
                    'Expected conversation message_count to be greater than 0'
                );
            });
    }

    /**
     * Property 21: Webhook Status Update Processing
     *
     * For any status update webhook from WhatsApp, the webhook handler should update
     * the message status in the database.
     *
     * Feature: platform-audit-and-testing, Property 21: Webhook Processing
     * Validates: Requirements 7.3
     */
    public function test_webhook_status_updates_persist_to_database(): void
    {
        $this->forAll(
            Generator\elements('sent', 'delivered', 'read')
        )
            ->then(function (string $statusValue) {
                // Set up test data
                $workspace = Workspace::factory()->create();
                $tenant = $workspace->tenant;
                $waba = WhatsAppBusinessAccount::factory()->create([
                    'tenant_id' => $tenant->id,
                ]);
                $phone = WhatsAppPhoneNumber::factory()->create([
                    'whatsapp_business_account_id' => $waba->id,
                ]);
                $conversation = WhatsAppConversation::factory()->create([
                    'workspace_id' => $workspace->id,
                    'whatsapp_phone_number_id' => $phone->id,
                ]);
                
                $wamid = 'wamid.status_' . uniqid();
                $message = WhatsAppMessage::factory()->create([
                    'conversation_id' => $conversation->id,
                    'wamid' => $wamid,
                    'direction' => WhatsAppMessageDirection::OUTBOUND,
                    'status' => WhatsAppMessageStatus::SENT,
                ]);
                
                // Create services
                $conversationService = app(WhatsAppConversationService::class);
                $messagingService = app(WhatsAppMessagingService::class);
                $webhookService = new WhatsAppWebhookService($conversationService, $messagingService);
                
                // Generate status update payload
                $statusData = [
                    'id' => $wamid,
                    'status' => $statusValue,
                    'timestamp' => now()->timestamp,
                ];
                
                // Get initial status
                $initialStatus = $message->status;
                
                // Process the status update
                $webhookService->processStatusUpdate($statusData);
                
                // Refresh the message from database
                $message->refresh();
                
                // Verify status was updated in database
                $expectedStatus = match ($statusValue) {
                    'sent' => WhatsAppMessageStatus::SENT,
                    'delivered' => WhatsAppMessageStatus::DELIVERED,
                    'read' => WhatsAppMessageStatus::READ,
                };
                
                // Only verify if status should have changed (not downgrading from READ)
                if ($initialStatus !== WhatsAppMessageStatus::READ) {
                    $this->assertEquals(
                        $expectedStatus,
                        $message->status,
                        "Expected message status to be updated to {$expectedStatus->value}"
                    );
                    
                    $this->assertNotNull(
                        $message->status_updated_at,
                        'Expected status_updated_at to be set'
                    );
                }
            });
    }

    /**
     * Generate message data based on message type.
     */
    private function generateMessageData(
        string $messageType,
        string $wamid,
        string $customerPhone,
        int $timestamp,
        string $content
    ): array {
        $baseData = [
            'from' => $customerPhone,
            'id' => $wamid,
            'timestamp' => $timestamp,
            'type' => $messageType,
        ];
        
        return match ($messageType) {
            'text' => array_merge($baseData, [
                'text' => ['body' => $content],
            ]),
            'image' => array_merge($baseData, [
                'image' => [
                    'id' => 'media_' . uniqid(),
                    'mime_type' => 'image/jpeg',
                    'sha256' => hash('sha256', $content),
                    'caption' => $content,
                ],
            ]),
            'video' => array_merge($baseData, [
                'video' => [
                    'id' => 'media_' . uniqid(),
                    'mime_type' => 'video/mp4',
                    'sha256' => hash('sha256', $content),
                ],
            ]),
            'location' => array_merge($baseData, [
                'location' => [
                    'latitude' => rand(-90, 90) + (rand(0, 999999) / 1000000),
                    'longitude' => rand(-180, 180) + (rand(0, 999999) / 1000000),
                    'name' => $content,
                    'address' => 'Test Address',
                ],
            ]),
            default => $baseData,
        };
    }
}
