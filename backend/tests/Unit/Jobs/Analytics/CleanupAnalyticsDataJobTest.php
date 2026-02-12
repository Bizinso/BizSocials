<?php

declare(strict_types=1);

/**
 * CleanupAnalyticsDataJob Unit Tests
 *
 * Tests for the job that cleans up old analytics data.
 *
 * @see \App\Jobs\Analytics\CleanupAnalyticsDataJob
 */

use App\Enums\Analytics\ReportStatus;
use App\Jobs\Analytics\CleanupAnalyticsDataJob;
use App\Models\Analytics\AnalyticsReport;
use App\Models\Analytics\UserActivityLog;
use App\Models\Inbox\PostMetricSnapshot;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    // Clear activity logs for test isolation
    \App\Models\Analytics\UserActivityLog::query()->delete();
});

describe('CleanupAnalyticsDataJob', function (): void {
    describe('job configuration', function (): void {
        it('is assigned to the maintenance queue', function (): void {
            $job = new CleanupAnalyticsDataJob();

            expect($job->queue)->toBe('maintenance');
        });

        it('is configured with 1 try', function (): void {
            $job = new CleanupAnalyticsDataJob();

            expect($job->tries)->toBe(1);
        });

        it('is configured with 30 minute timeout', function (): void {
            $job = new CleanupAnalyticsDataJob();

            expect($job->timeout)->toBe(1800);
        });

        it('has unique id', function (): void {
            $job = new CleanupAnalyticsDataJob();

            expect($job->uniqueId())->toBe('cleanup-analytics-data');
        });

        it('accepts custom retention periods', function (): void {
            $job = new CleanupAnalyticsDataJob(
                activityLogRetentionDays: 60,
                metricSnapshotRetentionDays: 14
            );

            expect($job)->toBeInstanceOf(CleanupAnalyticsDataJob::class);
        });
    });

    describe('cleaning up activity logs', function (): void {
        it('deletes activity logs older than 90 days', function (): void {
            // Arrange
            $user = User::factory()->create();

            // Old activity log (should be deleted)
            UserActivityLog::factory()->forUser($user)->old(100)->create();

            // Recent activity log (should be kept)
            $recentLog = UserActivityLog::factory()->forUser($user)->recent(30)->create();

            // Act
            $job = new CleanupAnalyticsDataJob();
            $job->handle();

            // Assert
            expect(UserActivityLog::find($recentLog->id))->not->toBeNull()
                ->and(UserActivityLog::count())->toBe(1);
        });

        it('uses custom retention period when specified', function (): void {
            // Arrange
            $user = User::factory()->create();

            // Log older than 30 days
            UserActivityLog::factory()->forUser($user)->old(40)->create();

            // Log older than 60 days
            UserActivityLog::factory()->forUser($user)->old(70)->create();

            // Act - use 60 day retention
            $job = new CleanupAnalyticsDataJob(activityLogRetentionDays: 60);
            $job->handle();

            // Assert - only logs older than 60 days deleted
            expect(UserActivityLog::count())->toBe(1);
        });

        it('handles cleanup with no activity logs', function (): void {
            // Arrange - no logs created

            // Act
            $job = new CleanupAnalyticsDataJob();
            $job->handle();

            // Assert - job completes without error
            expect(true)->toBeTrue();
        });

        it('deletes in chunks for performance', function (): void {
            // Arrange
            $user = User::factory()->create();

            // Create many old logs
            UserActivityLog::factory()->count(50)->forUser($user)->old(100)->create();

            $countBefore = UserActivityLog::count();

            // Act
            $job = new CleanupAnalyticsDataJob();
            $job->handle();

            // Assert
            expect(UserActivityLog::count())->toBe(0)
                ->and($countBefore)->toBe(50);
        });
    });

    describe('cleaning up metric snapshots', function (): void {
        it('deletes metric snapshots older than 30 days', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();

            // Create old snapshot (should be deleted)
            PostMetricSnapshot::factory()
                ->forWorkspace($workspace)
                ->create(['captured_at' => now()->subDays(40)]);

            // Create recent snapshot (should be kept)
            $recentSnapshot = PostMetricSnapshot::factory()
                ->forWorkspace($workspace)
                ->create(['captured_at' => now()->subDays(10)]);

            // Act
            $job = new CleanupAnalyticsDataJob();
            $job->handle();

            // Assert
            expect(PostMetricSnapshot::find($recentSnapshot->id))->not->toBeNull()
                ->and(PostMetricSnapshot::count())->toBe(1);
        });

        it('uses custom retention period for snapshots', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();

            // Snapshot 20 days old
            PostMetricSnapshot::factory()
                ->forWorkspace($workspace)
                ->create(['captured_at' => now()->subDays(20)]);

            // Act - use 14 day retention
            $job = new CleanupAnalyticsDataJob(metricSnapshotRetentionDays: 14);
            $job->handle();

            // Assert - snapshot should be deleted
            expect(PostMetricSnapshot::count())->toBe(0);
        });
    });

    describe('cleaning up expired reports', function (): void {
        it('marks expired reports and deletes files', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();

            // Create expired report with file
            $filePath = 'reports/expired-report.pdf';
            Storage::disk('local')->put($filePath, 'content');

            $expiredReport = AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->create([
                    'status' => ReportStatus::COMPLETED,
                    'file_path' => $filePath,
                    'expires_at' => now()->subDays(1),
                ]);

            // Act
            $job = new CleanupAnalyticsDataJob();
            $job->handle();

            // Assert
            $expiredReport->refresh();

            expect($expiredReport->status)->toBe(ReportStatus::EXPIRED)
                ->and($expiredReport->file_path)->toBeNull();
            Storage::disk('local')->assertMissing($filePath);
        });

        it('keeps reports that are not expired', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();

            $validReport = AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->completed()
                ->create([
                    'expires_at' => now()->addDays(7),
                ]);

            // Act
            $job = new CleanupAnalyticsDataJob();
            $job->handle();

            // Assert
            $validReport->refresh();

            expect($validReport->status)->toBe(ReportStatus::COMPLETED);
        });

        it('deletes old failed reports', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();

            // Old failed report (should be deleted)
            $oldFailedReport = AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->failed()
                ->create(['created_at' => now()->subDays(40)]);

            // Recent failed report (should be kept)
            $recentFailedReport = AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->failed()
                ->create(['created_at' => now()->subDays(10)]);

            // Act
            $job = new CleanupAnalyticsDataJob();
            $job->handle();

            // Assert
            expect(AnalyticsReport::find($oldFailedReport->id))->toBeNull()
                ->and(AnalyticsReport::find($recentFailedReport->id))->not->toBeNull();
        });
    });

    describe('edge cases', function (): void {
        it('handles mixed data types in single run', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();

            // Create old activity logs
            UserActivityLog::factory()->count(5)->forUser($user)->old(100)->create();

            // Create old metric snapshots
            PostMetricSnapshot::factory()
                ->count(3)
                ->forWorkspace($workspace)
                ->create(['captured_at' => now()->subDays(40)]);

            // Create expired report
            AnalyticsReport::factory()
                ->forWorkspace($workspace)
                ->createdBy($user)
                ->expired()
                ->create();

            // Act
            $job = new CleanupAnalyticsDataJob();
            $job->handle();

            // Assert
            expect(UserActivityLog::count())->toBe(0)
                ->and(PostMetricSnapshot::count())->toBe(0);
        });

        it('preserves recent data while cleaning old data', function (): void {
            // Arrange
            $user = User::factory()->create();
            $workspace = Workspace::factory()->forTenant($user->tenant)->create();

            // Mix of old and recent activity logs
            UserActivityLog::factory()->count(3)->forUser($user)->old(100)->create();
            UserActivityLog::factory()->count(2)->forUser($user)->recent(10)->create();

            // Act
            $job = new CleanupAnalyticsDataJob();
            $job->handle();

            // Assert - only recent logs remain
            expect(UserActivityLog::count())->toBe(2);
        });
    });
});
