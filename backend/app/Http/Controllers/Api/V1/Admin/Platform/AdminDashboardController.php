<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Platform;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\Admin\AdminTenantService;
use App\Services\Admin\AdminUserService;
use App\Services\Admin\PlatformStatsService;
use Illuminate\Http\JsonResponse;

final class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly PlatformStatsService $statsService,
        private readonly AdminTenantService $tenantService,
        private readonly AdminUserService $userService,
    ) {}

    /**
     * Get platform statistics.
     * GET /admin/dashboard/stats
     */
    public function stats(): JsonResponse
    {
        $stats = $this->statsService->getStats();

        return $this->success($stats->toArray(), 'Platform statistics retrieved successfully');
    }

    /**
     * Get revenue statistics.
     * GET /admin/dashboard/revenue
     */
    public function revenue(): JsonResponse
    {
        $revenue = $this->statsService->getRevenueStats();

        return $this->success($revenue, 'Revenue statistics retrieved successfully');
    }

    /**
     * Get growth metrics.
     * GET /admin/dashboard/growth
     */
    public function growth(): JsonResponse
    {
        $growth = $this->statsService->getGrowthMetrics();

        return $this->success($growth, 'Growth metrics retrieved successfully');
    }

    /**
     * Get activity metrics.
     * GET /admin/dashboard/activity
     */
    public function activity(): JsonResponse
    {
        $activity = $this->statsService->getActivityMetrics();

        return $this->success($activity, 'Activity metrics retrieved successfully');
    }

    /**
     * Get combined dashboard data.
     * GET /admin/dashboard
     */
    public function index(): JsonResponse
    {
        $stats = $this->statsService->getStats();
        $revenue = $this->statsService->getRevenueStats();
        $growth = $this->statsService->getGrowthMetrics();
        $activity = $this->statsService->getActivityMetrics();

        return $this->success([
            'stats' => $stats->toArray(),
            'revenue' => $revenue,
            'growth' => $growth,
            'activity' => $activity,
        ], 'Dashboard data retrieved successfully');
    }

    /**
     * Get tenant overview for dashboard.
     * GET /admin/dashboard/tenants
     */
    public function tenants(): JsonResponse
    {
        $stats = $this->tenantService->getStats();

        return $this->success($stats, 'Tenant overview retrieved successfully');
    }

    /**
     * Get user overview for dashboard.
     * GET /admin/dashboard/users
     */
    public function users(): JsonResponse
    {
        $stats = $this->userService->getStats();

        return $this->success($stats, 'User overview retrieved successfully');
    }
}
