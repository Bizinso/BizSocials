<?php

declare(strict_types=1);

use App\Enums\User\TenantRole;
use App\Models\Billing\Invoice;
use App\Models\Billing\PaymentMethod;
use App\Models\Billing\Subscription;
use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Billing\BillingService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentMethodService;
use App\Services\Billing\SubscriptionService;

beforeEach(function () {
    $this->subscriptionService = app(SubscriptionService::class);
    $this->invoiceService = app(InvoiceService::class);
    $this->paymentMethodService = app(PaymentMethodService::class);
    $this->service = app(BillingService::class);
    $this->tenant = Tenant::factory()->active()->create();
    $this->plan = PlanDefinition::factory()->professional()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
});

describe('getBillingSummary', function () {
    it('returns summary without subscription', function () {
        $summary = $this->service->getBillingSummary($this->tenant);

        expect($summary->current_subscription)->toBeNull();
        expect($summary->next_billing_date)->toBeNull();
        expect($summary->next_billing_amount)->toBeNull();
    });

    it('returns summary with subscription', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        $summary = $this->service->getBillingSummary($this->tenant);

        expect($summary->current_subscription)->not->toBeNull();
        expect($summary->current_subscription->id)->toBe($subscription->id);
        expect($summary->next_billing_date)->not->toBeNull();
        expect($summary->next_billing_amount)->not->toBeNull();
    });

    it('returns null billing date when cancel_at_period_end', function () {
        Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create([
                'cancel_at_period_end' => true,
            ]);

        $summary = $this->service->getBillingSummary($this->tenant);

        expect($summary->next_billing_date)->toBeNull();
        expect($summary->next_billing_amount)->toBeNull();
    });

    it('includes default payment method', function () {
        $method = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->default()
            ->create();

        $summary = $this->service->getBillingSummary($this->tenant);

        expect($summary->default_payment_method)->not->toBeNull();
        expect($summary->default_payment_method->id)->toBe($method->id);
    });

    it('calculates total invoices', function () {
        Invoice::factory()
            ->forTenant($this->tenant)
            ->count(5)
            ->create();

        $summary = $this->service->getBillingSummary($this->tenant);

        expect($summary->total_invoices)->toBe(5);
    });

    it('calculates total paid', function () {
        Invoice::factory()
            ->forTenant($this->tenant)
            ->paid()
            ->create(['total' => 1000]);
        Invoice::factory()
            ->forTenant($this->tenant)
            ->paid()
            ->create(['total' => 2000]);
        Invoice::factory()
            ->forTenant($this->tenant)
            ->issued()
            ->create(['total' => 500]);

        $summary = $this->service->getBillingSummary($this->tenant);

        expect($summary->total_paid)->toBe('3000.00');
    });
});

describe('getUsage', function () {
    it('returns usage statistics', function () {
        $this->tenant->update(['plan_id' => $this->plan->id]);

        Workspace::factory()
            ->for($this->tenant)
            ->active()
            ->count(2)
            ->create();

        $usage = $this->service->getUsage($this->tenant);

        expect($usage->workspaces_used)->toBe(2);
        expect($usage->team_members_used)->toBe(1); // Just the owner
    });

    it('excludes non-active workspaces', function () {
        Workspace::factory()
            ->for($this->tenant)
            ->active()
            ->count(3)
            ->create();
        // Deleted workspace (uses deleted() factory state which sets status and deleted_at)
        Workspace::factory()
            ->for($this->tenant)
            ->deleted()
            ->create();

        $usage = $this->service->getUsage($this->tenant);

        expect($usage->workspaces_used)->toBe(3);
    });

    it('counts team members', function () {
        User::factory()
            ->count(4)
            ->create(['tenant_id' => $this->tenant->id]);

        $usage = $this->service->getUsage($this->tenant);

        expect($usage->team_members_used)->toBe(5); // 4 + owner
    });
});

describe('getUpgradeOptions', function () {
    it('returns plans with higher sort order', function () {
        // The $this->plan (professional) was created in beforeEach
        // Create another plan with higher sort order
        $higherPlan = PlanDefinition::factory()->business()->create([
            'sort_order' => $this->plan->sort_order + 10,
            'is_active' => true,
            'is_public' => true,
        ]);

        $this->tenant->update(['plan_id' => $this->plan->id]);

        $options = $this->service->getUpgradeOptions($this->tenant);

        // Should contain the higher plan
        expect($options->pluck('id')->contains($higherPlan->id))->toBeTrue();
    });

    it('returns all public active plans when no current plan', function () {
        // Tenant has no plan assigned
        $this->tenant->update(['plan_id' => null]);

        $options = $this->service->getUpgradeOptions($this->tenant);

        // Should return plans with sort_order > 0 (which includes the professional plan)
        expect($options->count())->toBeGreaterThanOrEqual(1);
    });
});

describe('getAvailablePlans', function () {
    it('returns active public plans', function () {
        // Create an inactive plan with unique code
        $inactivePlan = PlanDefinition::factory()->enterprise()->inactive()->create();

        $plans = $this->service->getAvailablePlans();

        // Should only return active plans
        expect($plans->every(fn ($plan) => $plan->is_active))->toBeTrue();
        // Should not include inactive plan
        expect($plans->pluck('id')->contains($inactivePlan->id))->toBeFalse();
    });

    it('excludes private plans', function () {
        // Create a private plan with unique code
        $privatePlan = PlanDefinition::factory()->starter()->private()->create();

        $plans = $this->service->getAvailablePlans();

        // All returned plans should be public
        expect($plans->every(fn ($plan) => $plan->is_public))->toBeTrue();
        // Should not include private plan
        expect($plans->pluck('id')->contains($privatePlan->id))->toBeFalse();
    });

    it('returns plans ordered by sort_order', function () {
        $plans = $this->service->getAvailablePlans();

        // Verify ordering
        $sortOrders = $plans->pluck('sort_order')->toArray();
        $sortedOrders = $sortOrders;
        sort($sortedOrders);
        expect($sortOrders)->toBe($sortedOrders);
    });
});
