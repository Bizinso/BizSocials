<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Feedback;

use App\Data\Feedback\CreateRoadmapItemData;
use App\Data\Feedback\RoadmapItemData;
use App\Data\Feedback\UpdateRoadmapItemData;
use App\Enums\Feedback\RoadmapStatus;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Feedback\RoadmapItem;
use App\Services\Feedback\RoadmapService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class AdminRoadmapController extends Controller
{
    public function __construct(
        private readonly RoadmapService $roadmapService,
    ) {}

    /**
     * List all roadmap items (admin).
     * GET /admin/roadmap
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->query('status'),
            'category' => $request->query('category'),
            'quarter' => $request->query('quarter'),
            'is_public' => $request->query('is_public'),
            'search' => $request->query('search'),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
            'per_page' => $request->query('per_page', 15),
        ];

        $items = $this->roadmapService->listAll($filters);

        $transformedItems = collect($items->items())->map(
            fn (RoadmapItem $item) => RoadmapItemData::fromModel($item)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Roadmap items retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ],
            'links' => [
                'first' => $items->url(1),
                'last' => $items->url($items->lastPage()),
                'prev' => $items->previousPageUrl(),
                'next' => $items->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Create a new roadmap item.
     * POST /admin/roadmap
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'sometimes|string|nullable',
            'detailed_description' => 'sometimes|string|nullable',
            'category' => 'sometimes|string',
            'status' => 'sometimes|string',
            'target_quarter' => 'sometimes|string|max:20|nullable',
            'target_date' => 'sometimes|date|nullable',
            'is_public' => 'sometimes|boolean',
        ]);

        $data = CreateRoadmapItemData::from($validated);
        $item = $this->roadmapService->create($data);

        return $this->created(
            RoadmapItemData::fromModel($item)->toArray(),
            'Roadmap item created successfully'
        );
    }

    /**
     * Get a specific roadmap item.
     * GET /admin/roadmap/{roadmapItem}
     */
    public function show(Request $request, string $roadmapItem): JsonResponse
    {
        try {
            $item = $this->roadmapService->getItem($roadmapItem);

            return $this->success(
                RoadmapItemData::fromModel($item)->toArray(),
                'Roadmap item retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Roadmap item not found');
        }
    }

    /**
     * Update a roadmap item.
     * PUT /admin/roadmap/{roadmapItem}
     */
    public function update(Request $request, string $roadmapItem): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:200',
            'description' => 'sometimes|string|nullable',
            'detailed_description' => 'sometimes|string|nullable',
            'category' => 'sometimes|string',
            'status' => 'sometimes|string',
            'target_quarter' => 'sometimes|string|max:20|nullable',
            'target_date' => 'sometimes|date|nullable',
            'progress_percentage' => 'sometimes|integer|min:0|max:100',
            'is_public' => 'sometimes|boolean',
        ]);

        try {
            $item = $this->roadmapService->getItem($roadmapItem);
            $data = UpdateRoadmapItemData::from($validated);
            $item = $this->roadmapService->update($item, $data);

            return $this->success(
                RoadmapItemData::fromModel($item)->toArray(),
                'Roadmap item updated successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Roadmap item not found');
        }
    }

    /**
     * Update roadmap item status.
     * PUT /admin/roadmap/{roadmapItem}/status
     */
    public function updateStatus(Request $request, string $roadmapItem): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $status = RoadmapStatus::tryFrom($validated['status']);

        if ($status === null) {
            return $this->error('Invalid status provided', 422);
        }

        try {
            $item = $this->roadmapService->getItem($roadmapItem);
            $item = $this->roadmapService->updateStatus($item, $status);

            return $this->success(
                RoadmapItemData::fromModel($item)->toArray(),
                'Roadmap item status updated successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Roadmap item not found');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Delete a roadmap item.
     * DELETE /admin/roadmap/{roadmapItem}
     */
    public function destroy(Request $request, string $roadmapItem): JsonResponse
    {
        try {
            $item = $this->roadmapService->getItem($roadmapItem);
            $this->roadmapService->delete($item);

            return $this->success(null, 'Roadmap item deleted successfully');
        } catch (ModelNotFoundException) {
            return $this->notFound('Roadmap item not found');
        }
    }
}
