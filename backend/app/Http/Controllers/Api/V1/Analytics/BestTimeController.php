<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Workspace\Workspace;
use App\Services\Analytics\BestTimeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BestTimeController extends Controller
{
    public function __construct(
        private readonly BestTimeService $bestTimeService,
    ) {}

    /**
     * Analyze and return a heatmap of engagement scores by day and hour.
     * GET /api/v1/workspaces/{workspace}/best-times
     *
     * Query parameters:
     * - platform: Filter by platform (optional)
     *
     * @throws AuthorizationException
     */
    public function analyze(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $platform = $request->query('platform');
        $heatmap = $this->bestTimeService->analyze($workspace->id, $platform);

        return $this->success(
            [
                'platform' => $platform,
                'heatmap' => $heatmap,
            ],
            'Best time analysis retrieved successfully'
        );
    }

    /**
     * Get top recommended time slots.
     * GET /api/v1/workspaces/{workspace}/best-times/slots
     *
     * Query parameters:
     * - platform: Filter by platform (optional)
     * - count: Number of slots to return (default: 5)
     *
     * @throws AuthorizationException
     */
    public function recommendedSlots(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $platform = $request->query('platform');
        $count = min((int) $request->query('count', 5), 10);

        $slots = $this->bestTimeService->getRecommendedSlots($workspace->id, $platform, $count);

        return $this->success(
            [
                'platform' => $platform,
                'slots' => $slots,
            ],
            'Recommended time slots retrieved successfully'
        );
    }
}
