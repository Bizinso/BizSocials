<?php

declare(strict_types=1);

namespace App\Data\Support;

use App\Models\Support\SupportTicket;
use Spatie\LaravelData\Data;

final class SupportTicketSummaryData extends Data
{
    public function __construct(
        public string $id,
        public string $ticket_number,
        public string $subject,
        public string $status,
        public string $priority,
        public ?string $category_name,
        public ?string $assigned_to_name,
        public int $comment_count,
        public string $created_at,
        public string $updated_at,
    ) {}

    /**
     * Create SupportTicketSummaryData from a SupportTicket model.
     */
    public static function fromModel(SupportTicket $ticket): self
    {
        $ticket->loadMissing(['category', 'assignee']);

        return new self(
            id: $ticket->id,
            ticket_number: $ticket->ticket_number,
            subject: $ticket->subject,
            status: $ticket->status->value,
            priority: $ticket->priority->value,
            category_name: $ticket->category?->name,
            assigned_to_name: $ticket->assignee?->name,
            comment_count: $ticket->comment_count,
            created_at: $ticket->created_at->toIso8601String(),
            updated_at: $ticket->updated_at->toIso8601String(),
        );
    }
}
