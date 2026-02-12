<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Analytics\ScheduledReport;
use App\Models\Workspace\Workspace;
use App\Services\Analytics\ScheduledReportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ScheduledReportController extends Controller
{
    public function __construct(
        private readonly ScheduledReportService $reportService,
    ) {}

    /**
     * List scheduled reports for the workspace.
     * GET /api/v1/workspaces/{workspace}/scheduled-reports
     *
     * @throws AuthorizationException
     */
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $reports = $this->reportService->list($workspace->id);

        return $this->paginated($reports, 'Scheduled reports retrieved successfully');
    }

    /**
     * Create a new scheduled report.
     * POST /api/v1/workspaces/{workspace}/scheduled-reports
     *
     * @throws AuthorizationException
     */
    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'report_type' => 'required|string|max:50',
            'frequency' => 'required|string|in:weekly,monthly,quarterly',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'required|email',
            'parameters' => 'nullable|array',
        ]);

        $report = $this->reportService->create($workspace->id, $validated);

        return $this->created($report, 'Scheduled report created successfully');
    }

    /**
     * Show a specific scheduled report.
     * GET /api/v1/workspaces/{workspace}/scheduled-reports/{report}
     *
     * @throws AuthorizationException
     */
    public function show(Request $request, Workspace $workspace, ScheduledReport $scheduledReport): JsonResponse
    {
        $this->authorize('view', $workspace);

        return $this->success($scheduledReport, 'Scheduled report retrieved successfully');
    }

    /**
     * Update a scheduled report.
     * PUT /api/v1/workspaces/{workspace}/scheduled-reports/{report}
     *
     * @throws AuthorizationException
     */
    public function update(Request $request, Workspace $workspace, ScheduledReport $scheduledReport): JsonResponse
    {
        $this->authorize('view', $workspace);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'report_type' => 'sometimes|string|max:50',
            'frequency' => 'sometimes|string|in:weekly,monthly,quarterly',
            'recipients' => 'sometimes|array|min:1',
            'recipients.*' => 'required|email',
            'parameters' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $report = $this->reportService->update($scheduledReport, $validated);

        return $this->success($report, 'Scheduled report updated successfully');
    }

    /**
     * Delete a scheduled report.
     * DELETE /api/v1/workspaces/{workspace}/scheduled-reports/{report}
     *
     * @throws AuthorizationException
     */
    public function destroy(Request $request, Workspace $workspace, ScheduledReport $scheduledReport): JsonResponse
    {
        $this->authorize('view', $workspace);

        $this->reportService->delete($scheduledReport);

        return $this->noContent();
    }

    /**
     * Generate a report on demand.
     * POST /api/v1/workspaces/{workspace}/scheduled-reports/{report}/generate
     *
     * @throws AuthorizationException
     */
    public function generate(Request $request, Workspace $workspace, ScheduledReport $scheduledReport): JsonResponse
    {
        $this->authorize('view', $workspace);

        $data = $this->reportService->generateReport($scheduledReport);

        return $this->success($data, 'Report generated successfully');
    }
}
