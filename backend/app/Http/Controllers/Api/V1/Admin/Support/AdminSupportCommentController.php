<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Support;

use App\Data\Support\AddCommentData;
use App\Data\Support\SupportCommentData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Support\AddCommentRequest;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketComment;
use App\Services\Support\SupportCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class AdminSupportCommentController extends Controller
{
    public function __construct(
        private readonly SupportCommentService $commentService,
    ) {}

    /**
     * List all comments for a ticket (including internal notes).
     * GET /admin/support/tickets/{ticket}/comments
     */
    public function index(SupportTicket $ticket): JsonResponse
    {
        $comments = $this->commentService->listForTicket($ticket, includeInternal: true);

        return $this->success(
            $comments->map(fn (SupportTicketComment $comment) => SupportCommentData::fromModel($comment)->toArray())->toArray(),
            'Comments retrieved successfully'
        );
    }

    /**
     * Add an agent comment (visible to user).
     * POST /admin/support/tickets/{ticket}/comments
     */
    public function store(AddCommentRequest $request, SupportTicket $ticket): JsonResponse
    {
        /** @var SuperAdminUser $admin */
        $admin = $request->user();

        $data = new AddCommentData(
            content: $request->validated()['content'],
            is_internal: false
        );

        try {
            $comment = $this->commentService->addAgentComment($ticket, $admin, $data);

            return $this->created(
                SupportCommentData::fromModel($comment)->toArray(),
                'Comment added successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Add an internal note (not visible to user).
     * POST /admin/support/tickets/{ticket}/notes
     */
    public function storeNote(AddCommentRequest $request, SupportTicket $ticket): JsonResponse
    {
        /** @var SuperAdminUser $admin */
        $admin = $request->user();

        $data = new AddCommentData(
            content: $request->validated()['content'],
            is_internal: true
        );

        try {
            $comment = $this->commentService->addInternalNote($ticket, $admin, $data);

            return $this->created(
                SupportCommentData::fromModel($comment)->toArray(),
                'Internal note added successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }
}
