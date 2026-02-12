<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Support;

use App\Data\Support\AddCommentData;
use App\Data\Support\SupportCommentData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Support\AddCommentRequest;
use App\Models\Support\SupportTicketComment;
use App\Models\User;
use App\Services\Support\SupportCommentService;
use App\Services\Support\SupportTicketService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class SupportCommentController extends Controller
{
    public function __construct(
        private readonly SupportCommentService $commentService,
        private readonly SupportTicketService $ticketService,
    ) {}

    /**
     * List comments for a ticket.
     * GET /support/tickets/{ticket}/comments
     */
    public function index(Request $request, string $ticket): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $ticketModel = $this->ticketService->getForUser($user, $ticket);

            // Get only public comments for regular users
            $comments = $this->commentService->listForTicket($ticketModel, includeInternal: false);

            return $this->success(
                $comments->map(fn (SupportTicketComment $comment) => SupportCommentData::fromModel($comment)->toArray())->toArray(),
                'Comments retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Ticket not found');
        }
    }

    /**
     * Add a comment to a ticket.
     * POST /support/tickets/{ticket}/comments
     */
    public function store(AddCommentRequest $request, string $ticket): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $ticketModel = $this->ticketService->getForUser($user, $ticket);

            $data = new AddCommentData(
                content: $request->validated()['content'],
                is_internal: false
            );

            $comment = $this->commentService->addUserComment($ticketModel, $user, $data);

            return $this->created(
                SupportCommentData::fromModel($comment)->toArray(),
                'Comment added successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Ticket not found');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }
}
