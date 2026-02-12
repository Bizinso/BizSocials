<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Audit;

use App\Data\Audit\DataDeletionRequestData;
use App\Data\Audit\DataExportRequestData;
use App\Data\Audit\RequestDeletionData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Audit\DataDeletionRequest;
use App\Models\Audit\DataExportRequest;
use App\Models\User;
use App\Services\Audit\DataPrivacyService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class DataPrivacyController extends Controller
{
    public function __construct(
        private readonly DataPrivacyService $dataPrivacyService,
    ) {}

    /**
     * List export requests for the current user.
     * GET /privacy/export-requests
     */
    public function exportRequests(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $requests = $this->dataPrivacyService->getExportRequests($user);

        $transformedItems = $requests->map(
            fn (DataExportRequest $req) => DataExportRequestData::fromModel($req)->toArray()
        );

        return $this->success(
            $transformedItems->toArray(),
            'Export requests retrieved successfully'
        );
    }

    /**
     * Request a data export.
     * POST /privacy/export-requests
     */
    public function requestExport(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $exportRequest = $this->dataPrivacyService->requestExport($user);

            return $this->created(
                DataExportRequestData::fromModel($exportRequest)->toArray(),
                'Data export request created successfully. You will be notified when it is ready.'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Download an export.
     * GET /privacy/export-requests/{exportRequest}/download
     */
    public function downloadExport(Request $request, string $exportRequest): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $exportModel = DataExportRequest::forUser($user->id)
            ->find($exportRequest);

        if ($exportModel === null) {
            return $this->notFound('Export request not found');
        }

        try {
            $downloadUrl = $this->dataPrivacyService->getExportDownloadUrl($exportModel);

            return $this->success(
                ['download_url' => $downloadUrl],
                'Download URL generated successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * List deletion requests for the current user.
     * GET /privacy/deletion-requests
     */
    public function deletionRequests(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $requests = $this->dataPrivacyService->getDeletionRequests($user);

        $transformedItems = $requests->map(
            fn (DataDeletionRequest $req) => DataDeletionRequestData::fromModel($req)->toArray()
        );

        return $this->success(
            $transformedItems->toArray(),
            'Deletion requests retrieved successfully'
        );
    }

    /**
     * Request account deletion.
     * POST /privacy/deletion-requests
     */
    public function requestDeletion(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        try {
            $data = RequestDeletionData::from($validated);
            $deletionRequest = $this->dataPrivacyService->requestDeletion($user, $data->reason);

            return $this->created(
                DataDeletionRequestData::fromModel($deletionRequest)->toArray(),
                'Data deletion request created successfully. It will be reviewed and processed within 30 days.'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Cancel a deletion request.
     * DELETE /privacy/deletion-requests/{deletionRequest}
     */
    public function cancelDeletion(Request $request, string $deletionRequest): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $requestModel = DataDeletionRequest::forUser($user->id)
            ->find($deletionRequest);

        if ($requestModel === null) {
            return $this->notFound('Deletion request not found');
        }

        try {
            $this->dataPrivacyService->cancelDeletion($requestModel);

            return $this->success(
                null,
                'Deletion request cancelled successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }
}
