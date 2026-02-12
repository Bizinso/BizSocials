<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use App\Models\Analytics\ScheduledReport;
use App\Services\Analytics\ScheduledReportService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * SendScheduledReportsJob
 *
 * Finds scheduled reports that are due to be sent, generates them,
 * and emails the results to recipients. Updates next_send_at after each send.
 * Scheduled to run hourly.
 */
final class SendScheduledReportsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 120;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('default');
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'send-scheduled-reports:' . Carbon::now()->format('Y-m-d-H');
    }

    /**
     * Execute the job.
     */
    public function handle(ScheduledReportService $reportService): void
    {
        Log::info('[SendScheduledReportsJob] Starting scheduled report processing');

        $dueReports = ScheduledReport::due()->get();

        $sentCount = 0;
        $errorCount = 0;

        foreach ($dueReports as $report) {
            try {
                // Generate report data
                $data = $reportService->generateReport($report);

                // Send email to recipients
                $this->sendReportEmail($report, $data);

                // Update next_send_at
                $nextSendAt = $this->calculateNextSendAt($report->frequency);
                $report->update(['next_send_at' => $nextSendAt]);

                $sentCount++;

                Log::info('[SendScheduledReportsJob] Report sent', [
                    'report_id' => $report->id,
                    'report_name' => $report->name,
                    'recipients_count' => count($report->recipients),
                    'next_send_at' => $nextSendAt->toIso8601String(),
                ]);
            } catch (\Throwable $e) {
                $errorCount++;
                Log::error('[SendScheduledReportsJob] Failed to send report', [
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[SendScheduledReportsJob] Scheduled report processing completed', [
            'due_reports' => $dueReports->count(),
            'sent' => $sentCount,
            'errors' => $errorCount,
        ]);
    }

    /**
     * Send the report email to all recipients.
     *
     * @param  array<string, mixed>  $data
     */
    private function sendReportEmail(ScheduledReport $report, array $data): void
    {
        foreach ($report->recipients as $email) {
            try {
                Mail::raw(
                    $this->formatReportContent($report, $data),
                    function ($message) use ($email, $report): void {
                        $message->to($email)
                            ->subject("Scheduled Report: {$report->name}");
                    }
                );
            } catch (\Throwable $e) {
                Log::warning('[SendScheduledReportsJob] Failed to send email', [
                    'email' => $email,
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Format the report data as text content.
     *
     * @param  array<string, mixed>  $data
     */
    private function formatReportContent(ScheduledReport $report, array $data): string
    {
        $metrics = $data['metrics'] ?? [];
        $period = $data['period'] ?? [];

        return sprintf(
            "Report: %s\nType: %s\nPeriod: %s to %s\nGenerated: %s\n\n"
            . "Impressions: %s\nReach: %s\nEngagements: %s\nFollowers: %s\nEngagement Rate: %s%%\n",
            $report->name,
            $report->report_type,
            $period['start'] ?? 'N/A',
            $period['end'] ?? 'N/A',
            $data['generated_at'] ?? now()->toIso8601String(),
            number_format($metrics['impressions'] ?? 0),
            number_format($metrics['reach'] ?? 0),
            number_format($metrics['engagements'] ?? 0),
            number_format($metrics['followers_current'] ?? 0),
            $metrics['engagement_rate'] ?? 0
        );
    }

    /**
     * Calculate the next send datetime based on frequency.
     */
    private function calculateNextSendAt(string $frequency): Carbon
    {
        return match ($frequency) {
            'monthly' => Carbon::now()->addMonth()->startOfDay()->setHour(9),
            'quarterly' => Carbon::now()->addMonths(3)->startOfDay()->setHour(9),
            default => Carbon::now()->addWeek()->startOfDay()->setHour(9),
        };
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('[SendScheduledReportsJob] Job failed', [
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);
    }
}
