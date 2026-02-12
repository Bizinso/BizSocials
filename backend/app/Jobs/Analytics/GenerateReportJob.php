<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use App\Enums\Analytics\ReportStatus;
use App\Enums\Notification\NotificationType;
use App\Models\Analytics\AnalyticsAggregate;
use App\Models\Analytics\AnalyticsReport;
use App\Models\Notification\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * GenerateReportJob
 *
 * Generates analytics reports in various formats (PDF, CSV, XLSX).
 * Processes report requests asynchronously and notifies users on completion.
 *
 * Features:
 * - Supports multiple export formats
 * - Generates downloadable report files
 * - Sends notification when report is ready
 * - Handles failures gracefully with status updates
 */
final class GenerateReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int>
     */
    public array $backoff = [60, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $reportId,
    ) {
        $this->onQueue('reports');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $report = AnalyticsReport::find($this->reportId);

        if ($report === null) {
            Log::warning('[GenerateReportJob] Report not found', [
                'report_id' => $this->reportId,
            ]);

            return;
        }

        // Skip if already completed or processing
        if ($report->status === ReportStatus::COMPLETED) {
            Log::info('[GenerateReportJob] Report already completed', [
                'report_id' => $this->reportId,
            ]);

            return;
        }

        Log::info('[GenerateReportJob] Starting report generation', [
            'report_id' => $this->reportId,
            'report_type' => $report->report_type->value,
            'format' => $report->file_format,
        ]);

        // Update status to processing
        $report->markAsProcessing();

        try {
            // Gather report data
            $data = $this->gatherReportData($report);

            // Generate file based on format
            $filePath = $this->generateFile($report, $data);

            // Update report with file info
            $fileSize = Storage::disk('s3')->size($filePath);

            $report->update([
                'status' => ReportStatus::COMPLETED,
                'file_path' => $filePath,
                'file_size_bytes' => $fileSize,
                'completed_at' => now(),
                'expires_at' => now()->addDays(7),
            ]);

            // Send notification
            $this->notifyUser($report);

            Log::info('[GenerateReportJob] Report generation completed', [
                'report_id' => $this->reportId,
                'file_path' => $filePath,
                'file_size' => $fileSize,
            ]);
        } catch (\Throwable $e) {
            $report->markAsFailed($e->getMessage());

            throw $e;
        }
    }

    /**
     * Gather the data for the report.
     *
     * @return array<string, mixed>
     */
    private function gatherReportData(AnalyticsReport $report): array
    {
        $aggregates = AnalyticsAggregate::query()
            ->forWorkspace($report->workspace_id)
            ->inDateRange($report->date_from, $report->date_to);

        // Filter by social accounts if specified
        if ($report->social_account_ids !== null && count($report->social_account_ids) > 0) {
            $aggregates->whereIn('social_account_id', $report->social_account_ids);
        }

        $results = $aggregates->get();

        // Calculate totals
        $totals = [
            'impressions' => $results->sum('impressions'),
            'reach' => $results->sum('reach'),
            'engagements' => $results->sum('engagements'),
            'likes' => $results->sum('likes'),
            'comments' => $results->sum('comments'),
            'shares' => $results->sum('shares'),
            'saves' => $results->sum('saves'),
            'clicks' => $results->sum('clicks'),
            'video_views' => $results->sum('video_views'),
            'posts_count' => $results->sum('posts_count'),
        ];

        // Calculate average engagement rate
        $totalImpressions = $totals['impressions'];
        $avgEngagementRate = $totalImpressions > 0
            ? ($totals['engagements'] / $totalImpressions) * 100
            : 0;

        return [
            'report' => [
                'name' => $report->name,
                'type' => $report->report_type->value,
                'type_label' => $report->report_type->label(),
                'description' => $report->description,
                'date_from' => $report->date_from->format('Y-m-d'),
                'date_to' => $report->date_to->format('Y-m-d'),
                'date_range' => [
                    'from' => $report->date_from->format('M j, Y'),
                    'to' => $report->date_to->format('M j, Y'),
                ],
                'generated_at' => now()->toIso8601String(),
            ],
            'totals' => $totals,
            'avg_engagement_rate' => round($avgEngagementRate, 2),
            'daily_breakdown' => $results->groupBy(fn ($item) => $item->date->format('Y-m-d'))
                ->map(fn ($group) => [
                    'impressions' => $group->sum('impressions'),
                    'reach' => $group->sum('reach'),
                    'engagements' => $group->sum('engagements'),
                    'posts_count' => $group->sum('posts_count'),
                ])
                ->toArray(),
            'by_account' => $results->groupBy('social_account_id')
                ->filter(fn ($group, $key) => $key !== null)
                ->map(fn ($group) => [
                    'impressions' => $group->sum('impressions'),
                    'reach' => $group->sum('reach'),
                    'engagements' => $group->sum('engagements'),
                    'posts_count' => $group->sum('posts_count'),
                ])
                ->toArray(),
        ];
    }

    /**
     * Generate the report file.
     */
    private function generateFile(AnalyticsReport $report, array $data): string
    {
        $filename = sprintf(
            'reports/%s/%s-%s.%s',
            $report->workspace_id,
            Str::slug($report->name),
            Str::random(8),
            $report->file_format
        );

        $content = match ($report->file_format) {
            'pdf' => $this->generatePdf($report, $data),
            'csv' => $this->generateCsv($data),
            'json' => json_encode($data, JSON_PRETTY_PRINT),
            default => $this->generatePdf($report, $data),
        };

        Storage::disk('s3')->put($filename, $content);

        return $filename;
    }

    /**
     * Generate CSV content.
     */
    private function generateCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');

        // Headers
        fputcsv($output, ['Date', 'Impressions', 'Reach', 'Engagements', 'Posts']);

        // Data rows
        foreach ($data['daily_breakdown'] as $date => $metrics) {
            fputcsv($output, [
                $date,
                $metrics['impressions'],
                $metrics['reach'],
                $metrics['engagements'],
                $metrics['posts_count'],
            ]);
        }

        // Add totals row
        fputcsv($output, []);
        fputcsv($output, [
            'TOTAL',
            $data['totals']['impressions'],
            $data['totals']['reach'],
            $data['totals']['engagements'],
            $data['totals']['posts_count'],
        ]);

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Generate a PDF report using DomPDF.
     */
    private function generatePdf(AnalyticsReport $report, array $data): string
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
     * Notify the user that the report is ready.
     */
    private function notifyUser(AnalyticsReport $report): void
    {
        Notification::create([
            'user_id' => $report->created_by_user_id,
            'tenant_id' => $report->workspace->tenant_id,
            'type' => NotificationType::REPORT_READY,
            'title' => 'Your Report is Ready',
            'message' => sprintf(
                'Your analytics report "%s" has been generated and is ready for download.',
                $report->name
            ),
            'data' => [
                'report_id' => $report->id,
                'report_name' => $report->name,
                'workspace_id' => $report->workspace_id,
            ],
            'action_url' => sprintf(
                '/workspaces/%s/reports/%s/download',
                $report->workspace_id,
                $report->id
            ),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('[GenerateReportJob] Job failed', [
            'report_id' => $this->reportId,
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);

        // Update report status
        $report = AnalyticsReport::find($this->reportId);
        if ($report !== null) {
            $report->markAsFailed($exception?->getMessage() ?? 'Unknown error');
        }
    }
}
