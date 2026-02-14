<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Inbox\InboxItemType;
use App\Enums\WhatsApp\WhatsAppMessageDirection;
use App\Enums\WhatsApp\WhatsAppMessageStatus;
use App\Enums\WhatsApp\WhatsAppMessageType;
use App\Models\Inbox\InboxItem;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Models\WhatsApp\WhatsAppPhoneNumber;
use App\Services\BaseService;
use Illuminate\Support\Facades\Log;

final class WhatsAppWebhookService extends BaseService
{
    public function __construct(
        private readonly WhatsAppConversationService $conversationService,
        private readonly WhatsAppMessagingService $messagingService,
    ) {}

    public function processWebhook(array $payload): void
    {
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                $field = $change['field'] ?? '';

                if ($field !== 'messages') {
                    continue;
                }

                // Process inbound messages
                foreach ($value['messages'] ?? [] as $message) {
                    $metadata = $value['metadata'] ?? [];
                    $contacts = $value['contacts'] ?? [];

                    $this->processInboundMessage($message, $metadata, $contacts);
                }

                // Process status updates
                foreach ($value['statuses'] ?? [] as $status) {
                    $this->processStatusUpdate($status);
                }

                // Process errors
                foreach ($value['errors'] ?? [] as $error) {
                    $this->processError($error);
                }
            }
        }
    }

    public function processInboundMessage(array $message, array $metadata, array $contacts = []): WhatsAppMessage
    {
        $phoneNumberId = $metadata['phone_number_id'] ?? '';
        $customerPhone = $message['from'] ?? '';
        $wamid = $message['id'] ?? '';
        $timestamp = $message['timestamp'] ?? now()->timestamp;
        $type = $message['type'] ?? 'unknown';

        // Resolve contact name
        $customerName = null;
        $customerProfileName = null;
        foreach ($contacts as $contact) {
            if (($contact['wa_id'] ?? '') === $customerPhone) {
                $customerProfileName = $contact['profile']['name'] ?? null;
                $customerName = $customerProfileName;
                break;
            }
        }

        // Resolve conversation
        $conversation = $this->resolveConversation($phoneNumberId, $customerPhone, $customerName);

        // Update service window
        $conversation->update([
            'last_customer_message_at' => now(),
            'conversation_expires_at' => now()->addHours(24),
            'is_within_service_window' => true,
            'customer_profile_name' => $customerProfileName ?? $conversation->customer_profile_name,
        ]);

        // Parse message type
        $messageType = WhatsAppMessageType::tryFrom($type) ?? WhatsAppMessageType::UNKNOWN;

        // Extract content
        $contentText = null;
        $contentPayload = null;
        $mediaUrl = null;
        $mediaMimeType = null;

        switch ($type) {
            case 'text':
                $contentText = $message['text']['body'] ?? '';
                break;

            case 'image':
            case 'video':
            case 'audio':
            case 'document':
            case 'sticker':
                $mediaData = $message[$type] ?? [];
                $contentText = $mediaData['caption'] ?? null;
                $mediaMimeType = $mediaData['mime_type'] ?? null;
                $contentPayload = [
                    'media_id' => $mediaData['id'] ?? null,
                    'mime_type' => $mediaMimeType,
                    'sha256' => $mediaData['sha256'] ?? null,
                    'caption' => $contentText,
                ];
                break;

            case 'location':
                $loc = $message['location'] ?? [];
                $contentText = sprintf('Location: %s, %s', $loc['latitude'] ?? '', $loc['longitude'] ?? '');
                $contentPayload = [
                    'latitude' => $loc['latitude'] ?? null,
                    'longitude' => $loc['longitude'] ?? null,
                    'name' => $loc['name'] ?? null,
                    'address' => $loc['address'] ?? null,
                ];
                break;

            case 'contacts':
                $contentPayload = $message['contacts'] ?? [];
                $contentText = 'Contact shared';
                break;

            case 'interactive':
                $interactive = $message['interactive'] ?? [];
                $contentPayload = $interactive;
                $contentText = $interactive['body']['text'] ?? $interactive['button_reply']['title'] ?? $interactive['list_reply']['title'] ?? 'Interactive response';
                break;

            case 'reaction':
                $reaction = $message['reaction'] ?? [];
                $contentPayload = $reaction;
                $contentText = $reaction['emoji'] ?? '';
                break;

            default:
                $contentText = 'Unsupported message type: ' . $type;
                break;
        }

        // Check for opt-out
        if ($contentText !== null && $this->messagingService->isOptOutMessage($contentText)) {
            $this->messagingService->handleOptOut($customerPhone, $conversation->workspace_id);
        }

        // Create message record
        $waMessage = WhatsAppMessage::create([
            'conversation_id' => $conversation->id,
            'wamid' => $wamid,
            'direction' => WhatsAppMessageDirection::INBOUND,
            'type' => $messageType,
            'content_text' => $contentText,
            'content_payload' => $contentPayload,
            'media_url' => $mediaUrl,
            'media_mime_type' => $mediaMimeType,
            'status' => WhatsAppMessageStatus::DELIVERED,
            'platform_timestamp' => \Carbon\Carbon::createFromTimestamp((int) $timestamp),
            'status_updated_at' => now(),
        ]);

        // Update conversation counters
        $conversation->update([
            'last_message_at' => now(),
            'message_count' => $conversation->message_count + 1,
        ]);

        // Create inbox item
        $this->createInboxItem($waMessage, $conversation);

        $this->log('Inbound WhatsApp message processed', [
            'wamid' => $wamid,
            'conversation_id' => $conversation->id,
            'type' => $type,
        ]);

        return $waMessage;
    }

    public function processStatusUpdate(array $status): void
    {
        $wamid = $status['id'] ?? '';
        $statusValue = $status['status'] ?? '';
        $timestamp = $status['timestamp'] ?? now()->timestamp;

        $message = WhatsAppMessage::where('wamid', $wamid)->first();

        if ($message === null) {
            return;
        }

        $newStatus = match ($statusValue) {
            'sent' => WhatsAppMessageStatus::SENT,
            'delivered' => WhatsAppMessageStatus::DELIVERED,
            'read' => WhatsAppMessageStatus::READ,
            'failed' => WhatsAppMessageStatus::FAILED,
            default => null,
        };

        if ($newStatus === null) {
            return;
        }

        // Only advance status forward (sent → delivered → read)
        if ($message->status === WhatsAppMessageStatus::READ) {
            return;
        }

        $updateData = [
            'status' => $newStatus,
            'status_updated_at' => \Carbon\Carbon::createFromTimestamp((int) $timestamp),
        ];

        // Capture error info on failure
        if ($newStatus === WhatsAppMessageStatus::FAILED) {
            $errors = $status['errors'] ?? [];
            $firstError = $errors[0] ?? [];
            $updateData['error_code'] = (string) ($firstError['code'] ?? '');
            $updateData['error_message'] = $firstError['title'] ?? $firstError['message'] ?? 'Unknown error';
        }

        $message->update($updateData);

        Log::debug('[WhatsAppWebhook] Status updated', [
            'wamid' => $wamid,
            'status' => $statusValue,
        ]);
    }

    public function processAccountUpdate(array $update): void
    {
        $this->log('WhatsApp account update received', ['update' => $update]);
    }

    private function processError(array $error): void
    {
        Log::warning('[WhatsAppWebhook] Error received', [
            'code' => $error['code'] ?? '',
            'title' => $error['title'] ?? '',
            'message' => $error['message'] ?? '',
        ]);
    }

    private function resolveConversation(
        string $phoneNumberId,
        string $customerPhone,
        ?string $customerName,
    ): WhatsAppConversation {
        $phone = WhatsAppPhoneNumber::where('phone_number_id', $phoneNumberId)->first();

        if ($phone === null) {
            throw new \RuntimeException("Unknown phone number ID: {$phoneNumberId}");
        }

        $workspace = $phone->businessAccount->tenant->workspaces()->first();

        return $this->conversationService->findOrCreateConversation(
            $phone,
            $customerPhone,
            $customerName,
            $workspace?->id ?? '',
        );
    }

    private function createInboxItem(WhatsAppMessage $message, WhatsAppConversation $conversation): void
    {
        // Find or create a SocialAccount for the WhatsApp phone number
        $phone = $conversation->phoneNumber;
        
        $socialAccount = \App\Models\Social\SocialAccount::where([
            'workspace_id' => $conversation->workspace_id,
            'platform' => \App\Enums\Social\SocialPlatform::WHATSAPP,
            'platform_account_id' => $phone->phone_number_id,
        ])->first();

        if (!$socialAccount) {
            // Get the first admin user from the workspace, or any user from the tenant
            $workspace = \App\Models\Workspace\Workspace::find($conversation->workspace_id);
            $connectedByUser = $workspace?->members()
                ->wherePivot('role', \App\Enums\Workspace\WorkspaceRole::ADMIN)
                ->first();
            
            // Fallback to any workspace member if no admin found
            if (!$connectedByUser) {
                $connectedByUser = $workspace?->members()->first();
            }
            
            // Fallback to any tenant user if no workspace members
            if (!$connectedByUser) {
                $connectedByUser = $workspace?->tenant->users()->first();
            }
            
            // If still no user found, throw an exception
            if (!$connectedByUser) {
                throw new \RuntimeException('Cannot create SocialAccount: No user found in workspace or tenant');
            }
            
            $socialAccount = \App\Models\Social\SocialAccount::create([
                'workspace_id' => $conversation->workspace_id,
                'platform' => \App\Enums\Social\SocialPlatform::WHATSAPP,
                'platform_account_id' => $phone->phone_number_id,
                'account_name' => $phone->display_name,
                'account_username' => $phone->phone_number,
                'is_active' => true,
                'access_token_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString('whatsapp_phone_' . $phone->phone_number_id),
                'connected_by_user_id' => $connectedByUser->id,
                'connected_at' => now(),
            ]);
        }

        InboxItem::create([
            'workspace_id' => $conversation->workspace_id,
            'social_account_id' => $socialAccount->id,
            'item_type' => InboxItemType::WHATSAPP_MESSAGE,
            'status' => InboxItemStatus::UNREAD,
            'platform_item_id' => $message->wamid ?? $message->id,
            'author_name' => $conversation->customer_name ?? $conversation->customer_phone,
            'author_username' => $conversation->customer_phone,
            'content_text' => $message->content_text ?? '',
            'platform_created_at' => $message->platform_timestamp,
            'metadata' => [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'message_type' => $message->type->value,
            ],
        ]);
    }
}
