<?php

declare(strict_types=1);

use App\Data\Billing\CreateSubscriptionData;
use App\Enums\Billing\BillingCycle;
use App\Enums\Billing\SubscriptionStatus;
use App\Enums\User\TenantRole;
use App\Models\Billing\Subscription;
use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Billing\RazorpayService;
use App\Services\Billing\SubscriptionService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    // Mock RazorpayService to avoid real API calls
    $razorpayMock = Mockery::mock(RazorpayService::class);
    $razorpayMock->shouldReceive('createCustomer')->andReturn('cust_test_123');
    $razorpayMock->shouldReceive('createSubscription')->andReturn((object) ['id' => 'sub_test_123']);
    $razorpayMock->shouldReceive('cancelSubscription')->andReturn(null);
    $this->app->instance(RazorpayService::class, $razorpayMock);

    $this->service = app(SubscriptionService::class);
    $this->tenant = Tenant::factory()->active()->create();
    $this->plan = PlanDefinition::factory()->professional()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
});

describe('getCurrentForTenant', function () {
    it('returns null when no subscription exists', function () {
        $result = $this->service->getCurrentForTenant($this->tenant);

        expect($result)->toBeNull();
    });

    it('returns active subscription', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        $result = $this->service->getCurrentForTenant($this->tenant);

        expect($result)->not->toBeNull();
        expect($result->id)->toBe($subscription->id);
    });

    it('returns pending subscription', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->pending()
            ->create();

        $result = $this->service->getCurrentForTenant($this->tenant);

        expect($result)->not->toBeNull();
        expect($result->id)->toBe($subscription->id);
    });

    it('does not return cancelled subscription', function () {
        Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->cancelled()
            ->create();

        $result = $this->service->getCurrentForTenant($this->tenant);

        expect($result)->toBeNull();
    });
});

describe('create', function () {
    it('creates subscription', function () {
        $data = new CreateSubscriptionData(
            plan_id: $this->plan->id,
            billing_cycle: BillingCycle::MONTHLY,
        );

        $subscription = $this->service->create($this->tenant, $this->plan, $data);

        expect($subscription)->not->toBeNull();
        expect($subscription->tenant_id)->toBe($this->tenant->id);
        expect($subscription->plan_id)->toBe($this->plan->id);
        expect($subscription->status)->toBe(SubscriptionStatus::ACTIVE);
        expect($subscription->billing_cycle)->toBe(BillingCycle::MONTHLY);
    });

    it('creates yearly subscription', function () {
        $data = new CreateSubscriptionData(
            plan_id: $this->plan->id,
            billing_cycle: BillingCycle::YEARLY,
        );

        $subscription = $this->service->create($this->tenant, $this->plan, $data);

        expect($subscription->billing_cycle)->toBe(BillingCycle::YEARLY);
    });

    it('throws exception when subscription already exists', function () {
        Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        $data = new CreateSubscriptionData(
            plan_id: $this->plan->id,
        );

        expect(fn () => $this->service->create($this->tenant, $this->plan, $data))
            ->toThrow(ValidationException::class);
    });

    it('updates tenant plan_id', function () {
        $data = new CreateSubscriptionData(
            plan_id: $this->plan->id,
        );

        $this->service->create($this->tenant, $this->plan, $data);

        $this->tenant->refresh();
        expect($this->tenant->plan_id)->toBe($this->plan->id);
    });

    it('sets trial dates when plan has trial', function () {
        // Use business() to get a specific plan code and avoid conflicts
        $planWithTrial = PlanDefinition::factory()->business()->create(['trial_days' => 14]);

        $data = new CreateSubscriptionData(
            plan_id: $planWithTrial->id,
        );

        $subscription = $this->service->create($this->tenant, $planWithTrial, $data);

        expect($subscription->trial_end)->not->toBeNull();
        expect($subscription->isOnTrial())->toBeTrue();
    });
});

describe('changePlan', function () {
    it('changes subscription plan', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        $newPlan = PlanDefinition::factory()->business()->create();

        $result = $this->service->changePlan($subscription, $newPlan);

        expect($result->plan_id)->toBe($newPlan->id);
    });

    it('throws exception when changing to same plan', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        expect(fn () => $this->service->changePlan($subscription, $this->plan))
            ->toThrow(ValidationException::class);
    });

    it('updates tenant plan_id', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        $newPlan = PlanDefinition::factory()->business()->create();

        $this->service->changePlan($subscription, $newPlan);

        $this->tenant->refresh();
        expect($this->tenant->plan_id)->toBe($newPlan->id);
    });
});

describe('cancel', function () {
    it('cancels subscription at period end', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        $result = $this->service->cancel($subscription, true);

        expect($result->cancel_at_period_end)->toBeTrue();
        expect($result->cancelled_at)->not->toBeNull();
        expect($result->status)->toBe(SubscriptionStatus::ACTIVE);
    });

    it('cancels subscription immediately', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        $result = $this->service->cancel($subscription, false);

        expect($result->status)->toBe(SubscriptionStatus::CANCELLED);
        expect($result->ended_at)->not->toBeNull();
    });

    it('throws exception when already cancelled', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->cancelled()
            ->create();

        expect(fn () => $this->service->cancel($subscription))
            ->toThrow(ValidationException::class);
    });

    it('throws exception when completed', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->completed()
            ->create();

        expect(fn () => $this->service->cancel($subscription))
            ->toThrow(ValidationException::class);
    });
});

describe('reactivate', function () {
    it('reactivates cancelled subscription', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create([
                'cancelled_at' => now(),
                'cancel_at_period_end' => true,
            ]);

        $result = $this->service->reactivate($subscription);

        expect($result->cancel_at_period_end)->toBeFalse();
        expect($result->cancelled_at)->toBeNull();
        // Status should remain active
        expect($result->status)->toBe(SubscriptionStatus::ACTIVE);
    });

    it('throws exception when not cancelled', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        expect(fn () => $this->service->reactivate($subscription))
            ->toThrow(ValidationException::class);
    });

    it('throws exception when subscription has ended', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->cancelled()
            ->create([
                'ended_at' => now(),
            ]);

        expect(fn () => $this->service->reactivate($subscription))
            ->toThrow(ValidationException::class);
    });
});

describe('getHistory', function () {
    it('returns subscription history', function () {
        Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->count(3)
            ->create();

        $history = $this->service->getHistory($this->tenant);

        expect($history)->toHaveCount(3);
    });

    it('returns empty collection when no subscriptions', function () {
        $history = $this->service->getHistory($this->tenant);

        expect($history)->toBeEmpty();
    });

    it('does not include subscriptions from other tenants', function () {
        $otherTenant = Tenant::factory()->create();
        Subscription::factory()
            ->forTenant($otherTenant)
            ->forPlan($this->plan)
            ->count(2)
            ->create();
        Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->create();

        $history = $this->service->getHistory($this->tenant);

        expect($history)->toHaveCount(1);
    });
});
