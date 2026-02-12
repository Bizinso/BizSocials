<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Feedback;

use App\Data\Feedback\ReleaseNoteData;
use App\Data\Feedback\SubscribeChangelogData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Feedback\ReleaseNote;
use App\Services\Feedback\ReleaseNoteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReleaseNoteController extends Controller
{
    public function __construct(
        private readonly ReleaseNoteService $releaseNoteService,
    ) {}

    /**
     * List published release notes (changelog).
     * GET /changelog
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'type' => $request->query('type'),
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 10),
        ];

        $notes = $this->releaseNoteService->listPublished($filters);

        $transformedItems = collect($notes->items())->map(
            fn (ReleaseNote $note) => ReleaseNoteData::fromModel($note)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Changelog retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $notes->currentPage(),
                'last_page' => $notes->lastPage(),
                'per_page' => $notes->perPage(),
                'total' => $notes->total(),
                'from' => $notes->firstItem(),
                'to' => $notes->lastItem(),
            ],
            'links' => [
                'first' => $notes->url(1),
                'last' => $notes->url($notes->lastPage()),
                'prev' => $notes->previousPageUrl(),
                'next' => $notes->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get a specific release note by slug.
     * GET /changelog/{slug}
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        try {
            $note = $this->releaseNoteService->getBySlug($slug);

            return $this->success(
                ReleaseNoteData::fromModel($note)->toArray(),
                'Release note retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Release note not found');
        }
    }

    /**
     * Subscribe to changelog updates.
     * POST /changelog/subscribe
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'notify_major' => 'sometimes|boolean',
            'notify_minor' => 'sometimes|boolean',
            'notify_patch' => 'sometimes|boolean',
        ]);

        $data = SubscribeChangelogData::from($validated);

        $subscription = $this->releaseNoteService->subscribe($data);

        return $this->success([
            'subscribed' => true,
            'email' => $subscription->email,
            'preferences' => [
                'notify_major' => $subscription->notify_major,
                'notify_minor' => $subscription->notify_minor,
                'notify_patch' => $subscription->notify_patch,
            ],
        ], 'Successfully subscribed to changelog updates');
    }

    /**
     * Unsubscribe from changelog updates.
     * POST /changelog/unsubscribe
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $this->releaseNoteService->unsubscribe($validated['email']);

            return $this->success([
                'unsubscribed' => true,
            ], 'Successfully unsubscribed from changelog updates');
        } catch (ModelNotFoundException) {
            return $this->notFound('Subscription not found');
        }
    }
}
