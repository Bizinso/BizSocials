<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Data\Support\CreateTicketData;
use App\Data\Support\SupportStatsData;
use App\Data\Support\UpdateTicketData;
use App\Enums\Support\SupportChannel;
use App\Enums\Support\SupportTicketPriority;
use App\Enums\Support\SupportTicketStatus;
use App\Enums\Support\SupportTicketType;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportCategory;
use App\Models\Support\SupportTicket;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class SupportTicketService extends BaseService
{
    /**
     * List tickets for a specific user.
     *
     * @param array<string, mixed> $filters
     */
    public function listForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = SupportTicket::forUser($user->id)
            ->with(['category', 'assignee']);

        // Filter by status
        if (!empty($filters['status'])) {
            $status = SupportTicketStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->byStatus($status);
            }
        }

        // Filter by priority
        if (!empty($filters['priority'])) {
            $priority = SupportTicketPriority::tryFrom($filters['priority']);
            if ($priority !== null) {
                $query->byPriority($priority);
            }
        }

        // Search
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    /**
     * Create a new support ticket.
     */
    public function create(User $user, Tenant $tenant, CreateTicketData $data): SupportTicket
    {
        return $this->transaction(function () use ($user, $tenant, $data) {
            // Validate category if provided
            if ($data->category_id !== null) {
                $category = SupportCategory::find($data->category_id);
                if ($category === null || !$category->is_active) {
                    throw ValidationException::withMessages([
                        'category_id' => ['The selected category is invalid or inactive.'],
                    ]);
                }
            }

            $ticket = SupportTicket::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'requester_email' => $user->email,
                'requester_name' => $user->name,
                'category_id' => $data->category_id,
                'subject' => $data->subject,
                'description' => $data->description,
                'ticket_type' => $data->type,
                'priority' => $data->priority,
                'status' => SupportTicketStatus::NEW,
                'channel' => SupportChannel::WEB_FORM,
            ]);

            // Increment category ticket count if applicable
            if ($data->category_id !== null && isset($category)) {
                $category->incrementTicketCount();
            }

            $this->log('Support ticket created', [
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
            ]);

            return $ticket->fresh(['user', 'tenant', 'category', 'assignee']);
        });
    }

    /**
     * Get a ticket by ID.
     *
     * @throws ModelNotFoundException
     */
    public function get(string $id): SupportTicket
    {
        $ticket = SupportTicket::with(['user', 'tenant', 'category', 'assignee'])
            ->find($id);

        if ($ticket === null) {
            throw new ModelNotFoundException('Support ticket not found.');
        }

        return $ticket;
    }

    /**
     * Get a ticket for a specific user (ensures ownership).
     *
     * @throws ModelNotFoundException
     */
    public function getForUser(User $user, string $id): SupportTicket
    {
        $ticket = SupportTicket::forUser($user->id)
            ->with(['user', 'tenant', 'category', 'assignee'])
            ->find($id);

        if ($ticket === null) {
            throw new ModelNotFoundException('Support ticket not found.');
        }

        return $ticket;
    }

    /**
     * Update a ticket (limited fields for user).
     */
    public function update(SupportTicket $ticket, UpdateTicketData $data): SupportTicket
    {
        return $this->transaction(function () use ($ticket, $data) {
            $updateData = [];

            if ($data->subject !== null) {
                $updateData['subject'] = $data->subject;
            }

            if ($data->description !== null) {
                $updateData['description'] = $data->description;
            }

            if (!empty($updateData)) {
                $ticket->update($updateData);
                $ticket->last_activity_at = now();
                $ticket->save();
            }

            $this->log('Support ticket updated', [
                'ticket_id' => $ticket->id,
            ]);

            return $ticket->fresh(['user', 'tenant', 'category', 'assignee']);
        });
    }

    /**
     * Close a ticket.
     *
     * @throws ValidationException
     */
    public function close(SupportTicket $ticket): SupportTicket
    {
        if (!$ticket->status->canTransitionTo(SupportTicketStatus::CLOSED)) {
            throw ValidationException::withMessages([
                'status' => ['Ticket cannot be closed from its current status.'],
            ]);
        }

        $ticket->close();

        $this->log('Support ticket closed', [
            'ticket_id' => $ticket->id,
        ]);

        return $ticket->fresh(['user', 'tenant', 'category', 'assignee']);
    }

    /**
     * Reopen a closed ticket.
     *
     * @throws ValidationException
     */
    public function reopen(SupportTicket $ticket): SupportTicket
    {
        if (!$ticket->status->canTransitionTo(SupportTicketStatus::REOPENED)) {
            throw ValidationException::withMessages([
                'status' => ['Ticket cannot be reopened from its current status.'],
            ]);
        }

        $ticket->reopen();

        $this->log('Support ticket reopened', [
            'ticket_id' => $ticket->id,
        ]);

        return $ticket->fresh(['user', 'tenant', 'category', 'assignee']);
    }

    /**
     * List all tickets (admin).
     *
     * @param array<string, mixed> $filters
     */
    public function listAll(array $filters = []): LengthAwarePaginator
    {
        $query = SupportTicket::with(['user', 'tenant', 'category', 'assignee']);

        // Filter by status
        if (!empty($filters['status'])) {
            $status = SupportTicketStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->byStatus($status);
            }
        }

        // Filter by priority
        if (!empty($filters['priority'])) {
            $priority = SupportTicketPriority::tryFrom($filters['priority']);
            if ($priority !== null) {
                $query->byPriority($priority);
            }
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $type = SupportTicketType::tryFrom($filters['type']);
            if ($type !== null) {
                $query->byType($type);
            }
        }

        // Filter by category
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filter by assigned agent
        if (!empty($filters['assigned_to'])) {
            $query->assignedTo($filters['assigned_to']);
        }

        // Filter unassigned
        if (!empty($filters['unassigned'])) {
            $query->unassigned();
        }

        // Filter overdue
        if (!empty($filters['overdue'])) {
            $query->overdue();
        }

        // Search
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    /**
     * Assign a ticket to an admin agent.
     */
    public function assign(SupportTicket $ticket, SuperAdminUser $agent): SupportTicket
    {
        $ticket->assign($agent);

        $this->log('Support ticket assigned', [
            'ticket_id' => $ticket->id,
            'assigned_to' => $agent->id,
        ]);

        return $ticket->fresh(['user', 'tenant', 'category', 'assignee']);
    }

    /**
     * Unassign a ticket.
     */
    public function unassign(SupportTicket $ticket): SupportTicket
    {
        $ticket->unassign();

        $this->log('Support ticket unassigned', [
            'ticket_id' => $ticket->id,
        ]);

        return $ticket->fresh(['user', 'tenant', 'category', 'assignee']);
    }

    /**
     * Update ticket status.
     *
     * @throws ValidationException
     */
    public function updateStatus(SupportTicket $ticket, SupportTicketStatus $status): SupportTicket
    {
        if (!$ticket->changeStatus($status)) {
            throw ValidationException::withMessages([
                'status' => ['Cannot transition to the specified status from the current status.'],
            ]);
        }

        $this->log('Support ticket status updated', [
            'ticket_id' => $ticket->id,
            'new_status' => $status->value,
        ]);

        return $ticket->fresh(['user', 'tenant', 'category', 'assignee']);
    }

    /**
     * Update ticket priority.
     */
    public function updatePriority(SupportTicket $ticket, SupportTicketPriority $priority): SupportTicket
    {
        $ticket->priority = $priority;
        $ticket->sla_due_at = $ticket->calculateSlaDue();
        $ticket->save();

        $this->log('Support ticket priority updated', [
            'ticket_id' => $ticket->id,
            'new_priority' => $priority->value,
        ]);

        return $ticket->fresh(['user', 'tenant', 'category', 'assignee']);
    }

    /**
     * Get support statistics.
     */
    public function getStats(): SupportStatsData
    {
        $totalTickets = SupportTicket::count();
        $openTickets = SupportTicket::open()->count();
        $pendingTickets = SupportTicket::pending()->count();
        $resolvedTickets = SupportTicket::byStatus(SupportTicketStatus::RESOLVED)->count();
        $closedTickets = SupportTicket::byStatus(SupportTicketStatus::CLOSED)->count();
        $unassignedTickets = SupportTicket::unassigned()->whereNotIn('status', [
            SupportTicketStatus::RESOLVED,
            SupportTicketStatus::CLOSED,
        ])->count();

        // Stats by priority
        $byPriority = [];
        foreach (SupportTicketPriority::cases() as $priority) {
            $byPriority[$priority->value] = SupportTicket::byPriority($priority)->count();
        }

        // Stats by type
        $byType = [];
        foreach (SupportTicketType::cases() as $type) {
            $byType[$type->value] = SupportTicket::byType($type)->count();
        }

        return new SupportStatsData(
            total_tickets: $totalTickets,
            open_tickets: $openTickets,
            pending_tickets: $pendingTickets,
            resolved_tickets: $resolvedTickets,
            closed_tickets: $closedTickets,
            unassigned_tickets: $unassignedTickets,
            by_priority: $byPriority,
            by_type: $byType,
        );
    }
}
