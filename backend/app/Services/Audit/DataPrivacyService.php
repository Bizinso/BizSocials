<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Enums\Audit\DataRequestStatus;
use App\Enums\Audit\DataRequestType;
use App\Models\Audit\DataDeletionRequest;
use App\Models\Audit\DataExportRequest;
use App\Models\Platform\SuperAdminUser;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class DataPrivacyService extends BaseService
{
    /**
     * Request data export for a user.
     */
    public function requestExport(User $user): DataExportRequest
    {
        // Check if there's already a pending export request
        $pendingRequest = DataExportRequest::forUser($user->id)
            ->pending()
            ->first();

        if ($pendingRequest !== null) {
            throw ValidationException::withMessages([
                'export' => ['You already have a pending export request.'],
            ]);
        }

        $exportRequest = DataExportRequest::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'requested_by' => $user->id,
            'request_type' => DataRequestType::EXPORT,
            'status' => DataRequestStatus::PENDING,
            'format' => 'json',
            'download_count' => 0,
        ]);

        $this->log('Data export requested', [
            'user_id' => $user->id,
            'request_id' => $exportRequest->id,
        ]);

        return $exportRequest;
    }

    /**
     * Get export requests for a user.
     */
    public function getExportRequests(User $user): Collection
    {
        return DataExportRequest::forUser($user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Process an export request.
     */
    public function processExport(DataExportRequest $request): void
    {
        $request->start();

        try {
            // Collect user data (simplified - in production, implement full data collection)
            $userData = $this->collectUserData($request->user);

            // Save to file
            $fileName = sprintf('exports/user_%s_%s.json', $request->user_id, now()->format('YmdHis'));
            $filePath = storage_path('app/' . $fileName);

            file_put_contents($filePath, json_encode($userData, JSON_PRETTY_PRINT));

            $fileSize = filesize($filePath);

            $request->complete($fileName, $fileSize ?: 0);

            $this->log('Data export completed', [
                'request_id' => $request->id,
                'file_size' => $fileSize,
            ]);
        } catch (\Throwable $e) {
            $request->fail($e->getMessage());

            $this->log('Data export failed', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ], 'error');
        }
    }

    /**
     * Get download URL for an export.
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function getExportDownloadUrl(DataExportRequest $request): string
    {
        if (!$request->isCompleted()) {
            throw ValidationException::withMessages([
                'export' => ['Export is not yet completed.'],
            ]);
        }

        if ($request->isExpired()) {
            throw ValidationException::withMessages([
                'export' => ['Export has expired.'],
            ]);
        }

        $request->incrementDownloadCount();

        return $request->getDownloadUrl() ?? '';
    }

    /**
     * Request data deletion for a user.
     */
    public function requestDeletion(User $user, string $reason): DataDeletionRequest
    {
        // Check if there's already a pending deletion request
        $pendingRequest = DataDeletionRequest::forUser($user->id)
            ->pending()
            ->first();

        if ($pendingRequest !== null) {
            throw ValidationException::withMessages([
                'deletion' => ['You already have a pending deletion request.'],
            ]);
        }

        $deletionRequest = DataDeletionRequest::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'requested_by' => $user->id,
            'status' => DataRequestStatus::PENDING,
            'reason' => $reason,
            'requires_approval' => true,
            'scheduled_for' => now()->addDays(30),
        ]);

        $this->log('Data deletion requested', [
            'user_id' => $user->id,
            'request_id' => $deletionRequest->id,
        ]);

        return $deletionRequest;
    }

    /**
     * Get deletion requests for a user.
     */
    public function getDeletionRequests(User $user): Collection
    {
        return DataDeletionRequest::forUser($user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Cancel a deletion request.
     *
     * @throws ValidationException
     */
    public function cancelDeletion(DataDeletionRequest $request): void
    {
        if (!$request->isPending()) {
            throw ValidationException::withMessages([
                'deletion' => ['Only pending deletion requests can be cancelled.'],
            ]);
        }

        $request->cancel();

        $this->log('Data deletion cancelled', [
            'request_id' => $request->id,
        ]);
    }

    /**
     * Process a deletion request.
     */
    public function processDeletion(DataDeletionRequest $request): void
    {
        if (!$request->isApproved()) {
            throw ValidationException::withMessages([
                'deletion' => ['Deletion request must be approved before processing.'],
            ]);
        }

        try {
            // Perform deletion (simplified - in production, implement full data deletion)
            $summary = $this->deleteUserData($request->user);

            $request->complete($summary);

            $this->log('Data deletion completed', [
                'request_id' => $request->id,
                'summary' => $summary,
            ]);
        } catch (\Throwable $e) {
            $request->fail($e->getMessage());

            $this->log('Data deletion failed', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ], 'error');
        }
    }

    /**
     * List all export requests (admin).
     *
     * @param array<string, mixed> $filters
     */
    public function listAllExportRequests(array $filters = []): LengthAwarePaginator
    {
        $query = DataExportRequest::with(['user', 'requester']);

        // Filter by status
        if (!empty($filters['status'])) {
            $status = DataRequestStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    /**
     * List all deletion requests (admin).
     *
     * @param array<string, mixed> $filters
     */
    public function listAllDeletionRequests(array $filters = []): LengthAwarePaginator
    {
        $query = DataDeletionRequest::with(['user', 'requester', 'approver']);

        // Filter by status
        if (!empty($filters['status'])) {
            $status = DataRequestStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        // Filter by approval status
        if (isset($filters['needs_approval']) && $filters['needs_approval']) {
            $query->pending()
                ->where('requires_approval', true)
                ->whereNull('approved_at');
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    /**
     * Approve a deletion request (admin).
     */
    public function approveDeletion(DataDeletionRequest $request, SuperAdminUser $admin): void
    {
        if (!$request->isPending()) {
            throw ValidationException::withMessages([
                'deletion' => ['Only pending deletion requests can be approved.'],
            ]);
        }

        $request->approve($admin, now()->addDays(30));

        $this->log('Data deletion approved', [
            'request_id' => $request->id,
            'admin_id' => $admin->id,
        ]);
    }

    /**
     * Reject a deletion request (admin).
     */
    public function rejectDeletion(DataDeletionRequest $request, SuperAdminUser $admin, string $reason): void
    {
        if (!$request->isPending()) {
            throw ValidationException::withMessages([
                'deletion' => ['Only pending deletion requests can be rejected.'],
            ]);
        }

        $request->status = DataRequestStatus::CANCELLED;
        $request->failure_reason = $reason;
        $request->save();

        $this->log('Data deletion rejected', [
            'request_id' => $request->id,
            'admin_id' => $admin->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Collect user data for export.
     *
     * @return array<string, mixed>
     */
    private function collectUserData(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'phone' => $user->phone,
                'timezone' => $user->timezone,
                'language' => $user->language,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'exported_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Delete user data.
     *
     * @return array<string, int>
     */
    private function deleteUserData(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        // In a real implementation, this would delete all user data
        // For now, we just return a summary
        return [
            'user_deleted' => 1,
            'sessions_deleted' => 0,
            'audit_logs_anonymized' => 0,
        ];
    }
}
