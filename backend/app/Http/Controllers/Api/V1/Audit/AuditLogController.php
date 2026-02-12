<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Audit;

use App\Data\Audit\AuditLogData;
use App\Enums\Audit\AuditableType;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Audit\AuditLog;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * List audit logs for the current tenant.
     * GET /audit/logs
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenant = $user->tenant;

        if ($tenant === null) {
            return $this->error('No tenant associated with this user.', 422);
        }

        $filters = [
            'action' => $request->query('action'),
            'auditable_type' => $request->query('auditable_type'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 15),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
        ];

        $logs = $this->auditLogService->listForTenant($tenant, $filters);

        $transformedItems = collect($logs->items())->map(
            fn (AuditLog $log) => AuditLogData::fromModel($log)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Audit logs retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
            'links' => [
                'first' => $logs->url(1),
                'last' => $logs->url($logs->lastPage()),
                'prev' => $logs->previousPageUrl(),
                'next' => $logs->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get audit logs for a specific auditable resource.
     * GET /audit/logs/{auditableType}/{auditableId}
     */
    public function forAuditable(Request $request, string $auditableType, string $auditableId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenant = $user->tenant;

        if ($tenant === null) {
            return $this->error('No tenant associated with this user.', 422);
        }

        $type = AuditableType::tryFrom($auditableType);

        if ($type === null) {
            return $this->error('Invalid auditable type.', 400);
        }

        $modelClass = $type->modelClass();

        if ($modelClass === null) {
            return $this->error('This auditable type does not support direct lookup.', 400);
        }

        // Verify the resource belongs to the tenant
        $model = $modelClass::find($auditableId);

        if ($model === null) {
            return $this->notFound('Resource not found');
        }

        // Check tenant ownership if applicable
        if (method_exists($model, 'tenant_id') && $model->tenant_id !== $tenant->id) {
            return $this->forbidden('You do not have access to this resource.');
        }

        $logs = $this->auditLogService->listForAuditable($model);

        $transformedItems = $logs->map(
            fn (AuditLog $log) => AuditLogData::fromModel($log)->toArray()
        );

        return $this->success(
            $transformedItems->toArray(),
            'Audit logs retrieved successfully'
        );
    }
}
