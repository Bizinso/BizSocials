<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Feedback;

use App\Data\Feedback\CreateReleaseNoteData;
use App\Data\Feedback\ReleaseNoteData;
use App\Data\Feedback\UpdateReleaseNoteData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Feedback\ReleaseNote;
use App\Services\Feedback\ReleaseNoteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class AdminReleaseNoteController extends Controller
{
    public function __construct(
        private readonly ReleaseNoteService $releaseNoteService,
    ) {}

    /**
     * List all release notes (admin).
     * GET /admin/release-notes
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->query('status'),
            'type' => $request->query('type'),
            'search' => $request->query('search'),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
            'per_page' => $request->query('per_page', 15),
        ];

        $notes = $this->releaseNoteService->listAll($filters);

        $transformedItems = collect($notes->items())->map(
            fn (ReleaseNote $note) => ReleaseNoteData::fromModel($note)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Release notes retrieved successfully',
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
     * Create a new release note.
     * POST /admin/release-notes
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version' => 'required|string|max:50',
            'title' => 'required|string|max:200',
            'content' => 'required|string',
            'version_name' => 'sometimes|string|max:100|nullable',
            'summary' => 'sometimes|string|nullable',
            'release_type' => 'sometimes|string',
            'items' => 'sometimes|array|nullable',
            'items.*.title' => 'required_with:items|string',
            'items.*.description' => 'sometimes|string|nullable',
            'items.*.change_type' => 'required_with:items|string',
        ]);

        $data = CreateReleaseNoteData::from($validated);
        $note = $this->releaseNoteService->create($data);

        return $this->created(
            ReleaseNoteData::fromModel($note)->toArray(),
            'Release note created successfully'
        );
    }

    /**
     * Get a specific release note.
     * GET /admin/release-notes/{releaseNote}
     */
    public function show(Request $request, string $releaseNote): JsonResponse
    {
        try {
            $note = $this->releaseNoteService->get($releaseNote);

            return $this->success(
                ReleaseNoteData::fromModel($note)->toArray(),
                'Release note retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Release note not found');
        }
    }

    /**
     * Update a release note.
     * PUT /admin/release-notes/{releaseNote}
     */
    public function update(Request $request, string $releaseNote): JsonResponse
    {
        $validated = $request->validate([
            'version' => 'sometimes|string|max:50',
            'title' => 'sometimes|string|max:200',
            'content' => 'sometimes|string',
            'version_name' => 'sometimes|string|max:100|nullable',
            'summary' => 'sometimes|string|nullable',
            'release_type' => 'sometimes|string',
            'items' => 'sometimes|array|nullable',
            'items.*.title' => 'required_with:items|string',
            'items.*.description' => 'sometimes|string|nullable',
            'items.*.change_type' => 'required_with:items|string',
        ]);

        try {
            $note = $this->releaseNoteService->get($releaseNote);
            $data = UpdateReleaseNoteData::from($validated);
            $note = $this->releaseNoteService->update($note, $data);

            return $this->success(
                ReleaseNoteData::fromModel($note)->toArray(),
                'Release note updated successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Release note not found');
        }
    }

    /**
     * Publish a release note.
     * POST /admin/release-notes/{releaseNote}/publish
     */
    public function publish(Request $request, string $releaseNote): JsonResponse
    {
        try {
            $note = $this->releaseNoteService->get($releaseNote);
            $note = $this->releaseNoteService->publish($note);

            return $this->success(
                ReleaseNoteData::fromModel($note)->toArray(),
                'Release note published successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Release note not found');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Unpublish a release note.
     * POST /admin/release-notes/{releaseNote}/unpublish
     */
    public function unpublish(Request $request, string $releaseNote): JsonResponse
    {
        try {
            $note = $this->releaseNoteService->get($releaseNote);
            $note = $this->releaseNoteService->unpublish($note);

            return $this->success(
                ReleaseNoteData::fromModel($note)->toArray(),
                'Release note unpublished successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Release note not found');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Delete a release note.
     * DELETE /admin/release-notes/{releaseNote}
     */
    public function destroy(Request $request, string $releaseNote): JsonResponse
    {
        try {
            $note = $this->releaseNoteService->get($releaseNote);
            $this->releaseNoteService->delete($note);

            return $this->success(null, 'Release note deleted successfully');
        } catch (ModelNotFoundException) {
            return $this->notFound('Release note not found');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }
}
