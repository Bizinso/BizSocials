<?php

declare(strict_types=1);

namespace App\Data\WhatsApp;

use App\Models\WhatsApp\WhatsAppConversation;
use Spatie\LaravelData\Data;

final class WhatsAppConversationData extends Data
{
    public function __construct(
        public string $id,
        public string $workspace_id,
        public string $customer_phone,
        public ?string $customer_name,
        public ?string $customer_profile_name,
        public string $status,
        public string $priority,
        public ?string $assigned_to_user_id,
        public ?string $assigned_to_name,
        public ?string $assigned_to_team,
        public ?string $last_message_at,
        public ?string $last_customer_message_at,
        public ?string $conversation_expires_at,
        public bool $is_within_service_window,
        public int $message_count,
        public ?string $first_response_at,
        public ?string $phone_number,
        public ?string $phone_display_name,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromModel(WhatsAppConversation $conversation): self
    {
        return new self(
            id: $conversation->id,
            workspace_id: $conversation->workspace_id,
            customer_phone: $conversation->customer_phone,
            customer_name: $conversation->customer_name,
            customer_profile_name: $conversation->customer_profile_name,
            status: $conversation->status->value,
            priority: $conversation->priority->value,
            assigned_to_user_id: $conversation->assigned_to_user_id,
            assigned_to_name: $conversation->assignedUser?->name,
            assigned_to_team: $conversation->assigned_to_team,
            last_message_at: $conversation->last_message_at?->toIso8601String(),
            last_customer_message_at: $conversation->last_customer_message_at?->toIso8601String(),
            conversation_expires_at: $conversation->conversation_expires_at?->toIso8601String(),
            is_within_service_window: $conversation->is_within_service_window,
            message_count: $conversation->message_count,
            first_response_at: $conversation->first_response_at?->toIso8601String(),
            phone_number: $conversation->phoneNumber?->phone_number,
            phone_display_name: $conversation->phoneNumber?->display_name,
            created_at: $conversation->created_at->toIso8601String(),
            updated_at: $conversation->updated_at->toIso8601String(),
        );
    }
}
