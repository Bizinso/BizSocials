<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\KB;

use App\Data\KnowledgeBase\KBFeedbackData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\KnowledgeBase\KBArticleFeedback;
use App\Models\Platform\SuperAdminUser;
use App\Services\KnowledgeBase\KBFeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminKBFeedbackController extends Controller
{
    public function __construct(
        private readonly KBFeedbackService $feedbackService,
    ) {}

    /**
     * List all feedback (admin view).
     * GET /admin/kb/feedback
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->query('status'),
            'article_id' => $request->query('article_id'),
            'is_helpful' => $request->query('is_helpful'),
            'category' => $request->query('category'),
            'per_page' => $request->query('per_page', 15),
        ];

        $feedback = $this->feedbackService->list($filters);

        $transformedItems = collect($feedback->items())->map(
            fn (KBArticleFeedback $f) => KBFeedbackData::fromModel($f)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Feedback retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $feedback->currentPage(),
                'last_page' => $feedback->lastPage(),
                'per_page' => $feedback->perPage(),
                'total' => $feedback->total(),
                'from' => $feedback->firstItem(),
                'to' => $feedback->lastItem(),
            ],
            'links' => [
                'first' => $feedback->url(1),
                'last' => $feedback->url($feedback->lastPage()),
                'prev' => $feedback->previousPageUrl(),
                'next' => $feedback->nextPageUrl(),
            ],
        ]);
    }

    /**
     * List pending feedback.
     * GET /admin/kb/feedback/pending
     */
    public function pending(Request $request): JsonResponse
    {
        $filters = [
            'article_id' => $request->query('article_id'),
            'is_helpful' => $request->query('is_helpful'),
            'category' => $request->query('category'),
            'per_page' => $request->query('per_page', 15),
        ];

        $feedback = $this->feedbackService->listPending($filters);

        $transformedItems = collect($feedback->items())->map(
            fn (KBArticleFeedback $f) => KBFeedbackData::fromModel($f)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Pending feedback retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $feedback->currentPage(),
                'last_page' => $feedback->lastPage(),
                'per_page' => $feedback->perPage(),
                'total' => $feedback->total(),
                'from' => $feedback->firstItem(),
                'to' => $feedback->lastItem(),
            ],
            'links' => [
                'first' => $feedback->url(1),
                'last' => $feedback->url($feedback->lastPage()),
                'prev' => $feedback->previousPageUrl(),
                'next' => $feedback->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Resolve feedback (mark as reviewed).
     * POST /admin/kb/feedback/{feedback}/resolve
     */
    public function resolve(Request $request, KBArticleFeedback $feedback): JsonResponse
    {
        /** @var SuperAdminUser $admin */
        $admin = $request->user();
        $notes = $request->input('notes');

        $feedback = $this->feedbackService->resolve($feedback, $admin, $notes);

        return $this->success(
            KBFeedbackData::fromModel($feedback)->toArray(),
            'Feedback resolved successfully'
        );
    }

    /**
     * Mark feedback as actioned.
     * POST /admin/kb/feedback/{feedback}/action
     */
    public function action(Request $request, KBArticleFeedback $feedback): JsonResponse
    {
        /** @var SuperAdminUser $admin */
        $admin = $request->user();
        $notes = $request->input('notes');

        $feedback = $this->feedbackService->action($feedback, $admin, $notes);

        return $this->success(
            KBFeedbackData::fromModel($feedback)->toArray(),
            'Feedback marked as actioned'
        );
    }

    /**
     * Dismiss feedback.
     * POST /admin/kb/feedback/{feedback}/dismiss
     */
    public function dismiss(Request $request, KBArticleFeedback $feedback): JsonResponse
    {
        /** @var SuperAdminUser $admin */
        $admin = $request->user();
        $notes = $request->input('notes');

        $feedback = $this->feedbackService->dismiss($feedback, $admin, $notes);

        return $this->success(
            KBFeedbackData::fromModel($feedback)->toArray(),
            'Feedback dismissed'
        );
    }
}
