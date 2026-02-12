<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Feedback;

use App\Data\Feedback\RoadmapItemData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Feedback\RoadmapItem;
use App\Services\Feedback\RoadmapService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RoadmapController extends Controller
{
    public function __construct(
        private readonly RoadmapService $roadmapService,
    ) {}

    /**
     * Get public roadmap grouped by status.
     * GET /roadmap
     */
    public function index(Request $request): JsonResponse
    {
        $groupedItems = $this->roadmapService->getPublicRoadmap();

        // Transform each group
        $transformedGroups = $groupedItems->map(function ($items, $status) {
            return $items->map(
                fn (RoadmapItem $item) => RoadmapItemData::fromModel($item)->toArray()
            );
        });

        return $this->success([
            'grouped' => $transformedGroups,
            'statuses' => [
                'considering' => 'Considering',
                'planned' => 'Planned',
                'in_progress' => 'In Progress',
                'beta' => 'Beta',
                'shipped' => 'Shipped',
            ],
        ], 'Roadmap retrieved successfully');
    }

    /**
     * Get a specific roadmap item.
     * GET /roadmap/{roadmapItem}
     */
    public function show(Request $request, string $roadmapItem): JsonResponse
    {
        try {
            $item = $this->roadmapService->getPublicItem($roadmapItem);

            return $this->success(
                RoadmapItemData::fromModel($item)->toArray(),
                'Roadmap item retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Roadmap item not found');
        }
    }
}
