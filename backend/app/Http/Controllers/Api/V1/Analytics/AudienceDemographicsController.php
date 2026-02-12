<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use App\Services\Analytics\AudienceDemographicsService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AudienceDemographicsController extends Controller
{
    public function __construct(
        private readonly AudienceDemographicsService $demographicsService,
    ) {}

    /**
     * List all demographics for a workspace.
     * GET /api/v1/workspaces/{workspace}/analytics/demographics
     *
     * @throws AuthorizationException
     */
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $demographics = \App\Models\Analytics\AudienceDemographic::query()
            ->whereHas('socialAccount', function ($query) use ($workspace): void {
                $query->where('workspace_id', $workspace->id);
            })
            ->with('socialAccount')
            ->orderByDesc('snapshot_date')
            ->get();

        return $this->success($demographics, 'Demographics retrieved successfully');
    }

    /**
     * Get the latest demographic snapshot for a social account.
     * GET /api/v1/workspaces/{workspace}/demographics/latest/{socialAccount}
     *
     * @throws AuthorizationException
     */
    public function latest(Request $request, Workspace $workspace, SocialAccount $socialAccount): JsonResponse
    {
        $this->authorize('view', $workspace);

        $demographic = $this->demographicsService->getLatest($socialAccount->id);

        if ($demographic === null) {
            return $this->success(null, 'No demographic data available');
        }

        return $this->success($demographic, 'Latest demographics retrieved successfully');
    }

    /**
     * Get demographic history for a social account.
     * GET /api/v1/workspaces/{workspace}/demographics/history/{socialAccount}
     *
     * Query parameters:
     * - days: Number of days of history (default: 30)
     *
     * @throws AuthorizationException
     */
    public function history(Request $request, Workspace $workspace, SocialAccount $socialAccount): JsonResponse
    {
        $this->authorize('view', $workspace);

        $days = (int) $request->query('days', 30);
        $history = $this->demographicsService->getHistory($socialAccount->id, $days);

        return $this->success($history, 'Demographic history retrieved successfully');
    }

    /**
     * Get aggregated audience overview for the workspace.
     * GET /api/v1/workspaces/{workspace}/demographics/overview
     *
     * @throws AuthorizationException
     */
    public function workspaceOverview(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $overview = $this->demographicsService->getWorkspaceOverview($workspace->id);

        return $this->success($overview, 'Workspace audience overview retrieved successfully');
    }
}
