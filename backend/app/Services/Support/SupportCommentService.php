<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Data\Support\AddCommentData;
use App\Enums\Support\SupportCommentType;
use App\Enums\Support\SupportTicketStatus;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketComment;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class SupportCommentService extends BaseService
{
    /**
     * List comments for a ticket (user view - public only).
     *
     * @return Collection<int, SupportTicketComment>
     */
    public function listForTicket(SupportTicket $ticket, bool $includeInternal = false): Collection
    {
        $query = SupportTicketComment::forTicket($ticket->id)
            ->with(['user', 'admin'])
            ->orderBy('created_at', 'asc');

        if (!$includeInternal) {
            $query->public();
        }

        return $query->get();
    }

    /**
     * Add a comment from a user.
     *
     * @throws ValidationException
     */
    public function addUserComment(SupportTicket $ticket, User $user, AddCommentData $data): SupportTicketComment
    {
        // Check if ticket allows new comments
        if ($ticket->isClosed()) {
            throw ValidationException::withMessages([
                'ticket' => ['Cannot add comments to a closed ticket.'],
            ]);
        }

        return $this->transaction(function () use ($ticket, $user, $data) {
            $comment = $ticket->addComment(
                content: $data->content,
                type: SupportCommentType::REPLY,
                userId: $user->id,
                adminId: null,
                isInternal: false
            );

            // If ticket is waiting on customer, move it back to open
            if ($ticket->status === SupportTicketStatus::WAITING_CUSTOMER) {
                $ticket->changeStatus(SupportTicketStatus::OPEN);
            }

            $this->log('User comment added to ticket', [
                'ticket_id' => $ticket->id,
                'comment_id' => $comment->id,
                'user_id' => $user->id,
            ]);

            return $comment->fresh(['user', 'admin']);
        });
    }

    /**
     * Add a comment from an agent (admin).
     *
     * @throws ValidationException
     */
    public function addAgentComment(SupportTicket $ticket, SuperAdminUser $agent, AddCommentData $data): SupportTicketComment
    {
        // Check if ticket allows new comments
        if ($ticket->isClosed()) {
            throw ValidationException::withMessages([
                'ticket' => ['Cannot add comments to a closed ticket. Please reopen the ticket first.'],
            ]);
        }

        return $this->transaction(function () use ($ticket, $agent, $data) {
            $comment = $ticket->addComment(
                content: $data->content,
                type: SupportCommentType::REPLY,
                userId: null,
                adminId: $agent->id,
                isInternal: false
            );

            // If ticket is new or waiting internal, move it to waiting on customer
            if (in_array($ticket->status, [SupportTicketStatus::NEW, SupportTicketStatus::WAITING_INTERNAL], true)) {
                $ticket->changeStatus(SupportTicketStatus::WAITING_CUSTOMER);
            } elseif ($ticket->status === SupportTicketStatus::OPEN || $ticket->status === SupportTicketStatus::REOPENED) {
                $ticket->changeStatus(SupportTicketStatus::WAITING_CUSTOMER);
            }

            $this->log('Agent comment added to ticket', [
                'ticket_id' => $ticket->id,
                'comment_id' => $comment->id,
                'agent_id' => $agent->id,
            ]);

            return $comment->fresh(['user', 'admin']);
        });
    }

    /**
     * Add an internal note (admin only, not visible to user).
     *
     * @throws ValidationException
     */
    public function addInternalNote(SupportTicket $ticket, SuperAdminUser $agent, AddCommentData $data): SupportTicketComment
    {
        return $this->transaction(function () use ($ticket, $agent, $data) {
            $comment = $ticket->addComment(
                content: $data->content,
                type: SupportCommentType::NOTE,
                userId: null,
                adminId: $agent->id,
                isInternal: true
            );

            $this->log('Internal note added to ticket', [
                'ticket_id' => $ticket->id,
                'comment_id' => $comment->id,
                'agent_id' => $agent->id,
            ]);

            return $comment->fresh(['user', 'admin']);
        });
    }
}
