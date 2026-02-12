<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Support;

use App\Data\Support\SupportStatsData;
use App\Data\Support\SupportTicketData;
use App\Data\Support\SupportTicketSummaryData;
use App\Enums\Support\SupportTicketPriority;
use App\Enums\Support\SupportTicketStatus;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Support\AdminAssignRequest;
use App\Http\Requests\Support\AdminUpdatePriorityRequest;
use App\Http\Requests\Support\AdminUpdateStatusRequest;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportTicket;
use App\Services\Support\SupportTicketService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class AdminSupportTicketController extends Controller
{
    public function __construct(
        private readonly SupportTicketService $ticketService,
    ) {}

    /**
     * List all tickets.
     * GET /admin/support/tickets
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->query('status'),
            'priority' => $request->query('priority'),
            'type' => $request->query('type'),
            'category_id' => $request->query('category_id'),
            'assigned_to' => $request->query('assigned_to'),
            'unassigned' => $request->boolean('unassigned'),
            'overdue' => $request->boolean('overdue'),
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 15),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
        ];

        $tickets = $this->ticketService->listAll($filters);

        $transformedItems = collect($tickets->items())->map(
            fn (SupportTicket $ticket) => SupportTicketSummaryData::fromModel($ticket)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Tickets retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
                'from' => $tickets->firstItem(),
                'to' => $tickets->lastItem(),
            ],
            'links' => [
                'first' => $tickets->url(1),
                'last' => $tickets->url($tickets->lastPage()),
                'prev' => $tickets->previousPageUrl(),
                'next' => $tickets->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get a specific ticket.
     * GET /admin/support/tickets/{ticket}
     */
    public function show(SupportTicket $ticket): JsonResponse
    {
        try {
            $ticket = $this->ticketService->get($ticket->id);

            return $this->success(
                SupportTicketData::fromModel($ticket)->toArray(),
                'Ticket retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Ticket not found');
        }
    }

    /**
     * Assign ticket to an agent.
     * POST /admin/support/tickets/{ticket}/assign
     */
    public function assign(AdminAssignRequest $request, SupportTicket $ticket): JsonResponse
    {
        $agent = SuperAdminUser::find($request->validated()['agent_id']);

        if ($agent === null) {
            return $this->notFound('Agent not found');
        }

        $ticket = $this->ticketService->assign($ticket, $agent);

        return $this->success(
            SupportTicketData::fromModel($ticket)->toArray(),
            'Ticket assigned successfully'
        );
    }

    /**
     * Unassign ticket.
     * POST /admin/support/tickets/{ticket}/unassign
     */
    public function unassign(SupportTicket $ticket): JsonResponse
    {
        $ticket = $this->ticketService->unassign($ticket);

        return $this->success(
            SupportTicketData::fromModel($ticket)->toArray(),
            'Ticket unassigned successfully'
        );
    }

    /**
     * Update ticket status.
     * PUT /admin/support/tickets/{ticket}/status
     */
    public function updateStatus(AdminUpdateStatusRequest $request, SupportTicket $ticket): JsonResponse
    {
        $status = SupportTicketStatus::from($request->validated()['status']);

        try {
            $ticket = $this->ticketService->updateStatus($ticket, $status);

            return $this->success(
                SupportTicketData::fromModel($ticket)->toArray(),
                'Ticket status updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Update ticket priority.
     * PUT /admin/support/tickets/{ticket}/priority
     */
    public function updatePriority(AdminUpdatePriorityRequest $request, SupportTicket $ticket): JsonResponse
    {
        $priority = SupportTicketPriority::from($request->validated()['priority']);
        $ticket = $this->ticketService->updatePriority($ticket, $priority);

        return $this->success(
            SupportTicketData::fromModel($ticket)->toArray(),
            'Ticket priority updated successfully'
        );
    }

    /**
     * Get support statistics.
     * GET /admin/support/stats
     */
    public function stats(): JsonResponse
    {
        $stats = $this->ticketService->getStats();

        return $this->success(
            $stats->toArray(),
            'Stats retrieved successfully'
        );
    }
}
