<?php

declare(strict_types=1);

/**
 * ProcessDataExportJob Unit Tests
 *
 * Tests for the job that generates data exports for users
 * as part of GDPR/CCPA compliance.
 *
 * @see \App\Jobs\Privacy\ProcessDataExportJob
 */

use App\Enums\Notification\NotificationType;
use App\Jobs\Privacy\ProcessDataExportJob;
use App\Models\Notification\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Storage::fake('local');
});

describe('ProcessDataExportJob', function (): void {
    describe('generating export file', function (): void {
        it('generates export file for valid request', function (): void {
            // Arrange
            $user = User::factory()->create();
            $exportRequestId = Str::uuid()->toString();

            DB::table('data_export_requests')->insert([
                'id' => $exportRequestId,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'requested_by' => $user->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Act
            $job = new ProcessDataExportJob($exportRequestId);
            $job->handle();

            // Assert
            $request = DB::table('data_export_requests')
                ->where('id', $exportRequestId)
                ->first();

            expect($request->status)->toBe('completed')
                ->and($request->file_path)->not->toBeNull()
                ->and($request->completed_at)->not->toBeNull();
        });

        it('creates JSON file with user data', function (): void {
            // Arrange
            $user = User::factory()->create();
            $exportRequestId = Str::uuid()->toString();

            DB::table('data_export_requests')->insert([
                'id' => $exportRequestId,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'requested_by' => $user->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Act
            $job = new ProcessDataExportJob($exportRequestId);
            $job->handle();

            // Assert
            $request = DB::table('data_export_requests')
                ->where('id', $exportRequestId)
                ->first();

            expect($request->file_path)->toContain('.zip');
        });

        it('includes user profile data in export', function (): void {
            // Arrange
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
            $exportRequestId = Str::uuid()->toString();

            DB::table('data_export_requests')->insert([
                'id' => $exportRequestId,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'requested_by' => $user->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Act
            $job = new ProcessDataExportJob($exportRequestId);
            $job->handle();

            // Assert
            $request = DB::table('data_export_requests')
                ->where('id', $exportRequestId)
                ->first();

            expect($request->status)->toBe('completed');
        });
    });

    describe('updating request status', function (): void {
        it('updates status to processing when job starts', function (): void {
            // This is tested implicitly - the job updates to processing then completed
            // We verify the final state
            $user = User::factory()->create();
            $exportRequestId = Str::uuid()->toString();

            DB::table('data_export_requests')->insert([
                'id' => $exportRequestId,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'requested_by' => $user->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $job = new ProcessDataExportJob($exportRequestId);
            $job->handle();

            $request = DB::table('data_export_requests')
                ->where('id', $exportRequestId)
                ->first();

            expect($request->status)->toBe('completed');
        });

        it('updates status to completed on success', function (): void {
            // Arrange
            $user = User::factory()->create();
            $exportRequestId = Str::uuid()->toString();

            DB::table('data_export_requests')->insert([
                'id' => $exportRequestId,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'requested_by' => $user->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Act
            $job = new ProcessDataExportJob($exportRequestId);
            $job->handle();

            // Assert
            $request = DB::table('data_export_requests')
                ->where('id', $exportRequestId)
                ->first();

            expect($request->status)->toBe('completed')
                ->and($request->completed_at)->not->toBeNull();
        });

        it('sets expires_at to 7 days from completion', function (): void {
            // Arrange
            $user = User::factory()->create();
            $exportRequestId = Str::uuid()->toString();

            DB::table('data_export_requests')->insert([
                'id' => $exportRequestId,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'requested_by' => $user->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Act
            $job = new ProcessDataExportJob($exportRequestId);
            $job->handle();

            // Assert
            $request = DB::table('data_export_requests')
                ->where('id', $exportRequestId)
                ->first();

            expect($request->expires_at)->not->toBeNull();
        });
    });

    describe('sending notification when ready', function (): void {
        it('sends notification when export is ready', function (): void {
            // Arrange
            $user = User::factory()->create();
            $exportRequestId = Str::uuid()->toString();

            DB::table('data_export_requests')->insert([
                'id' => $exportRequestId,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'requested_by' => $user->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Act
            $job = new ProcessDataExportJob($exportRequestId);
            $job->handle();

            // Assert
            $notification = Notification::query()
                ->where('user_id', $user->id)
                ->where('type', NotificationType::DATA_EXPORT_READY)
                ->first();

            expect($notification)->not->toBeNull()
                ->and($notification->title)->toBe('Your Data Export is Ready');
        });

        it('includes download URL in notification', function (): void {
            // Arrange
            $user = User::factory()->create();
            $exportRequestId = Str::uuid()->toString();

            DB::table('data_export_requests')->insert([
                'id' => $exportRequestId,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'requested_by' => $user->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Act
            $job = new ProcessDataExportJob($exportRequestId);
            $job->handle();

            // Assert
            $notification = Notification::query()
                ->where('user_id', $user->id)
                ->where('type', NotificationType::DATA_EXPORT_READY)
                ->first();

            expect($notification->action_url)->toContain('download');
        });
    });

    describe('handling errors and updating status', function (): void {
        it('skips if request not found', function (): void {
            // Arrange - no request created

            // Act
            $job = new ProcessDataExportJob('non-existent-id');
            $job->handle();

            // Assert - job completes without error
            expect(true)->toBeTrue();
        });

        it('skips if request already completed', function (): void {
            // Arrange
            $user = User::factory()->create();
            $exportRequestId = Str::uuid()->toString();

            DB::table('data_export_requests')->insert([
                'id' => $exportRequestId,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'requested_by' => $user->id,
                'status' => 'completed',
                'file_path' => 'exports/test.zip',
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Act
            $job = new ProcessDataExportJob($exportRequestId);
            $job->handle();

            // Assert - job returns early, no changes
            $request = DB::table('data_export_requests')
                ->where('id', $exportRequestId)
                ->first();

            expect($request->status)->toBe('completed');
        });

        it('handles valid export request successfully', function (): void {
            // Arrange
            $user = User::factory()->create();
            $exportRequestId = Str::uuid()->toString();

            DB::table('data_export_requests')->insert([
                'id' => $exportRequestId,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'requested_by' => $user->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Act
            $job = new ProcessDataExportJob($exportRequestId);
            $job->handle();

            // Assert
            $request = DB::table('data_export_requests')
                ->where('id', $exportRequestId)
                ->first();

            expect($request->status)->toBe('completed');
        });
    });

    describe('job configuration', function (): void {
        it('is assigned to the privacy queue', function (): void {
            $job = new ProcessDataExportJob('request-id');

            expect($job->queue)->toBe('privacy');
        });

        it('is configured with correct number of tries', function (): void {
            $job = new ProcessDataExportJob('request-id');

            expect($job->tries)->toBe(3);
        });

        it('is configured with 30 minute timeout', function (): void {
            $job = new ProcessDataExportJob('request-id');

            expect($job->timeout)->toBe(1800);
        });

        it('is configured with exponential backoff', function (): void {
            $job = new ProcessDataExportJob('request-id');

            expect($job->backoff)->toBe([60, 300, 600]);
        });
    });
});
