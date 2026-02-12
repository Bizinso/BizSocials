<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Workspace;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Workspace\Workspace;
use App\Services\Workspace\WorkspaceDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkspaceDashboardController extends Controller
{
    public function __construct(
        private readonly WorkspaceDashboardService $dashboardService,
    ) {}

    /**
     * Get workspace dashboard statistics.
     */
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $stats = $this->dashboardService->getStats($workspace);

        return $this->success($stats);
    }
}
