<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Data\Admin\PlatformStatsData;
use App\Enums\Billing\SubscriptionStatus;
use App\Enums\Tenant\TenantStatus;
use App\Enums\User\UserStatus;
use App\Models\Billing\Subscription;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

final class PlatformStatsService extends BaseService
{
    /**
     * Get comprehensive platform statistics.
     */
    public function getStats(): PlatformStatsData
    {
        // Tenant stats
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', TenantStatus::ACTIVE)->count();
        $suspendedTenants = Tenant::where('status', TenantStatus::SUSPENDED)->count();

        // User stats
        $totalUsers = User::count();
        $activeUsers = User::where('status', UserStatus::ACTIVE)->count();

        // Workspace stats
        $totalWorkspaces = Workspace::count();

        // Subscription stats
        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::where('status', SubscriptionStatus::ACTIVE)->count();
        $trialSubscriptions = Subscription::whereNotNull('trial_end')
            ->where('trial_end', '>', now())
            ->count();

        // Tenants by status
        $tenantsByStatus = [];
        foreach (TenantStatus::cases() as $status) {
            $tenantsByStatus[$status->value] = Tenant::where('status', $status)->count();
        }

        // Tenants by plan
        $tenantsByPlan = Tenant::selectRaw('COALESCE(plan_id, \'none\') as plan_id, COUNT(*) as count')
            ->groupBy('plan_id')
            ->pluck('count', 'plan_id')
            ->toArray();

        // Users by status
        $usersByStatus = [];
        foreach (UserStatus::cases() as $status) {
            $usersByStatus[$status->value] = User::where('status', $status)->count();
        }

        // Subscriptions by status
        $subscriptionsByStatus = [];
        foreach (SubscriptionStatus::cases() as $status) {
            $subscriptionsByStatus[$status->value] = Subscription::where('status', $status)->count();
        }

        // Signups by month (last 12 months)
        $signupsByMonth = $this->getSignupsByMonth(12);

        return new PlatformStatsData(
            total_tenants: $totalTenants,
            active_tenants: $activeTenants,
            suspended_tenants: $suspendedTenants,
            total_users: $totalUsers,
            active_users: $activeUsers,
            total_workspaces: $totalWorkspaces,
            total_subscriptions: $totalSubscriptions,
            active_subscriptions: $activeSubscriptions,
            trial_subscriptions: $trialSubscriptions,
            tenants_by_status: $tenantsByStatus,
            tenants_by_plan: $tenantsByPlan,
            users_by_status: $usersByStatus,
            signups_by_month: $signupsByMonth,
            subscriptions_by_status: $subscriptionsByStatus,
            generated_at: now()->toIso8601String(),
        );
    }

    /**
     * Get signups by month for the last N months.
     *
     * @return array<string, int>
     */
    private function getSignupsByMonth(int $months): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();

        // Use database-agnostic approach with strftime for SQLite and DATE_FORMAT for MySQL
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $signups = Tenant::selectRaw("strftime('%Y-%m', created_at) as month, COUNT(*) as count")
                ->where('created_at', '>=', $startDate)
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('count', 'month')
                ->toArray();
        } else {
            $signups = Tenant::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
                ->where('created_at', '>=', $startDate)
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('count', 'month')
                ->toArray();
        }

        // Fill in missing months with zero
        $result = [];
        $currentMonth = $startDate->copy();
        while ($currentMonth <= now()) {
            $monthKey = $currentMonth->format('Y-m');
            $result[$monthKey] = $signups[$monthKey] ?? 0;
            $currentMonth->addMonth();
        }

        return $result;
    }

    /**
     * Get revenue statistics.
     *
     * @return array<string, mixed>
     */
    public function getRevenueStats(): array
    {
        // Monthly recurring revenue
        $mrr = Subscription::where('status', SubscriptionStatus::ACTIVE)
            ->where('billing_cycle', 'monthly')
            ->sum('amount');

        // Annual recurring revenue (converted to monthly)
        $arr = Subscription::where('status', SubscriptionStatus::ACTIVE)
            ->where('billing_cycle', 'yearly')
            ->sum('amount');

        $totalMrr = $mrr + ($arr / 12);

        return [
            'mrr' => round($totalMrr, 2),
            'arr' => round($totalMrr * 12, 2),
            'monthly_subscriptions' => Subscription::where('status', SubscriptionStatus::ACTIVE)
                ->where('billing_cycle', 'monthly')
                ->count(),
            'yearly_subscriptions' => Subscription::where('status', SubscriptionStatus::ACTIVE)
                ->where('billing_cycle', 'yearly')
                ->count(),
        ];
    }

    /**
     * Get growth metrics.
     *
     * @return array<string, mixed>
     */
    public function getGrowthMetrics(): array
    {
        $now = now();
        $lastMonth = $now->copy()->subMonth();

        // New tenants this month vs last month
        $newTenantsThisMonth = Tenant::where('created_at', '>=', $now->startOfMonth())->count();
        $newTenantsLastMonth = Tenant::whereBetween('created_at', [
            $lastMonth->startOfMonth(),
            $lastMonth->endOfMonth(),
        ])->count();

        $tenantGrowth = $newTenantsLastMonth > 0
            ? round((($newTenantsThisMonth - $newTenantsLastMonth) / $newTenantsLastMonth) * 100, 2)
            : 0;

        // New users this month vs last month
        $newUsersThisMonth = User::where('created_at', '>=', $now->startOfMonth())->count();
        $newUsersLastMonth = User::whereBetween('created_at', [
            $lastMonth->startOfMonth(),
            $lastMonth->endOfMonth(),
        ])->count();

        $userGrowth = $newUsersLastMonth > 0
            ? round((($newUsersThisMonth - $newUsersLastMonth) / $newUsersLastMonth) * 100, 2)
            : 0;

        // Churn rate (cancelled subscriptions / total subscriptions)
        $cancelledThisMonth = Subscription::where('cancelled_at', '>=', $now->startOfMonth())->count();
        $totalActiveAtStart = Subscription::where('created_at', '<', $now->startOfMonth())
            ->where('status', SubscriptionStatus::ACTIVE)
            ->count();

        $churnRate = $totalActiveAtStart > 0
            ? round(($cancelledThisMonth / $totalActiveAtStart) * 100, 2)
            : 0;

        return [
            'new_tenants_this_month' => $newTenantsThisMonth,
            'new_tenants_last_month' => $newTenantsLastMonth,
            'tenant_growth_percent' => $tenantGrowth,
            'new_users_this_month' => $newUsersThisMonth,
            'new_users_last_month' => $newUsersLastMonth,
            'user_growth_percent' => $userGrowth,
            'churn_rate_percent' => $churnRate,
        ];
    }

    /**
     * Get activity metrics.
     *
     * @return array<string, mixed>
     */
    public function getActivityMetrics(): array
    {
        return [
            'active_users_24h' => User::where('last_active_at', '>=', now()->subDay())->count(),
            'active_users_7d' => User::where('last_active_at', '>=', now()->subWeek())->count(),
            'active_users_30d' => User::where('last_active_at', '>=', now()->subMonth())->count(),
            'logins_today' => User::where('last_login_at', '>=', now()->startOfDay())->count(),
        ];
    }
}
