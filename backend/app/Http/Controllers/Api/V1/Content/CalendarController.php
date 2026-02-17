<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Models\Content\Post;
use App\Models\Workspace\Workspace;
use App\Services\Content\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Calendar Controller
 *
 * Handles calendar view and post rescheduling operations
 */
class CalendarController extends Controller
{
    public function __construct(
        private readonly CalendarService $calendarService
    ) {
    }

    /**
     * Get calendar posts for a date range
     *
     * @param Request $request
     * @param Workspace $workspace
     * @return JsonResponse
     */
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'platforms' => 'sometimes|array',
            'platforms.*' => 'string|in:facebook,instagram,twitter,linkedin,tiktok,youtube',
            'status' => 'sometimes|array',
            'status.*' => 'string|in:draft,scheduled,publishing,published,failed',
            'author_id' => 'sometimes|uuid|exists:users,id',
            'category_id' => 'sometimes|uuid|exists:content_categories,id',
            'view' => 'sometimes|string|in:list,grouped,stats',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $filters = [
            'platforms' => $validated['platforms'] ?? [],
            'status' => $validated['status'] ?? [],
            'author_id' => $validated['author_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
        ];

        $view = $validated['view'] ?? 'list';

        $data = match ($view) {
            'grouped' => $this->calendarService->getCalendarPostsByDate(
                $workspace,
                $startDate,
                $endDate,
                $filters
            ),
            'stats' => $this->calendarService->getCalendarStats(
                $workspace,
                $startDate,
                $endDate
            ),
            default => $this->calendarService->getCalendarPosts(
                $workspace,
                $startDate,
                $endDate,
                $filters
            ),
        };

        return response()->json([
            'data' => $data,
            'meta' => [
                'start_date' => $startDate->toIso8601String(),
                'end_date' => $endDate->toIso8601String(),
                'filters' => $filters,
                'view' => $view,
            ],
        ]);
    }

    /**
     * Reschedule a post (drag-and-drop)
     *
     * @param Request $request
     * @param Workspace $workspace
     * @param Post $post
     * @return JsonResponse
     */
    public function reschedule(Request $request, Workspace $workspace, Post $post): JsonResponse
    {
        // Verify post belongs to workspace
        if ($post->workspace_id !== $workspace->id) {
            return response()->json([
                'message' => 'Post not found in this workspace',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'scheduled_at' => 'required|date|after:now',
            'timezone' => 'sometimes|string|timezone',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        $newScheduledAt = Carbon::parse($validated['scheduled_at']);
        $timezone = $validated['timezone'] ?? null;

        $updatedPost = $this->calendarService->reschedulePost(
            $post,
            $newScheduledAt,
            $timezone
        );

        return response()->json([
            'data' => $updatedPost->load(['author', 'targets', 'media', 'category']),
            'message' => 'Post rescheduled successfully',
        ]);
    }
}
