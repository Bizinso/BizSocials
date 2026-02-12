<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inbox;

use App\Data\Inbox\CreateReplyData;
use App\Data\Inbox\InboxReplyData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Inbox\CreateReplyRequest;
use App\Models\Inbox\InboxItem;
use App\Models\Inbox\InboxReply;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Inbox\InboxReplyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class InboxReplyController extends Controller
{
    public function __construct(
        private readonly InboxReplyService $inboxReplyService,
    ) {}

    /**
     * List replies for an inbox item.
     */
    public function index(Request $request, Workspace $workspace, InboxItem $inboxItem): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Verify workspace belongs to user's tenant
        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        // Verify inbox item belongs to this workspace
        if ($inboxItem->workspace_id !== $workspace->id) {
            return $this->notFound('Inbox item not found');
        }

        // Check if user has access to this workspace
        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $replies = $this->inboxReplyService->listForItem($inboxItem);

        $transformedReplies = $replies->map(
            fn (InboxReply $reply) => InboxReplyData::fromModel($reply)->toArray()
        );

        return $this->success(
            $transformedReplies->toArray(),
            'Replies retrieved successfully'
        );
    }

    /**
     * Create a reply to an inbox item.
     */
    public function store(CreateReplyRequest $request, Workspace $workspace, InboxItem $inboxItem): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = CreateReplyData::from($request->validated());

        try {
            $reply = $this->inboxReplyService->create($inboxItem, $user, $data);

            return $this->created(
                InboxReplyData::fromModel($reply)->toArray(),
                'Reply created successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }
}
