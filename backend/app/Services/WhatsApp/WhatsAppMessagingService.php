<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\WhatsApp\WhatsAppMessageDirection;
use App\Enums\WhatsApp\WhatsAppMessageStatus;
use App\Enums\WhatsApp\WhatsAppMessageType;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Models\WhatsApp\WhatsAppOptIn;
use App\Models\WhatsApp\WhatsAppPhoneNumber;
use App\Services\BaseService;
use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;

final class WhatsAppMessagingService extends BaseService
{
    private const API_BASE = 'https://graph.facebook.com/v19.0/';

    private Client $client;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => self::API_BASE, 'timeout' => 30]);
    }

    public function sendTextMessage(
        WhatsAppPhoneNumber $phone,
        WhatsAppConversation $conversation,
        string $text,
        ?string $sentByUserId = null,
    ): WhatsAppMessage {
        $this->enforceServiceWindow($conversation);
        $this->checkDailyLimit($phone);

        $waba = $phone->businessAccount;

        $response = $this->client->post($phone->phone_number_id . '/messages', [
            'headers' => ['Authorization' => 'Bearer ' . $waba->getDecryptedToken()],
            'json' => [
                'messaging_product' => 'whatsapp',
                'to' => $conversation->customer_phone,
                'type' => 'text',
                'text' => ['body' => $text],
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
        $wamid = $result['messages'][0]['id'] ?? null;

        $message = $this->createOutboundMessage($conversation, [
            'wamid' => $wamid,
            'type' => WhatsAppMessageType::TEXT,
            'content_text' => $text,
            'sent_by_user_id' => $sentByUserId,
            'status' => WhatsAppMessageStatus::SENT,
        ]);

        $phone->incrementDailySendCount();

        return $message;
    }

    public function sendMediaMessage(
        WhatsAppPhoneNumber $phone,
        WhatsAppConversation $conversation,
        string $mediaUrl,
        WhatsAppMessageType $type,
        ?string $caption = null,
        ?string $sentByUserId = null,
    ): WhatsAppMessage {
        $this->enforceServiceWindow($conversation);
        $this->checkDailyLimit($phone);

        $waba = $phone->businessAccount;
        $mediaType = $type->value;

        $mediaPayload = ['link' => $mediaUrl];
        if ($caption !== null) {
            $mediaPayload['caption'] = $caption;
        }

        $response = $this->client->post($phone->phone_number_id . '/messages', [
            'headers' => ['Authorization' => 'Bearer ' . $waba->getDecryptedToken()],
            'json' => [
                'messaging_product' => 'whatsapp',
                'to' => $conversation->customer_phone,
                'type' => $mediaType,
                $mediaType => $mediaPayload,
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
        $wamid = $result['messages'][0]['id'] ?? null;

        $message = $this->createOutboundMessage($conversation, [
            'wamid' => $wamid,
            'type' => $type,
            'content_text' => $caption,
            'media_url' => $mediaUrl,
            'content_payload' => ['url' => $mediaUrl, 'caption' => $caption],
            'sent_by_user_id' => $sentByUserId,
            'status' => WhatsAppMessageStatus::SENT,
        ]);

        $phone->incrementDailySendCount();

        return $message;
    }

    public function sendTemplateMessage(
        WhatsAppPhoneNumber $phone,
        string $recipientPhone,
        string $templateName,
        string $language,
        array $components = [],
        ?string $sentByUserId = null,
    ): WhatsAppMessage {
        $this->checkDailyLimit($phone);

        $waba = $phone->businessAccount;

        $response = $this->client->post($phone->phone_number_id . '/messages', [
            'headers' => ['Authorization' => 'Bearer ' . $waba->getDecryptedToken()],
            'json' => [
                'messaging_product' => 'whatsapp',
                'to' => $recipientPhone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => $language],
                    'components' => $components,
                ],
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
        $wamid = $result['messages'][0]['id'] ?? null;

        $conversationService = app(WhatsAppConversationService::class);
        $workspace = $waba->tenant->workspaces()->first();
        $conversation = $conversationService->findOrCreateConversation(
            $phone,
            $recipientPhone,
            null,
            $workspace?->id ?? '',
        );

        $message = $this->createOutboundMessage($conversation, [
            'wamid' => $wamid,
            'type' => WhatsAppMessageType::TEMPLATE,
            'content_text' => "Template: {$templateName}",
            'content_payload' => ['template_name' => $templateName, 'language' => $language, 'components' => $components],
            'sent_by_user_id' => $sentByUserId,
            'status' => WhatsAppMessageStatus::SENT,
        ]);

        $phone->incrementDailySendCount();

        return $message;
    }

    public function markAsRead(WhatsAppPhoneNumber $phone, string $wamid): void
    {
        $waba = $phone->businessAccount;

        $this->client->post($phone->phone_number_id . '/messages', [
            'headers' => ['Authorization' => 'Bearer ' . $waba->getDecryptedToken()],
            'json' => [
                'messaging_product' => 'whatsapp',
                'status' => 'read',
                'message_id' => $wamid,
            ],
        ]);
    }

    public function uploadMedia(WhatsAppPhoneNumber $phone, UploadedFile $file): string
    {
        $waba = $phone->businessAccount;

        $response = $this->client->post($phone->phone_number_id . '/media', [
            'headers' => ['Authorization' => 'Bearer ' . $waba->getDecryptedToken()],
            'multipart' => [
                ['name' => 'file', 'contents' => fopen($file->getPathname(), 'r'), 'filename' => $file->getClientOriginalName()],
                ['name' => 'type', 'contents' => $file->getMimeType()],
                ['name' => 'messaging_product', 'contents' => 'whatsapp'],
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        return $result['id'] ?? '';
    }

    public function handleOptOut(string $customerPhone, string $workspaceId): void
    {
        $optIn = WhatsAppOptIn::where('workspace_id', $workspaceId)
            ->where('phone_number', $customerPhone)
            ->where('is_active', true)
            ->first();

        if ($optIn !== null) {
            $optIn->optOut();
            $this->log('Customer opted out', ['phone' => $customerPhone, 'workspace' => $workspaceId]);
        }
    }

    public function isOptOutMessage(string $text): bool
    {
        $keywords = ['stop', 'unsubscribe', 'opt out', 'cancel', 'quit'];

        return in_array(strtolower(trim($text)), $keywords, true);
    }

    private function enforceServiceWindow(WhatsAppConversation $conversation): void
    {
        if (!$conversation->isWithinServiceWindow()) {
            throw new \RuntimeException(
                'Cannot send free-form message outside the 24-hour service window. Use a template message instead.'
            );
        }
    }

    private function checkDailyLimit(WhatsAppPhoneNumber $phone): void
    {
        if ($phone->hasReachedDailyLimit()) {
            throw new \RuntimeException('Daily send limit reached for this phone number.');
        }
    }

    private function createOutboundMessage(WhatsAppConversation $conversation, array $data): WhatsAppMessage
    {
        $message = WhatsAppMessage::create(array_merge($data, [
            'conversation_id' => $conversation->id,
            'direction' => WhatsAppMessageDirection::OUTBOUND,
            'platform_timestamp' => now(),
            'status_updated_at' => now(),
        ]));

        $conversation->update([
            'last_message_at' => now(),
            'message_count' => $conversation->message_count + 1,
        ]);

        return $message;
    }
}
