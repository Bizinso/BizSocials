<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Audit;

use App\Data\Audit\SecurityEventData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Audit\SecurityEvent;
use App\Models\User;
use App\Services\Audit\SecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SecurityController extends Controller
{
    public function __construct(
        private readonly SecurityService $securityService,
    ) {}

    /**
     * List security events for the current tenant.
     * GET /security/events
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
            'event_type' => $request->query('event_type'),
            'severity' => $request->query('severity'),
            'is_resolved' => $request->query('is_resolved'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'ip_address' => $request->query('ip_address'),
            'per_page' => $request->query('per_page', 15),
            'sort_by' => $request->query('sort_by', 'created_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
        ];

        $events = $this->securityService->listForTenant($tenant, $filters);

        $transformedItems = collect($events->items())->map(
            fn (SecurityEvent $event) => SecurityEventData::fromModel($event)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Security events retrieved successfully',
            'data' => $transformedItems,
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'from' => $events->firstItem(),
                'to' => $events->lastItem(),
            ],
            'links' => [
                'first' => $events->url(1),
                'last' => $events->url($events->lastPage()),
                'prev' => $events->previousPageUrl(),
                'next' => $events->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get security statistics for the current tenant.
     * GET /security/stats
     */
    public function stats(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenant = $user->tenant;

        if ($tenant === null) {
            return $this->error('No tenant associated with this user.', 422);
        }

        $stats = $this->securityService->getStats($tenant);

        return $this->success(
            $stats->toArray(),
            'Security statistics retrieved successfully'
        );
    }
}
