<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\Analytics\ActivityCategory;
use App\Enums\Analytics\ActivityType;
use App\Models\Analytics\UserActivityLog;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * UsageAnalyticsService
 *
 * Tracks and analyzes user activity and feature usage.
 * Handles:
 * - Activity logging and tracking
 * - Feature adoption metrics
 * - User activity summaries
 * - Workspace usage analysis
 * - Active user counting
 */
final class UsageAnalyticsService extends BaseService
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
    ) {}

    /**
     * Track a user activity event.
     *
     * Records user interactions for analytics and usage tracking.
     * Automatically captures device info from the current request.
     *
     * @param User $user The user performing the activity
     * @param ActivityType $type The type of activity
     * @param string|null $workspaceId Optional workspace context
     * @param string|null $resourceType Type of resource involved
     * @param string|null $resourceId ID of the resource involved
     * @param array<string, mixed> $metadata Additional metadata
     * @return UserActivityLog The created activity log
     */
    public function trackActivity(
        User $user,
        ActivityType $type,
        ?string $workspaceId = null,
        ?string $resourceType = null,
        ?string $resourceId = null,
        array $metadata = []
    ): UserActivityLog {
        $deviceInfo = $this->extractDeviceInfo();

        $activityLog = UserActivityLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'workspace_id' => $workspaceId,
            'activity_type' => $type,
            'activity_category' => $type->category(),
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'page_url' => Request::fullUrl(),
            'referrer_url' => Request::header('Referer'),
            'session_id' => session()->getId(),
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'os' => $deviceInfo['os'],
            'metadata' => $metadata,
        ]);

        $this->log('Activity tracked', [
            'user_id' => $user->id,
            'activity_type' => $type->value,
            'workspace_id' => $workspaceId,
        ]);

        return $activityLog;
    }

    /**
     * Get feature adoption metrics for a tenant.
     *
     * Analyzes which features are being used and by how many users.
     *
     * @param string $tenantId The tenant UUID
     * @param string $period Period string (e.g., '7d', '30d')
     * @return array<string, mixed> Feature adoption data
     */
    public function getFeatureAdoption(string $tenantId, string $period = '30d'): array
    {
        $dateRange = $this->analyticsService->parsePeriod($period);
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        // Get total active users in the period
        $totalActiveUsers = UserActivityLog::forTenant($tenantId)
            ->inDateRange($start, $end)
            ->distinct('user_id')
            ->count('user_id');

        // Get activity counts by category
        $categoryStats = UserActivityLog::forTenant($tenantId)
            ->inDateRange($start, $end)
            ->select('activity_category')
            ->selectRaw('COUNT(*) as activity_count')
            ->selectRaw('COUNT(DISTINCT user_id) as unique_users')
            ->groupBy('activity_category')
            ->get()
            ->keyBy(fn ($item) => $item->activity_category->value);

        // Get activity counts by type
        $typeStats = UserActivityLog::forTenant($tenantId)
            ->inDateRange($start, $end)
            ->select('activity_type')
            ->selectRaw('COUNT(*) as activity_count')
            ->selectRaw('COUNT(DISTINCT user_id) as unique_users')
            ->groupBy('activity_type')
            ->get()
            ->keyBy(fn ($item) => $item->activity_type->value);

        // Calculate feature adoption rates
        $featureAdoption = [];
        foreach (ActivityCategory::cases() as $category) {
            $stats = $categoryStats->get($category->value);
            $uniqueUsers = $stats?->unique_users ?? 0;
            $adoptionRate = $totalActiveUsers > 0
                ? round(($uniqueUsers / $totalActiveUsers) * 100, 2)
                : 0.0;

            $featureAdoption[$category->value] = [
                'category' => $category->value,
                'label' => $category->label(),
                'unique_users' => $uniqueUsers,
                'activity_count' => $stats?->activity_count ?? 0,
                'adoption_rate' => $adoptionRate,
            ];
        }

        // Get detailed type breakdown
        $typeBreakdown = [];
        foreach (ActivityType::cases() as $type) {
            $stats = $typeStats->get($type->value);
            if ($stats !== null) {
                $typeBreakdown[$type->value] = [
                    'type' => $type->value,
                    'label' => $type->label(),
                    'category' => $type->category()->value,
                    'unique_users' => $stats->unique_users,
                    'activity_count' => $stats->activity_count,
                ];
            }
        }

        // Sort by activity count
        uasort($typeBreakdown, fn ($a, $b) => $b['activity_count'] <=> $a['activity_count']);

        $this->log('Feature adoption retrieved', [
            'tenant_id' => $tenantId,
            'period' => $period,
            'total_active_users' => $totalActiveUsers,
        ]);

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'total_active_users' => $totalActiveUsers,
            'by_category' => array_values($featureAdoption),
            'by_type' => array_values($typeBreakdown),
        ];
    }

    /**
     * Get activity summary for a specific user.
     *
     * Returns aggregated activity data for a user.
     *
     * @param string $userId The user UUID
     * @param string $period Period string (e.g., '7d', '30d')
     * @return array<string, mixed> User activity summary
     */
    public function getUserActivitySummary(string $userId, string $period = '30d'): array
    {
        $dateRange = $this->analyticsService->parsePeriod($period);
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        // Get total activity count
        $totalActivities = UserActivityLog::forUser($userId)
            ->inDateRange($start, $end)
            ->count();

        // Get activity by category
        $byCategory = UserActivityLog::forUser($userId)
            ->inDateRange($start, $end)
            ->select('activity_category')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('activity_category')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->activity_category->value => $item->count])
            ->toArray();

        // Get daily activity trend
        $dailyTrend = UserActivityLog::forUser($userId)
            ->inDateRange($start, $end)
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as count')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->date => $item->count])
            ->toArray();

        // Get unique session count
        $uniqueSessions = UserActivityLog::forUser($userId)
            ->inDateRange($start, $end)
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->count('session_id');

        // Get recent activities
        $recentActivities = UserActivityLog::forUser($userId)
            ->inDateRange($start, $end)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (UserActivityLog $log) => [
                'type' => $log->activity_type->value,
                'label' => $log->activity_type->label(),
                'category' => $log->activity_category->value,
                'workspace_id' => $log->workspace_id,
                'resource_type' => $log->resource_type,
                'resource_id' => $log->resource_id,
                'created_at' => $log->created_at->toIso8601String(),
            ]);

        // Get device breakdown
        $deviceBreakdown = UserActivityLog::forUser($userId)
            ->inDateRange($start, $end)
            ->whereNotNull('device_type')
            ->select('device_type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('device_type')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->device_type => $item->count])
            ->toArray();

        $this->log('User activity summary retrieved', [
            'user_id' => $userId,
            'period' => $period,
            'total_activities' => $totalActivities,
        ]);

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'summary' => [
                'total_activities' => $totalActivities,
                'unique_sessions' => $uniqueSessions,
                'avg_activities_per_day' => round($totalActivities / max($start->diffInDays($end), 1), 2),
            ],
            'by_category' => $byCategory,
            'daily_trend' => $dailyTrend,
            'device_breakdown' => $deviceBreakdown,
            'recent_activities' => $recentActivities,
        ];
    }

    /**
     * Get usage summary for a workspace.
     *
     * Returns aggregated usage data for a workspace.
     *
     * @param string $workspaceId The workspace UUID
     * @param string $period Period string (e.g., '7d', '30d')
     * @return array<string, mixed> Workspace usage summary
     */
    public function getWorkspaceUsageSummary(string $workspaceId, string $period = '30d'): array
    {
        $dateRange = $this->analyticsService->parsePeriod($period);
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        // Get total activity count
        $totalActivities = UserActivityLog::forWorkspace($workspaceId)
            ->inDateRange($start, $end)
            ->count();

        // Get unique active users
        $uniqueUsers = UserActivityLog::forWorkspace($workspaceId)
            ->inDateRange($start, $end)
            ->distinct('user_id')
            ->count('user_id');

        // Get activity by category
        $byCategory = UserActivityLog::forWorkspace($workspaceId)
            ->inDateRange($start, $end)
            ->select('activity_category')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COUNT(DISTINCT user_id) as unique_users')
            ->groupBy('activity_category')
            ->get()
            ->map(fn ($item) => [
                'category' => $item->activity_category->value,
                'label' => $item->activity_category->label(),
                'count' => $item->count,
                'unique_users' => $item->unique_users,
            ]);

        // Get daily activity trend
        $dailyTrend = UserActivityLog::forWorkspace($workspaceId)
            ->inDateRange($start, $end)
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as activities')
            ->selectRaw('COUNT(DISTINCT user_id) as active_users')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(fn ($item) => [
                'date' => $item->date,
                'activities' => $item->activities,
                'active_users' => $item->active_users,
            ]);

        // Get top users by activity
        $topUsers = UserActivityLog::forWorkspace($workspaceId)
            ->inDateRange($start, $end)
            ->select('user_id')
            ->selectRaw('COUNT(*) as activity_count')
            ->groupBy('user_id')
            ->orderByDesc('activity_count')
            ->limit(10)
            ->with('user:id,first_name,last_name,email')
            ->get()
            ->map(fn ($item) => [
                'user_id' => $item->user_id,
                'user_name' => $item->user?->full_name ?? 'Unknown',
                'activity_count' => $item->activity_count,
            ]);

        // Get most common activities
        $topActivities = UserActivityLog::forWorkspace($workspaceId)
            ->inDateRange($start, $end)
            ->select('activity_type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('activity_type')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'type' => $item->activity_type->value,
                'label' => $item->activity_type->label(),
                'count' => $item->count,
            ]);

        $this->log('Workspace usage summary retrieved', [
            'workspace_id' => $workspaceId,
            'period' => $period,
            'total_activities' => $totalActivities,
            'unique_users' => $uniqueUsers,
        ]);

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'summary' => [
                'total_activities' => $totalActivities,
                'unique_active_users' => $uniqueUsers,
                'avg_activities_per_day' => round($totalActivities / max($start->diffInDays($end), 1), 2),
                'avg_activities_per_user' => $uniqueUsers > 0
                    ? round($totalActivities / $uniqueUsers, 2)
                    : 0.0,
            ],
            'by_category' => $byCategory,
            'daily_trend' => $dailyTrend,
            'top_users' => $topUsers,
            'top_activities' => $topActivities,
        ];
    }

    /**
     * Get count of active users for a tenant.
     *
     * Returns the number of unique users with activity in the period.
     *
     * @param string $tenantId The tenant UUID
     * @param string $period Period string (e.g., '7d', '30d')
     * @return int Count of active users
     */
    public function getActiveUsersCount(string $tenantId, string $period = '7d'): int
    {
        $dateRange = $this->analyticsService->parsePeriod($period);

        $count = UserActivityLog::forTenant($tenantId)
            ->inDateRange($dateRange['start'], $dateRange['end'])
            ->distinct('user_id')
            ->count('user_id');

        $this->log('Active users count retrieved', [
            'tenant_id' => $tenantId,
            'period' => $period,
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Extract device information from the current request.
     *
     * @return array{device_type: string|null, browser: string|null, os: string|null}
     */
    private function extractDeviceInfo(): array
    {
        $userAgent = Request::userAgent() ?? '';

        // Simple device type detection
        $deviceType = match (true) {
            str_contains(strtolower($userAgent), 'mobile') => 'mobile',
            str_contains(strtolower($userAgent), 'tablet') => 'tablet',
            str_contains(strtolower($userAgent), 'ipad') => 'tablet',
            default => 'desktop',
        };

        // Simple browser detection
        $browser = match (true) {
            str_contains($userAgent, 'Chrome') && !str_contains($userAgent, 'Edg') => 'Chrome',
            str_contains($userAgent, 'Firefox') => 'Firefox',
            str_contains($userAgent, 'Safari') && !str_contains($userAgent, 'Chrome') => 'Safari',
            str_contains($userAgent, 'Edg') => 'Edge',
            str_contains($userAgent, 'Opera') || str_contains($userAgent, 'OPR') => 'Opera',
            default => null,
        };

        // Simple OS detection
        $os = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iOS') || str_contains($userAgent, 'iPhone') => 'iOS',
            default => null,
        };

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'os' => $os,
        ];
    }
}
