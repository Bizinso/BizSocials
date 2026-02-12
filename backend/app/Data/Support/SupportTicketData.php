<?php

declare(strict_types=1);

namespace App\Data\Support;

use App\Models\Support\SupportTicket;
use Spatie\LaravelData\Data;

final class SupportTicketData extends Data
{
    public function __construct(
        public string $id,
        public string $ticket_number,
        public string $subject,
        public string $description,
        public string $status,
        public string $priority,
        public string $type,
        public string $channel,
        public ?string $category_id,
        public ?string $category_name,
        public string $user_id,
        public string $user_name,
        public string $user_email,
        public ?string $tenant_id,
        public ?string $tenant_name,
        public ?string $assigned_to_id,
        public ?string $assigned_to_name,
        public int $comment_count,
        public ?string $first_response_at,
        public ?string $resolved_at,
        public ?string $closed_at,
        public string $created_at,
        public string $updated_at,
    ) {}

    /**
     * Create SupportTicketData from a SupportTicket model.
     */
    public static function fromModel(SupportTicket $ticket): self
    {
        $ticket->loadMissing(['user', 'tenant', 'category', 'assignee']);

        return new self(
            id: $ticket->id,
            ticket_number: $ticket->ticket_number,
            subject: $ticket->subject,
            description: $ticket->description,
            status: $ticket->status->value,
            priority: $ticket->priority->value,
            type: $ticket->ticket_type->value,
            channel: $ticket->channel->value,
            category_id: $ticket->category_id,
            category_name: $ticket->category?->name,
            user_id: $ticket->user_id ?? '',
            user_name: $ticket->user?->name ?? $ticket->requester_name,
            user_email: $ticket->user?->email ?? $ticket->requester_email,
            tenant_id: $ticket->tenant_id,
            tenant_name: $ticket->tenant?->name,
            assigned_to_id: $ticket->assigned_to,
            assigned_to_name: $ticket->assignee?->name,
            comment_count: $ticket->comment_count,
            first_response_at: $ticket->first_response_at?->toIso8601String(),
            resolved_at: $ticket->resolved_at?->toIso8601String(),
            closed_at: $ticket->closed_at?->toIso8601String(),
            created_at: $ticket->created_at->toIso8601String(),
            updated_at: $ticket->updated_at->toIso8601String(),
        );
    }
}
