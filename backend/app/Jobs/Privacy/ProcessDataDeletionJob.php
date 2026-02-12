<?php

declare(strict_types=1);

namespace App\Jobs\Privacy;

use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationType;
use App\Models\Notification\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ProcessDataDeletionJob
 *
 * Processes an approved data deletion request by permanently removing
 * all user data from the system. This job implements GDPR/CCPA compliant
 * data deletion including:
 * - Deleting user profile and settings
 * - Removing all posts and media files
 * - Clearing notifications and preferences
 * - Anonymizing audit logs (preserving for compliance)
 * - Removing sessions and tokens
 *
 * Features:
 * - Verifies deletion is approved before processing
 * - Checks for cancellation during processing
 * - Anonymizes data that must be retained for legal/compliance
 * - Sends confirmation notification before final deletion
 * - Creates permanent deletion record for compliance
 */
final class ProcessDataDeletionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 1; // Deletion should only be attempted once

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 3600; // 1 hour for large deletions

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $deletionRequestId,
    ) {
        $this->onQueue('privacy');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[ProcessDataDeletionJob] Starting data deletion', [
            'deletion_request_id' => $this->deletionRequestId,
        ]);

        // Get the deletion request
        $deletionRequest = DB::table('data_deletion_requests')
            ->where('id', $this->deletionRequestId)
            ->first();

        if ($deletionRequest === null) {
            Log::warning('[ProcessDataDeletionJob] Deletion request not found', [
                'deletion_request_id' => $this->deletionRequestId,
            ]);
            return;
        }

        // Verify the request is approved
        if ($deletionRequest->status !== 'approved') {
            Log::warning('[ProcessDataDeletionJob] Deletion request not approved', [
                'deletion_request_id' => $this->deletionRequestId,
                'status' => $deletionRequest->status,
            ]);
            return;
        }

        // Check if cancelled
        if ($deletionRequest->status === 'cancelled') {
            Log::info('[ProcessDataDeletionJob] Deletion request was cancelled', [
                'deletion_request_id' => $this->deletionRequestId,
            ]);
            return;
        }

        // Get the user
        $user = User::find($deletionRequest->user_id);

        if ($user === null) {
            Log::warning('[ProcessDataDeletionJob] User not found', [
                'deletion_request_id' => $this->deletionRequestId,
                'user_id' => $deletionRequest->user_id,
            ]);
            $this->updateStatus('failed', 'User not found');
            return;
        }

        // Store email before deletion for final notification
        $userEmail = $user->email;
        $userName = $user->name;
        $tenantId = $user->tenant_id;

        // Update status to processing
        $this->updateStatus('processing');

        try {
            DB::beginTransaction();

            // Delete user data in order (respecting foreign key constraints)
            $this->deleteUserSessions($user);
            $this->deleteUserNotifications($user);
            $this->deleteUserNotificationPreferences($user);
            $this->deleteUserPosts($user);
            $this->deleteUserSocialAccounts($user);
            $this->deleteUserMedia($user);
            $this->deleteUserWorkspaceMemberships($user);
            $this->anonymizeAuditLogs($user);

            // Check for cancellation before final deletion
            $deletionRequest = DB::table('data_deletion_requests')
                ->where('id', $this->deletionRequestId)
                ->first();

            if ($deletionRequest->status === 'cancelled') {
                DB::rollBack();
                Log::info('[ProcessDataDeletionJob] Deletion cancelled mid-process', [
                    'deletion_request_id' => $this->deletionRequestId,
                ]);
                return;
            }

            // Delete the user account
            $this->deleteUserAccount($user);

            // Create permanent deletion record
            $this->createDeletionRecord($deletionRequest, $userEmail, $userName);

            DB::commit();

            // Update status to completed
            $this->updateStatus('completed');

            // Send confirmation email (external notification since user is deleted)
            $this->sendConfirmationEmail($userEmail, $userName);

            Log::info('[ProcessDataDeletionJob] Data deletion completed', [
                'deletion_request_id' => $this->deletionRequestId,
                'user_email' => $userEmail,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->updateStatus('failed', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete all user sessions.
     */
    private function deleteUserSessions(User $user): void
    {
        $count = DB::table('user_sessions')
            ->where('user_id', $user->id)
            ->delete();

        Log::debug('[ProcessDataDeletionJob] Deleted user sessions', [
            'user_id' => $user->id,
            'count' => $count,
        ]);
    }

    /**
     * Delete all user notifications.
     */
    private function deleteUserNotifications(User $user): void
    {
        $count = DB::table('notifications')
            ->where('user_id', $user->id)
            ->delete();

        Log::debug('[ProcessDataDeletionJob] Deleted notifications', [
            'user_id' => $user->id,
            'count' => $count,
        ]);
    }

    /**
     * Delete user notification preferences.
     */
    private function deleteUserNotificationPreferences(User $user): void
    {
        $count = DB::table('notification_preferences')
            ->where('user_id', $user->id)
            ->delete();

        Log::debug('[ProcessDataDeletionJob] Deleted notification preferences', [
            'user_id' => $user->id,
            'count' => $count,
        ]);
    }

    /**
     * Delete all posts created by the user.
     */
    private function deleteUserPosts(User $user): void
    {
        // Get post IDs
        $postIds = DB::table('posts')
            ->where('created_by_user_id', $user->id)
            ->pluck('id')
            ->toArray();

        if (empty($postIds)) {
            return;
        }

        // Delete related records
        DB::table('post_media')->whereIn('post_id', $postIds)->delete();
        DB::table('post_targets')->whereIn('post_id', $postIds)->delete();
        DB::table('approval_decisions')->whereIn('post_id', $postIds)->delete();

        // Delete posts
        $count = DB::table('posts')
            ->where('created_by_user_id', $user->id)
            ->delete();

        Log::debug('[ProcessDataDeletionJob] Deleted posts', [
            'user_id' => $user->id,
            'count' => $count,
        ]);
    }

    /**
     * Delete social accounts connected by the user.
     */
    private function deleteUserSocialAccounts(User $user): void
    {
        $count = DB::table('social_accounts')
            ->where('connected_by_user_id', $user->id)
            ->delete();

        Log::debug('[ProcessDataDeletionJob] Deleted social accounts', [
            'user_id' => $user->id,
            'count' => $count,
        ]);
    }

    /**
     * Delete media files uploaded by the user.
     */
    private function deleteUserMedia(User $user): void
    {
        // Get media file paths
        $mediaPaths = DB::table('post_media')
            ->join('posts', 'posts.id', '=', 'post_media.post_id')
            ->where('posts.created_by_user_id', $user->id)
            ->pluck('post_media.storage_path')
            ->toArray();

        foreach ($mediaPaths as $path) {
            try {
                Storage::delete($path);
            } catch (\Throwable $e) {
                Log::warning('[ProcessDataDeletionJob] Failed to delete media file', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::debug('[ProcessDataDeletionJob] Deleted media files', [
            'user_id' => $user->id,
            'count' => count($mediaPaths),
        ]);
    }

    /**
     * Delete user workspace memberships.
     */
    private function deleteUserWorkspaceMemberships(User $user): void
    {
        $count = DB::table('workspace_memberships')
            ->where('user_id', $user->id)
            ->delete();

        Log::debug('[ProcessDataDeletionJob] Deleted workspace memberships', [
            'user_id' => $user->id,
            'count' => $count,
        ]);
    }

    /**
     * Anonymize audit logs for compliance.
     * Audit logs are retained but user-identifying information is removed.
     */
    private function anonymizeAuditLogs(User $user): void
    {
        $count = DB::table('audit_logs')
            ->where('user_id', $user->id)
            ->update([
                'user_id' => null,
                'ip_address' => '0.0.0.0',
                'user_agent' => 'ANONYMIZED',
            ]);

        Log::debug('[ProcessDataDeletionJob] Anonymized audit logs', [
            'user_id' => $user->id,
            'count' => $count,
        ]);
    }

    /**
     * Delete the user account.
     */
    private function deleteUserAccount(User $user): void
    {
        // Revoke all tokens
        $user->tokens()->delete();

        // Force delete the user (bypass soft delete)
        DB::table('users')->where('id', $user->id)->delete();

        Log::debug('[ProcessDataDeletionJob] Deleted user account', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a permanent deletion record for compliance.
     * Stores the deletion summary in the request's deletion_summary JSON field.
     */
    private function createDeletionRecord(object $deletionRequest, string $userEmail, string $userName): void
    {
        // Store deletion summary in the request record for compliance
        DB::table('data_deletion_requests')
            ->where('id', $this->deletionRequestId)
            ->update([
                'deletion_summary' => json_encode([
                    'user_email_hash' => hash('sha256', $userEmail),
                    'user_name_hash' => hash('sha256', $userName),
                    'deleted_at' => now()->toIso8601String(),
                    'reason' => $deletionRequest->reason,
                ]),
            ]);

        Log::debug('[ProcessDataDeletionJob] Created deletion record', [
            'deletion_request_id' => $this->deletionRequestId,
        ]);
    }

    /**
     * Send confirmation email about completed deletion.
     */
    private function sendConfirmationEmail(string $email, string $name): void
    {
        try {
            // This would use a direct mail sending since the user no longer exists
            // in the system and cannot receive in-app notifications
            \Illuminate\Support\Facades\Mail::raw(
                sprintf(
                    "Dear %s,\n\nYour data deletion request has been processed and all your personal data has been permanently removed from BizSocials.\n\nThis action cannot be undone.\n\nIf you have any questions, please contact support@bizsocials.com.\n\nBest regards,\nThe BizSocials Team",
                    $name
                ),
                function ($message) use ($email, $name) {
                    $message->to($email, $name)
                        ->subject('Your Data Has Been Deleted - BizSocials');
                }
            );

            Log::debug('[ProcessDataDeletionJob] Sent confirmation email', [
                'email' => $email,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[ProcessDataDeletionJob] Failed to send confirmation email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the deletion request status.
     */
    private function updateStatus(string $status, ?string $errorMessage = null): void
    {
        $update = [
            'status' => $status,
            'updated_at' => now(),
        ];

        if ($status === 'completed') {
            $update['completed_at'] = now();
        }

        if ($errorMessage !== null) {
            $update['failure_reason'] = $errorMessage;
        }

        DB::table('data_deletion_requests')
            ->where('id', $this->deletionRequestId)
            ->update($update);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('[ProcessDataDeletionJob] Job failed', [
            'deletion_request_id' => $this->deletionRequestId,
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);

        $this->updateStatus('failed', $exception?->getMessage());
    }
}
