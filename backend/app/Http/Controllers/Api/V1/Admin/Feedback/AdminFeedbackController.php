<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Feedback;

use App\Data\Feedback\FeedbackData;
use App\Data\Feedback\FeedbackStatsData;
use App\Enums\Feedback\FeedbackStatus;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Feedback\Feedback;
use App\Models\Feedback\RoadmapItem;
use App\Services\Feedback\FeedbackService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class AdminFeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackService $feedbackService,
    ) {}

    /**
     * List all feedback (admin).
     * GET /admin/feedback
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'type' => $request->query('type'),
            'category' => $request->query('category'),
            'status' => $request->query('status'),
            'is_open' => $request->query('is_open'),
            'is_closed' => $request->query('is_closed'),
            'search' => $request->query('search'),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
            'per_page' => $request->query('per_page', 15),
        ];

        $feedback = $this->feedbackService->listAll($filters);

        $transformedItems = collect($feedback->items())->map(
            fn (Feedback $item) => FeedbackData::fromModel($item)->toArray()
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
     * Get a specific feedback (admin).
     * GET /admin/feedback/{feedback}
     */
    public function show(Request $request, string $feedback): JsonResponse
    {
        try {
            $feedbackModel = $this->feedbackService->get($feedback);

            return $this->success(
                FeedbackData::fromModel($feedbackModel)->toArray(),
                'Feedback retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Feedback not found');
        }
    }

    /**
     * Update feedback status.
     * PUT /admin/feedback/{feedback}/status
     */
    public function updateStatus(Request $request, string $feedback): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'reason' => 'sometimes|string|nullable',
        ]);

        $status = FeedbackStatus::tryFrom($validated['status']);

        if ($status === null) {
            return $this->error('Invalid status provided', 422);
        }

        try {
            $feedbackModel = $this->feedbackService->get($feedback);
            $feedbackModel = $this->feedbackService->updateStatus(
                $feedbackModel,
                $status,
                $validated['reason'] ?? null
            );

            return $this->success(
                FeedbackData::fromModel($feedbackModel)->toArray(),
                'Feedback status updated successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Feedback not found');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Link feedback to a roadmap item.
     * POST /admin/feedback/{feedback}/link-roadmap
     */
    public function linkToRoadmap(Request $request, string $feedback): JsonResponse
    {
        $validated = $request->validate([
            'roadmap_item_id' => 'required|string|exists:roadmap_items,id',
        ]);

        try {
            $feedbackModel = $this->feedbackService->get($feedback);
            $roadmapItem = RoadmapItem::findOrFail($validated['roadmap_item_id']);

            $this->feedbackService->linkToRoadmap($feedbackModel, $roadmapItem);

            $feedbackModel->refresh();

            return $this->success(
                FeedbackData::fromModel($feedbackModel)->toArray(),
                'Feedback linked to roadmap item successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Feedback or roadmap item not found');
        }
    }

    /**
     * Get feedback statistics.
     * GET /admin/feedback-stats
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->feedbackService->getStats();

        return $this->success($stats->toArray(), 'Feedback statistics retrieved successfully');
    }
}
