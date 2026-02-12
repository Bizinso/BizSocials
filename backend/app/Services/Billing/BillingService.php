<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Data\Billing\BillingSummaryData;
use App\Data\Billing\PaymentMethodData;
use App\Data\Billing\SubscriptionData;
use App\Data\Billing\UsageData;
use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;

final class BillingService extends BaseService
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly InvoiceService $invoiceService,
        private readonly PaymentMethodService $paymentMethodService,
    ) {}

    /**
     * Get billing summary for a tenant.
     */
    public function getBillingSummary(Tenant $tenant): BillingSummaryData
    {
        // Get current subscription
        $subscription = $this->subscriptionService->getCurrentForTenant($tenant);
        $subscriptionData = $subscription !== null
            ? SubscriptionData::fromModel($subscription)
            : null;

        // Get next billing info
        $nextBillingDate = null;
        $nextBillingAmount = null;
        if ($subscription !== null && !$subscription->cancel_at_period_end) {
            $nextBillingDate = $subscription->current_period_end?->toIso8601String();
            $nextBillingAmount = number_format((float) $subscription->amount, 2, '.', '');
        }

        // Get invoice stats
        $totalInvoices = $this->invoiceService->getCountForTenant($tenant);
        $totalPaid = $this->invoiceService->getTotalPaidForTenant($tenant);

        // Get default payment method
        $defaultMethod = $this->paymentMethodService->getDefaultForTenant($tenant);
        $defaultMethodData = $defaultMethod !== null
            ? PaymentMethodData::fromModel($defaultMethod)
            : null;

        return new BillingSummaryData(
            current_subscription: $subscriptionData,
            next_billing_date: $nextBillingDate,
            next_billing_amount: $nextBillingAmount,
            total_invoices: $totalInvoices,
            total_paid: number_format($totalPaid, 2, '.', ''),
            default_payment_method: $defaultMethodData,
        );
    }

    /**
     * Get usage statistics for a tenant.
     */
    public function getUsage(Tenant $tenant): UsageData
    {
        // Get plan limits
        $plan = $tenant->plan;
        $workspacesLimit = $plan?->getLimit('workspaces') ?? 3;
        $socialAccountsLimit = $plan?->getLimit('social_accounts') ?? 10;
        $teamMembersLimit = $plan?->getLimit('team_members') ?? 5;
        $postsLimit = $plan?->getLimit('posts_per_month');

        // Get actual usage (only active workspaces, not deleted)
        $workspacesUsed = Workspace::where('tenant_id', $tenant->id)
            ->active()
            ->count();

        // Social accounts count (across all workspaces)
        $socialAccountsUsed = \App\Models\Social\SocialAccount::whereHas('workspace', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })->count();

        // Team members count
        $teamMembersUsed = User::where('tenant_id', $tenant->id)->count();

        // Posts this month
        $postsThisMonth = \App\Models\Content\Post::whereHas('workspace', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return new UsageData(
            workspaces_used: $workspacesUsed,
            workspaces_limit: $workspacesLimit === -1 ? null : $workspacesLimit,
            social_accounts_used: $socialAccountsUsed,
            social_accounts_limit: $socialAccountsLimit === -1 ? null : $socialAccountsLimit,
            team_members_used: $teamMembersUsed,
            team_members_limit: $teamMembersLimit === -1 ? null : $teamMembersLimit,
            posts_this_month: $postsThisMonth,
            posts_limit: $postsLimit === -1 ? null : $postsLimit,
        );
    }

    /**
     * Get available upgrade options for a tenant.
     *
     * @return Collection<int, PlanDefinition>
     */
    public function getUpgradeOptions(Tenant $tenant): Collection
    {
        $currentPlan = $tenant->plan;
        $currentSortOrder = $currentPlan?->sort_order ?? 0;

        // Get all public active plans with higher sort_order
        return PlanDefinition::active()
            ->public()
            ->where('sort_order', '>', $currentSortOrder)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get all available plans.
     *
     * @return Collection<int, PlanDefinition>
     */
    public function getAvailablePlans(): Collection
    {
        return PlanDefinition::active()
            ->public()
            ->orderBy('sort_order')
            ->get();
    }
}
