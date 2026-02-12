<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Support;

use App\Data\Support\CreateTicketData;
use App\Data\Support\SupportTicketData;
use App\Data\Support\SupportTicketSummaryData;
use App\Data\Support\UpdateTicketData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Support\CreateTicketRequest;
use App\Http\Requests\Support\UpdateTicketRequest;
use App\Models\Support\SupportTicket;
use App\Models\User;
use App\Services\Support\SupportTicketService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class SupportTicketController extends Controller
{
    public function __construct(
        private readonly SupportTicketService $ticketService,
    ) {}

    /**
     * List tickets for the authenticated user.
     * GET /support/tickets
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $filters = [
            'status' => $request->query('status'),
            'priority' => $request->query('priority'),
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 15),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
        ];

        $tickets = $this->ticketService->listForUser($user, $filters);

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
     * Create a new support ticket.
     * POST /support/tickets
     */
    public function store(CreateTicketRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenant = $user->tenant;

        if ($tenant === null) {
            return $this->error('No tenant associated with this user.', 422);
        }

        $data = CreateTicketData::from($request->validated());

        try {
            $ticket = $this->ticketService->create($user, $tenant, $data);

            return $this->created(
                SupportTicketData::fromModel($ticket)->toArray(),
                'Ticket created successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Get a specific ticket.
     * GET /support/tickets/{ticket}
     */
    public function show(Request $request, string $ticket): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $ticketModel = $this->ticketService->getForUser($user, $ticket);

            return $this->success(
                SupportTicketData::fromModel($ticketModel)->toArray(),
                'Ticket retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Ticket not found');
        }
    }

    /**
     * Update a ticket.
     * PUT /support/tickets/{ticket}
     */
    public function update(UpdateTicketRequest $request, string $ticket): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $ticketModel = $this->ticketService->getForUser($user, $ticket);

            // Only allow updates on open tickets
            if ($ticketModel->isClosed()) {
                return $this->error('Cannot update a closed ticket.', 422);
            }

            $data = UpdateTicketData::from($request->validated());
            $ticketModel = $this->ticketService->update($ticketModel, $data);

            return $this->success(
                SupportTicketData::fromModel($ticketModel)->toArray(),
                'Ticket updated successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Ticket not found');
        }
    }

    /**
     * Close a ticket.
     * POST /support/tickets/{ticket}/close
     */
    public function close(Request $request, string $ticket): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $ticketModel = $this->ticketService->getForUser($user, $ticket);
            $ticketModel = $this->ticketService->close($ticketModel);

            return $this->success(
                SupportTicketData::fromModel($ticketModel)->toArray(),
                'Ticket closed successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Ticket not found');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Reopen a ticket.
     * POST /support/tickets/{ticket}/reopen
     */
    public function reopen(Request $request, string $ticket): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $ticketModel = $this->ticketService->getForUser($user, $ticket);
            $ticketModel = $this->ticketService->reopen($ticketModel);

            return $this->success(
                SupportTicketData::fromModel($ticketModel)->toArray(),
                'Ticket reopened successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Ticket not found');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }
}
