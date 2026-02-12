<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Data\WhatsApp\WhatsAppConversationData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\WhatsApp\AssignConversationRequest;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\Workspace\Workspace;
use App\Services\WhatsApp\WhatsAppConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WhatsAppConversationController extends Controller
{
    public function __construct(
        private readonly WhatsAppConversationService $conversationService,
    ) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $conversations = $this->conversationService->listForWorkspace(
            $workspace->id,
            $request->only(['status', 'assigned_to_user_id', 'unassigned', 'priority', 'search', 'per_page']),
        );

        return $this->paginated($conversations, WhatsAppConversationData::class);
    }

    public function show(Workspace $workspace, WhatsAppConversation $conversation): JsonResponse
    {
        $conversation->load(['phoneNumber', 'assignedUser']);

        return $this->success(
            WhatsAppConversationData::fromModel($conversation),
        );
    }

    public function assign(AssignConversationRequest $request, Workspace $workspace, WhatsAppConversation $conversation): JsonResponse
    {
        $user = $request->validated('user_id')
            ? User::findOrFail($request->validated('user_id'))
            : null;

        $this->conversationService->assignTo(
            $conversation,
            $user,
            $request->validated('team'),
        );

        return $this->success(null, 'Conversation assigned');
    }

    public function resolve(Workspace $workspace, WhatsAppConversation $conversation): JsonResponse
    {
        $this->conversationService->resolve($conversation);

        return $this->success(null, 'Conversation resolved');
    }

    public function reopen(Workspace $workspace, WhatsAppConversation $conversation): JsonResponse
    {
        $this->conversationService->reopen($conversation);

        return $this->success(null, 'Conversation reopened');
    }
}
