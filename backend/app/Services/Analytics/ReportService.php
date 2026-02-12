<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\Analytics\ReportStatus;
use App\Enums\Analytics\ReportType;
use App\Models\Analytics\AnalyticsReport;
use App\Models\User;
use App\Services\BaseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

/**
 * ReportService
 *
 * Handles analytics report creation, generation, and management.
 * Handles:
 * - Report creation and configuration
 * - Report generation processing
 * - Report file download URLs
 * - Report listing and filtering
 * - Report deletion and cleanup
 */
final class ReportService extends BaseService
{
    /**
     * Default report expiration in days.
     */
    private const DEFAULT_EXPIRATION_DAYS = 30;

    /**
     * Storage disk for reports.
     */
    private const STORAGE_DISK = 's3';

    /**
     * Storage directory for reports.
     */
    private const STORAGE_PATH = 'reports';

    public function __construct(
        private readonly AnalyticsService $analyticsService,
        private readonly ContentPerformanceService $contentPerformanceService,
    ) {}

    /**
     * Create a new analytics report.
     *
     * Creates a report record in pending status ready for generation.
     *
     * @param string $workspaceId The workspace UUID
     * @param User $user The user creating the report
     * @param array<string, mixed> $data Report configuration data
     * @return AnalyticsReport The created report
     */
    public function createReport(string $workspaceId, User $user, array $data): AnalyticsReport
    {
        $reportType = ReportType::from($data['report_type']);
        $dateFrom = Carbon::parse($data['date_from']);
        $dateTo = Carbon::parse($data['date_to']);

        $report = AnalyticsReport::create([
            'workspace_id' => $workspaceId,
            'created_by_user_id' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'report_type' => $reportType,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'social_account_ids' => $data['social_account_ids'] ?? null,
            'metrics' => $data['metrics'] ?? null,
            'filters' => $data['filters'] ?? null,
            'status' => ReportStatus::PENDING,
            'file_format' => $data['file_format'] ?? 'pdf',
        ]);

        $this->log('Report created', [
            'report_id' => $report->id,
            'workspace_id' => $workspaceId,
            'user_id' => $user->id,
            'report_type' => $reportType->value,
        ]);

        return $report;
    }

    /**
     * Generate the report content and save to file.
     *
     * Processes the report based on its type and configuration.
     * Updates the report status upon completion or failure.
     *
     * @param AnalyticsReport $report The report to generate
     * @return void
     */
    public function generateReport(AnalyticsReport $report): void
    {
        try {
            $report->markAsProcessing();

            $this->log('Report generation started', [
                'report_id' => $report->id,
                'report_type' => $report->report_type->value,
            ]);

            // Gather report data based on type
            $reportData = $this->gatherReportData($report);

            // Generate the report file
            $filePath = $this->generateReportFile($report, $reportData);
            $fileSize = Storage::disk(self::STORAGE_DISK)->size($filePath);

            // Calculate expiration date
            $expiresAt = now()->addDays(self::DEFAULT_EXPIRATION_DAYS);

            $report->markAsCompleted($filePath, $fileSize, $expiresAt);

            $this->log('Report generation completed', [
                'report_id' => $report->id,
                'file_path' => $filePath,
                'file_size' => $fileSize,
            ]);
        } catch (\Throwable $e) {
            $report->markAsFailed();

            $this->log('Report generation failed', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
            ], 'error');

            throw $e;
        }
    }

    /**
     * Get the download URL for a report.
     *
     * Returns a temporary signed URL for downloading the report file.
     *
     * @param AnalyticsReport $report The report to get URL for
     * @return string The download URL
     * @throws \RuntimeException If report is not available
     */
    public function getReportDownloadUrl(AnalyticsReport $report): string
    {
        if (!$report->isAvailable()) {
            throw new \RuntimeException('Report is not available for download.');
        }

        if ($report->file_path === null) {
            throw new \RuntimeException('Report file path is not set.');
        }

        // Generate a temporary signed URL (valid for 1 hour)
        $url = Storage::disk(self::STORAGE_DISK)->temporaryUrl(
            $report->file_path,
            now()->addHour()
        );

        $this->log('Report download URL generated', [
            'report_id' => $report->id,
        ]);

        return $url;
    }

    /**
     * List reports for a workspace with pagination and filtering.
     *
     * Available filters:
     * - status: ReportStatus value
     * - report_type: ReportType value
     * - created_by: User UUID
     * - from_date: Filter by creation date from
     * - to_date: Filter by creation date to
     * - search: Search in name and description
     * - per_page: Items per page (max 100)
     * - sort_by: Column to sort by
     * - sort_dir: Sort direction (asc/desc)
     *
     * @param string $workspaceId The workspace UUID
     * @param array<string, mixed> $filters Optional filters
     * @return LengthAwarePaginator Paginated list of reports
     */
    public function listReports(string $workspaceId, array $filters = []): LengthAwarePaginator
    {
        $query = AnalyticsReport::forWorkspace($workspaceId)
            ->with('createdBy');

        // Filter by status
        if (!empty($filters['status'])) {
            $status = ReportStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->withStatus($status);
            }
        }

        // Filter by report type
        if (!empty($filters['report_type'])) {
            $type = ReportType::tryFrom($filters['report_type']);
            if ($type !== null) {
                $query->ofType($type);
            }
        }

        // Filter by creator
        if (!empty($filters['created_by'])) {
            $query->forUser($filters['created_by']);
        }

        // Filter by creation date range
        if (!empty($filters['from_date'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['from_date'])->startOfDay());
        }

        if (!empty($filters['to_date'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['to_date'])->endOfDay());
        }

        // Search in name and description
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by available only
        if (!empty($filters['available_only']) && $filters['available_only'] === true) {
            $query->available();
        }

        // Pagination
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    /**
     * Delete a report and its associated file.
     *
     * @param AnalyticsReport $report The report to delete
     * @return void
     */
    public function deleteReport(AnalyticsReport $report): void
    {
        $reportId = $report->id;

        // Delete the file if it exists
        if ($report->file_path !== null) {
            $this->deleteReportFile($report->file_path);
        }

        $report->delete();

        $this->log('Report deleted', [
            'report_id' => $reportId,
        ]);
    }

    /**
     * Clean up expired reports.
     *
     * Finds and processes reports that have expired.
     * Deletes report files and updates status to expired.
     *
     * @return int Number of reports cleaned up
     */
    public function cleanupExpiredReports(): int
    {
        $expiredReports = AnalyticsReport::completed()
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;

        foreach ($expiredReports as $report) {
            try {
                // Delete the file
                if ($report->file_path !== null) {
                    $this->deleteReportFile($report->file_path);
                }

                $report->markAsExpired();
                $count++;
            } catch (\Throwable $e) {
                $this->log('Failed to cleanup expired report', [
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                ], 'warning');
            }
        }

        $this->log('Expired reports cleanup completed', [
            'cleaned_up_count' => $count,
        ]);

        return $count;
    }

    /**
     * Gather report data based on report type and configuration.
     *
     * @param AnalyticsReport $report The report to gather data for
     * @return array<string, mixed> The gathered report data
     */
    private function gatherReportData(AnalyticsReport $report): array
    {
        $workspaceId = $report->workspace_id;
        $dateFrom = $report->date_from;
        $dateTo = $report->date_to;

        $data = [
            'report' => [
                'id' => $report->id,
                'name' => $report->name,
                'description' => $report->description,
                'type' => $report->report_type->value,
                'type_label' => $report->report_type->label(),
                'date_range' => [
                    'from' => $dateFrom->toDateString(),
                    'to' => $dateTo->toDateString(),
                    'days' => $report->getDateRangeDays(),
                ],
                'generated_at' => now()->toIso8601String(),
            ],
        ];

        switch ($report->report_type) {
            case ReportType::PERFORMANCE:
                $data['dashboard_metrics'] = $this->analyticsService->getDashboardMetrics(
                    $workspaceId,
                    $dateFrom->diffInDays($dateTo) . 'd'
                );
                $data['engagement_trend'] = $this->analyticsService->getEngagementTrend(
                    $workspaceId,
                    $dateFrom,
                    $dateTo
                );
                $data['platform_metrics'] = $this->analyticsService->getMetricsByPlatform(
                    $workspaceId,
                    $dateFrom,
                    $dateTo
                );
                break;

            case ReportType::ENGAGEMENT:
                $data['engagement_trend'] = $this->analyticsService->getEngagementTrend(
                    $workspaceId,
                    $dateFrom,
                    $dateTo
                );
                $data['top_posts'] = $this->contentPerformanceService
                    ->getTopPosts($workspaceId, 20, 'engagement')
                    ->toArray();
                $data['by_platform'] = $this->contentPerformanceService->getPerformanceByPlatform(
                    $workspaceId,
                    $dateFrom,
                    $dateTo
                );
                break;

            case ReportType::GROWTH:
                $data['follower_trend'] = $this->analyticsService->getFollowerGrowthTrend(
                    $workspaceId,
                    $dateFrom,
                    $dateTo
                );
                $data['dashboard_metrics'] = $this->analyticsService->getDashboardMetrics(
                    $workspaceId,
                    $dateFrom->diffInDays($dateTo) . 'd'
                );
                break;

            case ReportType::CONTENT:
                $data['performance_overview'] = $this->contentPerformanceService->getPerformanceOverview(
                    $workspaceId,
                    $dateFrom->diffInDays($dateTo) . 'd'
                );
                $data['by_content_type'] = $this->contentPerformanceService->getPerformanceByContentType(
                    $workspaceId,
                    $dateFrom,
                    $dateTo
                );
                $data['top_posts'] = $this->contentPerformanceService
                    ->getTopPosts($workspaceId, 20)
                    ->toArray();
                $data['best_posting_times'] = $this->contentPerformanceService->getBestPostingTimes($workspaceId);
                break;

            case ReportType::AUDIENCE:
                $data['follower_trend'] = $this->analyticsService->getFollowerGrowthTrend(
                    $workspaceId,
                    $dateFrom,
                    $dateTo
                );
                $data['platform_metrics'] = $this->analyticsService->getMetricsByPlatform(
                    $workspaceId,
                    $dateFrom,
                    $dateTo
                );
                break;

            case ReportType::CUSTOM:
                // Custom reports include all available metrics
                $data['dashboard_metrics'] = $this->analyticsService->getDashboardMetrics(
                    $workspaceId,
                    $dateFrom->diffInDays($dateTo) . 'd'
                );
                $data['engagement_trend'] = $this->analyticsService->getEngagementTrend(
                    $workspaceId,
                    $dateFrom,
                    $dateTo
                );
                $data['follower_trend'] = $this->analyticsService->getFollowerGrowthTrend(
                    $workspaceId,
                    $dateFrom,
                    $dateTo
                );
                $data['platform_metrics'] = $this->analyticsService->getMetricsByPlatform(
                    $workspaceId,
                    $dateFrom,
                    $dateTo
                );
                $data['content_performance'] = $this->contentPerformanceService->getPerformanceOverview(
                    $workspaceId,
                    $dateFrom->diffInDays($dateTo) . 'd'
                );
                $data['top_posts'] = $this->contentPerformanceService
                    ->getTopPosts($workspaceId, 10)
                    ->toArray();
                break;
        }

        return $data;
    }

    /**
     * Generate the report file.
     *
     * Creates the report file in the specified format and returns the file path.
     *
     * @param AnalyticsReport $report The report configuration
     * @param array<string, mixed> $data The report data
     * @return string The generated file path
     */
    private function generateReportFile(AnalyticsReport $report, array $data): string
    {
        $fileName = sprintf(
            '%s/%s_%s_%s.%s',
            self::STORAGE_PATH,
            $report->workspace_id,
            $report->id,
            now()->format('YmdHis'),
            $report->file_format
        );

        $content = match ($report->file_format) {
            'pdf' => $this->generatePdfContent($report, $data),
            'json' => json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            'csv' => $this->generateCsvContent($data),
            default => $this->generatePdfContent($report, $data),
        };

        Storage::disk(self::STORAGE_DISK)->put($fileName, $content);

        return $fileName;
    }

    /**
     * Generate PDF content from report data using DomPDF.
     *
     * @param AnalyticsReport $report The report model
     * @param array<string, mixed> $data The report data
     * @return string PDF binary content
     */
    private function generatePdfContent(AnalyticsReport $report, array $data): string
    {
        $report->loadMissing('workspace');

        $viewData = [
            'report' => $data['report'],
            'workspaceName' => $report->workspace?->name ?? 'Workspace',
            'dashboardMetrics' => $data['dashboard_metrics'] ?? [],
            'engagementTrend' => $data['engagement_trend'] ?? [],
            'platformMetrics' => $data['platform_metrics'] ?? [],
            'topPosts' => $data['top_posts'] ?? [],
            'byContentType' => $data['by_content_type'] ?? [],
            'followerTrend' => $data['follower_trend'] ?? [],
        ];

        $pdf = Pdf::loadView('pdf.analytics-report', $viewData)
            ->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    /**
     * Generate CSV content from report data.
     *
     * @param array<string, mixed> $data The report data
     * @return string CSV content
     */
    private function generateCsvContent(array $data): string
    {
        $lines = [];

        // Add report header
        $lines[] = 'Report: ' . ($data['report']['name'] ?? 'Analytics Report');
        $lines[] = 'Type: ' . ($data['report']['type_label'] ?? '');
        $lines[] = 'Date Range: ' . ($data['report']['date_range']['from'] ?? '') . ' to ' . ($data['report']['date_range']['to'] ?? '');
        $lines[] = 'Generated: ' . ($data['report']['generated_at'] ?? '');
        $lines[] = '';

        // Add metrics if available
        if (isset($data['dashboard_metrics']['metrics'])) {
            $lines[] = 'Key Metrics';
            $lines[] = 'Metric,Value';
            foreach ($data['dashboard_metrics']['metrics'] as $key => $value) {
                $lines[] = $key . ',' . (is_numeric($value) ? $value : '"' . $value . '"');
            }
            $lines[] = '';
        }

        // Add engagement trend if available
        if (isset($data['engagement_trend']) && is_array($data['engagement_trend'])) {
            $lines[] = 'Engagement Trend';
            $lines[] = 'Date,Engagements,Likes,Comments,Shares,Saves';
            foreach ($data['engagement_trend'] as $day) {
                $lines[] = implode(',', [
                    $day['date'] ?? '',
                    $day['engagements'] ?? 0,
                    $day['likes'] ?? 0,
                    $day['comments'] ?? 0,
                    $day['shares'] ?? 0,
                    $day['saves'] ?? 0,
                ]);
            }
            $lines[] = '';
        }

        // Add platform metrics if available
        if (isset($data['platform_metrics']) && is_array($data['platform_metrics'])) {
            $lines[] = 'Platform Metrics';
            $lines[] = 'Platform,Posts,Impressions,Reach,Engagements,Engagement Rate';
            foreach ($data['platform_metrics'] as $platform) {
                $lines[] = implode(',', [
                    $platform['platform'] ?? '',
                    $platform['posts_count'] ?? 0,
                    $platform['impressions'] ?? 0,
                    $platform['reach'] ?? 0,
                    $platform['engagements'] ?? 0,
                    $platform['engagement_rate'] ?? 0,
                ]);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Delete a report file from storage.
     *
     * @param string $filePath The file path to delete
     * @return void
     */
    private function deleteReportFile(string $filePath): void
    {
        if (Storage::disk(self::STORAGE_DISK)->exists($filePath)) {
            Storage::disk(self::STORAGE_DISK)->delete($filePath);
        }
    }
}
