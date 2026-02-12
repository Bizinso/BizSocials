<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Data\Analytics\AnalyticsReportData;
use App\Enums\Analytics\ReportType;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Analytics\CreateReportRequest;
use App\Jobs\Analytics\GenerateReportJob;
use App\Models\Analytics\AnalyticsReport;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportController extends Controller
{
    /**
     * List analytics reports for a workspace.
     * GET /api/v1/workspaces/{workspace}/analytics/reports
     *
     * Query parameters:
     * - status: Filter by status (pending, processing, completed, failed, expired)
     * - report_type: Filter by report type
     * - per_page: Number of items per page (default: 20, max: 100)
     * - sort_by: Column to sort by (default: created_at)
     * - sort_dir: Sort direction (default: desc)
     *
     * @throws AuthorizationException
     */
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $perPage = min((int) $request->query('per_page', 20), 100);
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');

        $query = AnalyticsReport::forWorkspace($workspace->id)
            ->with('createdBy');

        // Apply status filter
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Apply report type filter
        if ($reportType = $request->query('report_type')) {
            $query->where('report_type', $reportType);
        }

        // Apply sorting
        $allowedSortColumns = ['created_at', 'name', 'date_from', 'date_to', 'status'];
        if (in_array($sortBy, $allowedSortColumns, true)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $reports = $query->paginate($perPage);

        $transformedItems = collect($reports->items())->map(
            fn (AnalyticsReport $report): array => AnalyticsReportData::fromModel($report)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Reports retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
                'from' => $reports->firstItem(),
                'to' => $reports->lastItem(),
            ],
            'links' => [
                'first' => $reports->url(1),
                'last' => $reports->url($reports->lastPage()),
                'prev' => $reports->previousPageUrl(),
                'next' => $reports->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Create a new analytics report.
     * POST /api/v1/workspaces/{workspace}/analytics/reports
     *
     * @throws AuthorizationException
     */
    public function store(CreateReportRequest $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('update', $workspace);

        /** @var User $user */
        $user = $request->user();

        $report = AnalyticsReport::createForWorkspace(
            workspace: $workspace,
            user: $user,
            name: $request->input('name'),
            reportType: ReportType::from($request->input('report_type')),
            dateFrom: Carbon::parse($request->input('date_from')),
            dateTo: Carbon::parse($request->input('date_to')),
            socialAccountIds: $request->input('social_account_ids'),
            metrics: $request->input('metrics'),
            filters: $request->input('filters'),
            fileFormat: $request->input('file_format', 'pdf'),
            description: $request->input('description'),
        );

        GenerateReportJob::dispatch($report->id);

        return $this->created(
            AnalyticsReportData::fromModel($report)->toArray(),
            'Report creation initiated successfully'
        );
    }

    /**
     * Get a specific analytics report.
     * GET /api/v1/workspaces/{workspace}/analytics/reports/{report}
     *
     * @throws AuthorizationException
     */
    public function show(Workspace $workspace, AnalyticsReport $report): JsonResponse
    {
        $this->authorize('view', $workspace);

        // Ensure report belongs to workspace
        if ($report->workspace_id !== $workspace->id) {
            return $this->notFound('Report not found');
        }

        return $this->success(
            AnalyticsReportData::fromModel($report)->toArray(),
            'Report retrieved successfully'
        );
    }

    /**
     * Download a generated analytics report.
     * GET /api/v1/workspaces/{workspace}/analytics/reports/{report}/download
     *
     * @throws AuthorizationException
     */
    public function download(Workspace $workspace, AnalyticsReport $report): JsonResponse|StreamedResponse
    {
        $this->authorize('view', $workspace);

        // Ensure report belongs to workspace
        if ($report->workspace_id !== $workspace->id) {
            return $this->notFound('Report not found');
        }

        // Check if report is available for download
        if (!$report->isAvailable()) {
            if ($report->isPending() || $report->isProcessing()) {
                return $this->error(
                    'Report is still being generated. Please try again later.',
                    400
                );
            }

            if ($report->isFailed()) {
                return $this->error(
                    'Report generation failed. Please create a new report.',
                    400
                );
            }

            if ($report->isExpired()) {
                return $this->error(
                    'Report has expired. Please create a new report.',
                    410
                );
            }

            return $this->error(
                'Report is not available for download.',
                400
            );
        }

        // Check if file exists
        if (!Storage::disk('local')->exists($report->file_path)) {
            return $this->error(
                'Report file not found. Please create a new report.',
                404
            );
        }

        // Generate download filename
        $extension = pathinfo($report->file_path, PATHINFO_EXTENSION);
        $filename = sprintf(
            '%s_%s_%s.%s',
            str_replace(' ', '_', $report->name),
            $report->date_from->format('Ymd'),
            $report->date_to->format('Ymd'),
            $extension
        );

        return Storage::disk('local')->download($report->file_path, $filename);
    }

    /**
     * Delete an analytics report.
     * DELETE /api/v1/workspaces/{workspace}/analytics/reports/{report}
     *
     * @throws AuthorizationException
     */
    public function destroy(Workspace $workspace, AnalyticsReport $report): JsonResponse
    {
        $this->authorize('update', $workspace);

        // Ensure report belongs to workspace
        if ($report->workspace_id !== $workspace->id) {
            return $this->notFound('Report not found');
        }

        // Delete the file if it exists
        if ($report->file_path !== null && Storage::disk('local')->exists($report->file_path)) {
            Storage::disk('local')->delete($report->file_path);
        }

        $report->delete();

        return $this->success(
            null,
            'Report deleted successfully'
        );
    }
}
