<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Audit;

use App\Data\Audit\DataDeletionRequestData;
use App\Data\Audit\DataExportRequestData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Audit\DataDeletionRequest;
use App\Models\Audit\DataExportRequest;
use App\Models\Platform\SuperAdminUser;
use App\Services\Audit\DataPrivacyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class AdminDataPrivacyController extends Controller
{
    public function __construct(
        private readonly DataPrivacyService $dataPrivacyService,
    ) {}

    /**
     * List all export requests.
     * GET /admin/privacy/export-requests
     */
    public function exportRequests(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->query('status'),
            'per_page' => $request->query('per_page', 15),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
        ];

        $requests = $this->dataPrivacyService->listAllExportRequests($filters);

        $transformedItems = collect($requests->items())->map(
            fn (DataExportRequest $req) => DataExportRequestData::fromModel($req)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Export requests retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
                'from' => $requests->firstItem(),
                'to' => $requests->lastItem(),
            ],
            'links' => [
                'first' => $requests->url(1),
                'last' => $requests->url($requests->lastPage()),
                'prev' => $requests->previousPageUrl(),
                'next' => $requests->nextPageUrl(),
            ],
        ]);
    }

    /**
     * List all deletion requests.
     * GET /admin/privacy/deletion-requests
     */
    public function deletionRequests(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->query('status'),
            'needs_approval' => $request->boolean('needs_approval'),
            'per_page' => $request->query('per_page', 15),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
        ];

        $requests = $this->dataPrivacyService->listAllDeletionRequests($filters);

        $transformedItems = collect($requests->items())->map(
            fn (DataDeletionRequest $req) => DataDeletionRequestData::fromModel($req)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Deletion requests retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
                'from' => $requests->firstItem(),
                'to' => $requests->lastItem(),
            ],
            'links' => [
                'first' => $requests->url(1),
                'last' => $requests->url($requests->lastPage()),
                'prev' => $requests->previousPageUrl(),
                'next' => $requests->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Approve a deletion request.
     * POST /admin/privacy/deletion-requests/{deletionRequest}/approve
     */
    public function approveDeletion(Request $request, string $deletionRequest): JsonResponse
    {
        /** @var SuperAdminUser $admin */
        $admin = $request->user();

        $requestModel = DataDeletionRequest::find($deletionRequest);

        if ($requestModel === null) {
            return $this->notFound('Deletion request not found');
        }

        try {
            $this->dataPrivacyService->approveDeletion($requestModel, $admin);

            return $this->success(
                DataDeletionRequestData::fromModel($requestModel->fresh())->toArray(),
                'Deletion request approved successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * Reject a deletion request.
     * POST /admin/privacy/deletion-requests/{deletionRequest}/reject
     */
    public function rejectDeletion(Request $request, string $deletionRequest): JsonResponse
    {
        /** @var SuperAdminUser $admin */
        $admin = $request->user();

        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        $requestModel = DataDeletionRequest::find($deletionRequest);

        if ($requestModel === null) {
            return $this->notFound('Deletion request not found');
        }

        try {
            $this->dataPrivacyService->rejectDeletion($requestModel, $admin, $validated['reason']);

            return $this->success(
                DataDeletionRequestData::fromModel($requestModel->fresh())->toArray(),
                'Deletion request rejected successfully'
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }
}
