<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Data\WhatsApp\WhatsAppMessageData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\WhatsApp\SendWhatsAppMessageRequest;
use App\Enums\WhatsApp\WhatsAppMessageType;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\Workspace\Workspace;
use App\Services\WhatsApp\WhatsAppMessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WhatsAppMessageController extends Controller
{
    public function __construct(
        private readonly WhatsAppMessagingService $messagingService,
    ) {}

    public function index(Request $request, Workspace $workspace, WhatsAppConversation $conversation): JsonResponse
    {
        $messages = $conversation->messages()
            ->with('sentByUser')
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', '50'));

        return $this->paginated($messages, WhatsAppMessageData::class);
    }

    public function send(SendWhatsAppMessageRequest $request, Workspace $workspace, WhatsAppConversation $conversation): JsonResponse
    {
        $phone = $conversation->phoneNumber;
        $validated = $request->validated();
        $userId = $request->user()->id;

        $message = match ($validated['type']) {
            'text' => $this->messagingService->sendTextMessage(
                $phone,
                $conversation,
                $validated['content'],
                $userId,
            ),
            'template' => $this->messagingService->sendTemplateMessage(
                $phone,
                $conversation->customer_phone,
                $validated['template_name'] ?? '',
                $validated['template_language'] ?? 'en',
                $validated['template_components'] ?? [],
                $userId,
            ),
            default => $this->messagingService->sendMediaMessage(
                $phone,
                $conversation,
                $validated['media_url'],
                WhatsAppMessageType::from($validated['type']),
                $validated['caption'] ?? null,
                $userId,
            ),
        };

        return $this->created(
            WhatsAppMessageData::fromModel($message),
            'Message sent',
        );
    }

    public function sendMedia(SendWhatsAppMessageRequest $request, Workspace $workspace, WhatsAppConversation $conversation): JsonResponse
    {
        return $this->send($request, $workspace, $conversation);
    }
}
