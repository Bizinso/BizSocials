<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Feedback;

use App\Data\Feedback\AddFeedbackCommentData;
use App\Data\Feedback\FeedbackCommentData;
use App\Data\Feedback\FeedbackData;
use App\Data\Feedback\SubmitFeedbackData;
use App\Data\Feedback\VoteFeedbackData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Feedback\Feedback;
use App\Models\User;
use App\Services\Feedback\FeedbackService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class FeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackService $feedbackService,
    ) {}

    /**
     * List public feedback.
     * GET /feedback
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'type' => $request->query('type'),
            'category' => $request->query('category'),
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
            'per_page' => $request->query('per_page', 15),
        ];

        /** @var User|null $user */
        $user = $request->user();

        $feedback = $this->feedbackService->listPublic($filters);

        $transformedItems = collect($feedback->items())->map(
            fn (Feedback $item) => FeedbackData::fromModel($item, $user)->toArray()
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
     * Get popular feedback.
     * GET /feedback/popular
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 10), 50);

        /** @var User|null $user */
        $user = $request->user();

        $feedback = $this->feedbackService->getPopular($limit);

        $transformedItems = $feedback->map(
            fn (Feedback $item) => FeedbackData::fromModel($item, $user)->toArray()
        );

        return $this->success($transformedItems, 'Popular feedback retrieved successfully');
    }

    /**
     * Submit new feedback.
     * POST /feedback
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'required|string',
            'type' => 'sometimes|string',
            'category' => 'sometimes|string|nullable',
            'email' => 'sometimes|email|nullable',
            'name' => 'sometimes|string|max:100|nullable',
            'is_anonymous' => 'sometimes|boolean',
        ]);

        $data = SubmitFeedbackData::from($validated);

        /** @var User|null $user */
        $user = $request->user();

        try {
            $feedback = $this->feedbackService->submit($data, $user);

            return $this->created(
                FeedbackData::fromModel($feedback, $user)->toArray(),
                'Feedback submitted successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Get a specific feedback.
     * GET /feedback/{feedback}
     */
    public function show(Request $request, string $feedback): JsonResponse
    {
        try {
            $feedbackModel = $this->feedbackService->get($feedback);

            /** @var User|null $user */
            $user = $request->user();

            return $this->success(
                FeedbackData::fromModel($feedbackModel, $user)->toArray(),
                'Feedback retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Feedback not found');
        }
    }

    /**
     * Vote on feedback.
     * POST /feedback/{feedback}/vote
     */
    public function vote(Request $request, string $feedback): JsonResponse
    {
        $validated = $request->validate([
            'vote_type' => 'sometimes|string|in:upvote,downvote',
        ]);

        $data = VoteFeedbackData::from($validated);

        /** @var User $user */
        $user = $request->user();

        try {
            $feedbackModel = $this->feedbackService->get($feedback);
            $this->feedbackService->vote($feedbackModel, $user, $data->vote_type);

            $feedbackModel->refresh();

            return $this->success(
                FeedbackData::fromModel($feedbackModel, $user)->toArray(),
                'Vote recorded successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Feedback not found');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Remove vote from feedback.
     * DELETE /feedback/{feedback}/vote
     */
    public function removeVote(Request $request, string $feedback): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $feedbackModel = $this->feedbackService->get($feedback);
            $this->feedbackService->removeVote($feedbackModel, $user);

            $feedbackModel->refresh();

            return $this->success(
                FeedbackData::fromModel($feedbackModel, $user)->toArray(),
                'Vote removed successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Feedback not found');
        }
    }

    /**
     * Add a comment to feedback.
     * POST /feedback/{feedback}/comments
     */
    public function addComment(Request $request, string $feedback): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'commenter_name' => 'sometimes|string|max:100|nullable',
        ]);

        $data = AddFeedbackCommentData::from($validated);

        /** @var User|null $user */
        $user = $request->user();

        try {
            $feedbackModel = $this->feedbackService->get($feedback);
            $comment = $this->feedbackService->addComment($feedbackModel, $data, $user);

            return $this->created(
                FeedbackCommentData::fromModel($comment)->toArray(),
                'Comment added successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Feedback not found');
        }
    }

    /**
     * Get comments for feedback.
     * GET /feedback/{feedback}/comments
     */
    public function comments(Request $request, string $feedback): JsonResponse
    {
        try {
            $feedbackModel = $this->feedbackService->get($feedback);
            $comments = $feedbackModel->comments()
                ->public()
                ->orderBy('created_at', 'asc')
                ->get();

            $transformedComments = $comments->map(
                fn ($comment) => FeedbackCommentData::fromModel($comment)->toArray()
            );

            return $this->success($transformedComments, 'Comments retrieved successfully');
        } catch (ModelNotFoundException) {
            return $this->notFound('Feedback not found');
        }
    }
}
