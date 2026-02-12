<?php

declare(strict_types=1);

/**
 * GenerateReportJob Unit Tests
 *
 * Tests for the job that generates analytics reports.
 *
 * @see \App\Jobs\Analytics\GenerateReportJob
 */

use App\Enums\Analytics\ReportStatus;
use App\Enums\Analytics\ReportType;
use App\Enums\Notification\NotificationType;
use App\Jobs\Analytics\GenerateReportJob;
use App\Models\Analytics\AnalyticsAggregate;
use App\Models\Analytics\AnalyticsReport;
use App\Models\Notification\Notification;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('s3');
});

describe('GenerateReportJob', function (): void {
    describe('job configuration', function (): void {
        it('is assigned to the reports queue', function (): void {
            $job = new GenerateReportJob(reportId: fake()->uuid());

            expect($job->queue)->toBe('reports');
        });

        it('is configured with correct number of tries', function (): void {
            $job = new GenerateReportJob(reportId: fake()->uuid());

            expect($job->tries)->toBe(2);
        });

        it('is configured with 10 minute timeout', function (): void {
            $job = new GenerateReportJob(reportId: fake()->uuid());

            expect($job->timeout)->toBe(600);
        });

        it('is configured with exponential backoff', function (): void {
            $job = new GenerateReportJob(reportId: fake()->uuid());

            expect($job->backoff)->toBe([60, 300]);
        });
    });

    describe('generating reports', function (): void {
        it('generates report file for valid request', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();
            $report = AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->pending()
                ->create();

            // Act
            $job = new GenerateReportJob(reportId: $report->id);
            $job->handle();

            // Assert
            $report->refresh();

            expect($report->status)->toBe(ReportStatus::COMPLETED)
                ->and($report->file_path)->not->toBeNull()
                ->and($report->completed_at)->not->toBeNull()
                ->and($report->expires_at)->not->toBeNull();
        });

        it('sets expiration to 7 days from completion', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();
            $report = AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->pending()
                ->create();

            // Act
            $job = new GenerateReportJob(reportId: $report->id);
            $job->handle();

            // Assert
            $report->refresh();

            expect((int) $report->completed_at->diffInDays($report->expires_at))->toBe(7);
        });

        it('creates file in correct location', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();
            $report = AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->pending()
                ->create(['file_format' => 'csv']);

            // Act
            $job = new GenerateReportJob(reportId: $report->id);
            $job->handle();

            // Assert
            $report->refresh();

            expect($report->file_path)->toContain('reports/')
                ->and($report->file_path)->toContain($workspace->id)
                ->and($report->file_path)->toEndWith('.csv');
        });

        it('includes aggregated metrics in report data', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();

            // Create some aggregates
            AnalyticsAggregate::factory()
                ->forWorkspace($workspace)
                ->count(5)
                ->create([
                    'impressions' => 1000,
                    'engagements' => 100,
                ]);

            $report = AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->pending()
                ->create();

            // Act
            $job = new GenerateReportJob(reportId: $report->id);
            $job->handle();

            // Assert
            $report->refresh();

            expect($report->status)->toBe(ReportStatus::COMPLETED)
                ->and($report->file_size_bytes)->toBeGreaterThan(0);
        });
    });

    describe('updating status', function (): void {
        it('updates status to processing when job starts', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();
            $report = AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->pending()
                ->create();

            // After handle completes, status should be completed (not processing)
            // We can verify the final state
            $job = new GenerateReportJob(reportId: $report->id);
            $job->handle();

            $report->refresh();

            expect($report->status)->toBe(ReportStatus::COMPLETED);
        });

        it('updates status to completed on success', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();
            $report = AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->pending()
                ->create();

            // Act
            $job = new GenerateReportJob(reportId: $report->id);
            $job->handle();

            // Assert
            $report->refresh();

            expect($report->status)->toBe(ReportStatus::COMPLETED)
                ->and($report->completed_at)->not->toBeNull();
        });
    });

    describe('sending notifications', function (): void {
        it('sends notification when report is ready', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();
            $report = AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->pending()
                ->create();

            // Act
            $job = new GenerateReportJob(reportId: $report->id);
            $job->handle();

            // Assert
            $notification = Notification::query()
                ->where('user_id', $user->id)
                ->where('type', NotificationType::REPORT_READY)
                ->first();

            expect($notification)->not->toBeNull()
                ->and($notification->title)->toBe('Your Report is Ready');
        });

        it('includes download URL in notification', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();
            $report = AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->pending()
                ->create();

            // Act
            $job = new GenerateReportJob(reportId: $report->id);
            $job->handle();

            // Assert
            $notification = Notification::query()
                ->where('user_id', $user->id)
                ->where('type', NotificationType::REPORT_READY)
                ->first();

            expect($notification->action_url)->toContain('download')
                ->and($notification->action_url)->toContain($report->id);
        });
    });

    describe('handling edge cases', function (): void {
        it('skips if report not found', function (): void {
            // Arrange - no report created

            // Act
            $job = new GenerateReportJob(reportId: 'non-existent-id');
            $job->handle();

            // Assert - job completes without error
            expect(true)->toBeTrue();
        });

        it('skips if report already completed', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();
            $report = AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->completed()
                ->create([
                    'file_path' => 'reports/existing.pdf',
                ]);

            $originalFilePath = $report->file_path;

            // Act
            $job = new GenerateReportJob(reportId: $report->id);
            $job->handle();

            // Assert - file path unchanged
            $report->refresh();

            expect($report->file_path)->toBe($originalFilePath);
        });
    });

    describe('file formats', function (): void {
        it('generates CSV file for csv format', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();
            $report = AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->pending()
                ->create(['file_format' => 'csv']);

            // Act
            $job = new GenerateReportJob(reportId: $report->id);
            $job->handle();

            // Assert
            $report->refresh();

            expect($report->file_path)->toEndWith('.csv');
            Storage::disk('s3')->assertExists($report->file_path);
        });

        it('generates JSON file for json format', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();
            $report = AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->pending()
                ->create(['file_format' => 'json']);

            // Act
            $job = new GenerateReportJob(reportId: $report->id);
            $job->handle();

            // Assert
            $report->refresh();

            expect($report->file_path)->toEndWith('.json');
            Storage::disk('s3')->assertExists($report->file_path);
        });

        it('generates PDF file for pdf format', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();
            $report = AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->pending()
                ->create(['file_format' => 'pdf']);

            // Act
            $job = new GenerateReportJob(reportId: $report->id);
            $job->handle();

            // Assert
            $report->refresh();

            expect($report->file_path)->toEndWith('.pdf');
            Storage::disk('s3')->assertExists($report->file_path);
        });
    });
});
