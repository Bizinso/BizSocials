<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\WhatsApp\WhatsAppConversationStatus;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppPhoneNumber;
use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

final class WhatsAppConversationService extends BaseService
{
    public function findOrCreateConversation(
        WhatsAppPhoneNumber $phone,
        string $customerPhone,
        ?string $customerName,
        string $workspaceId,
    ): WhatsAppConversation {
        return WhatsAppConversation::firstOrCreate(
            [
                'whatsapp_phone_number_id' => $phone->id,
                'customer_phone' => $customerPhone,
            ],
            [
                'workspace_id' => $workspaceId,
                'customer_name' => $customerName,
                'status' => WhatsAppConversationStatus::ACTIVE,
                'last_message_at' => now(),
            ],
        );
    }

    public function assignTo(WhatsAppConversation $conversation, ?User $user, ?string $team = null): void
    {
        $conversation->update([
            'assigned_to_user_id' => $user?->id,
            'assigned_to_team' => $team,
        ]);

        $this->log('Conversation assigned', [
            'conversation_id' => $conversation->id,
            'user_id' => $user?->id,
            'team' => $team,
        ]);
    }

    public function resolve(WhatsAppConversation $conversation): void
    {
        $conversation->update(['status' => WhatsAppConversationStatus::RESOLVED]);
        $this->log('Conversation resolved', ['conversation_id' => $conversation->id]);
    }

    public function reopen(WhatsAppConversation $conversation): void
    {
        $conversation->update(['status' => WhatsAppConversationStatus::ACTIVE]);
        $this->log('Conversation reopened', ['conversation_id' => $conversation->id]);
    }

    public function listForWorkspace(string $workspaceId, array $filters = []): LengthAwarePaginator
    {
        $query = WhatsAppConversation::forWorkspace($workspaceId)
            ->with(['phoneNumber', 'assignedUser'])
            ->orderByDesc('last_message_at');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['assigned_to_user_id'])) {
            $query->where('assigned_to_user_id', $filters['assigned_to_user_id']);
        }

        if (!empty($filters['unassigned'])) {
            $query->whereNull('assigned_to_user_id');
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_profile_name', 'like', "%{$search}%");
            });
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }
}
