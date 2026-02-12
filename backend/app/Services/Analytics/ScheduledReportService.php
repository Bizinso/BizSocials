<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Analytics\ScheduledReport;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * ScheduledReportService
 *
 * Manages scheduled report CRUD operations and report generation.
 */
final class ScheduledReportService extends BaseService
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
    ) {}

    /**
     * List scheduled reports for a workspace.
     */
    public function list(string $workspaceId): LengthAwarePaginator
    {
        return ScheduledReport::forWorkspace($workspaceId)
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    /**
     * Create a new scheduled report.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(string $workspaceId, array $data): ScheduledReport
    {
        return $this->transaction(function () use ($workspaceId, $data): ScheduledReport {
            $nextSendAt = $this->calculateNextSendAt($data['frequency'] ?? 'weekly');

            $report = ScheduledReport::create([
                'workspace_id' => $workspaceId,
                'name' => $data['name'],
                'report_type' => $data['report_type'],
                'frequency' => $data['frequency'] ?? 'weekly',
                'recipients' => $data['recipients'],
                'parameters' => $data['parameters'] ?? null,
                'next_send_at' => $nextSendAt,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->log('Scheduled report created', [
                'workspace_id' => $workspaceId,
                'report_id' => $report->id,
                'name' => $report->name,
            ]);

            return $report;
        });
    }

    /**
     * Update an existing scheduled report.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ScheduledReport $report, array $data): ScheduledReport
    {
        return $this->transaction(function () use ($report, $data): ScheduledReport {
            $updateData = array_filter([
                'name' => $data['name'] ?? null,
                'report_type' => $data['report_type'] ?? null,
                'frequency' => $data['frequency'] ?? null,
                'recipients' => $data['recipients'] ?? null,
                'parameters' => array_key_exists('parameters', $data) ? $data['parameters'] : null,
                'is_active' => $data['is_active'] ?? null,
            ], fn ($value) => $value !== null);

            // Recalculate next send if frequency changed
            if (isset($data['frequency']) && $data['frequency'] !== $report->frequency) {
                $updateData['next_send_at'] = $this->calculateNextSendAt($data['frequency']);
            }

            $report->update($updateData);

            $this->log('Scheduled report updated', [
                'report_id' => $report->id,
            ]);

            return $report;
        });
    }

    /**
     * Delete a scheduled report.
     */
    public function delete(ScheduledReport $report): void
    {
        $this->transaction(function () use ($report): void {
            $reportId = $report->id;
            $report->delete();

            $this->log('Scheduled report deleted', [
                'report_id' => $reportId,
            ]);
        });
    }

    /**
     * Generate report data for a scheduled report.
     *
     * @return array<string, mixed>
     */
    public function generateReport(ScheduledReport $report): array
    {
        $period = match ($report->frequency) {
            'monthly' => '30d',
            'quarterly' => '90d',
            default => '7d',
        };

        $dashboardData = $this->analyticsService->getDashboardMetrics(
            $report->workspace_id,
            $period
        );

        $this->log('Scheduled report generated', [
            'report_id' => $report->id,
            'report_type' => $report->report_type,
        ]);

        return [
            'report_id' => $report->id,
            'report_name' => $report->name,
            'report_type' => $report->report_type,
            'frequency' => $report->frequency,
            'generated_at' => Carbon::now()->toIso8601String(),
            'period' => $dashboardData['period'],
            'metrics' => $dashboardData['metrics'],
            'comparison' => $dashboardData['comparison'],
        ];
    }

    /**
     * Calculate the next send datetime based on frequency.
     */
    private function calculateNextSendAt(string $frequency): Carbon
    {
        return match ($frequency) {
            'monthly' => Carbon::now()->addMonth()->startOfDay()->setHour(9),
            'quarterly' => Carbon::now()->addMonths(3)->startOfDay()->setHour(9),
            default => Carbon::now()->addWeek()->startOfDay()->setHour(9), // weekly
        };
    }
}
