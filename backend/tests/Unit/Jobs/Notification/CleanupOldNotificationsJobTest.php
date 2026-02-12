<?php

declare(strict_types=1);

/**
 * CleanupOldNotificationsJob Unit Tests
 *
 * Tests for the job that cleans up old notifications
 * to prevent the notifications table from growing unbounded.
 *
 * @see \App\Jobs\Notification\CleanupOldNotificationsJob
 */

use App\Jobs\Notification\CleanupOldNotificationsJob;
use App\Models\Notification\Notification;
use App\Models\User;

describe('CleanupOldNotificationsJob', function (): void {
    describe('deleting old read notifications', function (): void {
        it('deletes read notifications older than 90 days', function (): void {
            // Arrange
            $user = User::factory()->create();

            // Create old read notification (should be deleted)
            $oldReadNotification = Notification::factory()->create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'read_at' => now()->subDays(100),
                'created_at' => now()->subDays(100),
            ]);

            // Create recent read notification (should be kept)
            $recentReadNotification = Notification::factory()->create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'read_at' => now()->subDays(30),
                'created_at' => now()->subDays(30),
            ]);

            // Act
            $job = new CleanupOldNotificationsJob();
            $job->handle();

            // Assert
            expect(Notification::find($oldReadNotification->id))->toBeNull()
                ->and(Notification::find($recentReadNotification->id))->not->toBeNull();
        });

        it('deletes multiple old read notifications', function (): void {
            // Arrange
            $user = User::factory()->create();

            Notification::factory()->count(10)->create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'read_at' => now()->subDays(100),
                'created_at' => now()->subDays(100),
            ]);

            $countBefore = Notification::count();

            // Act
            $job = new CleanupOldNotificationsJob();
            $job->handle();

            // Assert
            expect(Notification::count())->toBe(0)
                ->and($countBefore)->toBe(10);
        });

        it('uses custom retention period when specified', function (): void {
            // Arrange
            $user = User::factory()->create();

            // Create notification older than 30 days
            $oldNotification = Notification::factory()->create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'read_at' => now()->subDays(35),
                'created_at' => now()->subDays(35),
            ]);

            // Act - use 30 day retention
            $job = new CleanupOldNotificationsJob(readRetentionDays: 30);
            $job->handle();

            // Assert
            expect(Notification::find($oldNotification->id))->toBeNull();
        });
    });

    describe('keeping recent notifications', function (): void {
        it('keeps read notifications within retention period', function (): void {
            // Arrange
            $user = User::factory()->create();

            $recentNotification = Notification::factory()->create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'read_at' => now()->subDays(30),
                'created_at' => now()->subDays(30),
            ]);

            // Act
            $job = new CleanupOldNotificationsJob();
            $job->handle();

            // Assert
            expect(Notification::find($recentNotification->id))->not->toBeNull();
        });

        it('keeps unread notifications for longer period', function (): void {
            // Arrange
            $user = User::factory()->create();

            // Create unread notification older than 90 days but within 180 days
            $oldUnreadNotification = Notification::factory()->create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'read_at' => null,
                'created_at' => now()->subDays(100),
            ]);

            // Act
            $job = new CleanupOldNotificationsJob();
            $job->handle();

            // Assert - should still exist
            expect(Notification::find($oldUnreadNotification->id))->not->toBeNull();
        });

        it('deletes very old unread notifications', function (): void {
            // Arrange
            $user = User::factory()->create();

            // Create unread notification older than 180 days
            $veryOldUnreadNotification = Notification::factory()->create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'read_at' => null,
                'created_at' => now()->subDays(200),
            ]);

            // Act
            $job = new CleanupOldNotificationsJob();
            $job->handle();

            // Assert
            expect(Notification::find($veryOldUnreadNotification->id))->toBeNull();
        });

        it('keeps notifications created today', function (): void {
            // Arrange
            $user = User::factory()->create();

            $todayNotification = Notification::factory()->create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'read_at' => now(),
                'created_at' => now(),
            ]);

            // Act
            $job = new CleanupOldNotificationsJob();
            $job->handle();

            // Assert
            expect(Notification::find($todayNotification->id))->not->toBeNull();
        });
    });

    describe('returning count of deleted', function (): void {
        it('handles cleanup with no notifications', function (): void {
            // Arrange - no notifications created

            // Act
            $job = new CleanupOldNotificationsJob();
            $job->handle();

            // Assert - job completes without error
            expect(true)->toBeTrue();
        });

        it('deletes in chunks for performance', function (): void {
            // Arrange
            $user = User::factory()->create();

            // Create many old notifications
            Notification::factory()->count(50)->create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'read_at' => now()->subDays(100),
                'created_at' => now()->subDays(100),
            ]);

            // Act
            $job = new CleanupOldNotificationsJob();
            $job->handle();

            // Assert - all should be deleted
            expect(Notification::count())->toBe(0);
        });
    });

    describe('deleting failed notifications', function (): void {
        it('deletes failed notifications older than 30 days', function (): void {
            // Arrange
            $user = User::factory()->create();

            $oldFailedNotification = Notification::factory()->create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'failed_at' => now()->subDays(35),
                'failure_reason' => 'Email delivery failed',
                'created_at' => now()->subDays(35),
            ]);

            // Act
            $job = new CleanupOldNotificationsJob();
            $job->handle();

            // Assert
            expect(Notification::find($oldFailedNotification->id))->toBeNull();
        });

        it('keeps recent failed notifications', function (): void {
            // Arrange
            $user = User::factory()->create();

            $recentFailedNotification = Notification::factory()->create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'failed_at' => now()->subDays(10),
                'failure_reason' => 'Email delivery failed',
                'created_at' => now()->subDays(10),
            ]);

            // Act
            $job = new CleanupOldNotificationsJob();
            $job->handle();

            // Assert
            expect(Notification::find($recentFailedNotification->id))->not->toBeNull();
        });
    });

    describe('job configuration', function (): void {
        it('is assigned to the maintenance queue', function (): void {
            $job = new CleanupOldNotificationsJob();

            expect($job->queue)->toBe('maintenance');
        });

        it('is configured with correct number of tries', function (): void {
            $job = new CleanupOldNotificationsJob();

            expect($job->tries)->toBe(3);
        });

        it('is configured with 10 minute timeout', function (): void {
            $job = new CleanupOldNotificationsJob();

            expect($job->timeout)->toBe(600);
        });

        it('is configured with 60 second backoff', function (): void {
            $job = new CleanupOldNotificationsJob();

            expect($job->backoff)->toBe(60);
        });

        it('accepts custom retention periods', function (): void {
            $job = new CleanupOldNotificationsJob(
                readRetentionDays: 60,
                unreadRetentionDays: 120
            );

            expect(true)->toBeTrue(); // Job created without error
        });
    });

    describe('edge cases', function (): void {
        it('handles notifications at exact cutoff boundary', function (): void {
            // Arrange
            $user = User::factory()->create();

            // Create notification exactly at 90 day boundary
            $boundaryNotification = Notification::factory()->create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'read_at' => now()->subDays(90),
                'created_at' => now()->subDays(90),
            ]);

            // Act
            $job = new CleanupOldNotificationsJob();
            $job->handle();

            // Assert - notification at exact boundary should be kept (uses < not <=)
            expect(Notification::find($boundaryNotification->id))->not->toBeNull();
        });

        it('preserves notifications for multiple users', function (): void {
            // Arrange
            $user1 = User::factory()->create();
            $user2 = User::factory()->create(['tenant_id' => $user1->tenant_id]);

            // Old notification for user1 (should be deleted)
            Notification::factory()->create([
                'user_id' => $user1->id,
                'tenant_id' => $user1->tenant_id,
                'read_at' => now()->subDays(100),
                'created_at' => now()->subDays(100),
            ]);

            // Recent notification for user2 (should be kept)
            $recentNotification = Notification::factory()->create([
                'user_id' => $user2->id,
                'tenant_id' => $user2->tenant_id,
                'read_at' => now()->subDays(10),
                'created_at' => now()->subDays(10),
            ]);

            // Act
            $job = new CleanupOldNotificationsJob();
            $job->handle();

            // Assert
            expect(Notification::count())->toBe(1)
                ->and(Notification::find($recentNotification->id))->not->toBeNull();
        });
    });
});
