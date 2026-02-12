<?php

declare(strict_types=1);

use App\Enums\Billing\BillingCycle;
use App\Enums\Billing\SubscriptionStatus;
use App\Enums\User\TenantRole;
use App\Models\Billing\Subscription;
use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Billing\RazorpayService;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->plan = PlanDefinition::factory()->professional()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->admin = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::ADMIN,
    ]);
    $this->member = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::MEMBER,
    ]);

    // Mock RazorpayService to avoid real API calls in tests
    $razorpayMock = Mockery::mock(RazorpayService::class);
    $razorpayMock->shouldReceive('createCustomer')->andReturn('cust_test_123');
    $razorpayMock->shouldReceive('createSubscription')->andReturn((object) ['id' => 'sub_test_123']);
    $razorpayMock->shouldReceive('cancelSubscription')->andReturn(null);
    $this->app->instance(RazorpayService::class, $razorpayMock);
});

describe('GET /api/v1/billing/subscription', function () {
    it('returns null when no subscription exists', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/subscription');

        $response->assertOk()
            ->assertJsonPath('data', null);
    });

    it('returns current subscription', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/subscription');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'tenant_id',
                    'plan_id',
                    'plan_name',
                    'status',
                    'billing_cycle',
                    'currency',
                    'amount',
                    'current_period_start',
                    'current_period_end',
                    'is_on_trial',
                    'trial_days_remaining',
                    'days_until_renewal',
                    'cancel_at_period_end',
                    'created_at',
                ],
            ])
            ->assertJsonPath('data.id', $subscription->id);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/billing/subscription');

        $response->assertUnauthorized();
    });

    it('allows any tenant member to view subscription', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        Sanctum::actingAs($this->member);

        $response = $this->getJson('/api/v1/billing/subscription');

        $response->assertOk()
            ->assertJsonPath('data.id', $subscription->id);
    });
});

describe('POST /api/v1/billing/subscription', function () {
    it('creates subscription for owner', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/subscription', [
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'monthly',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.plan_id', $this->plan->id)
            ->assertJsonPath('data.status', SubscriptionStatus::ACTIVE->value);
    });

    it('denies admin from creating subscription', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/billing/subscription', [
            'plan_id' => $this->plan->id,
        ]);

        $response->assertForbidden();
    });

    it('denies member from creating subscription', function () {
        Sanctum::actingAs($this->member);

        $response = $this->postJson('/api/v1/billing/subscription', [
            'plan_id' => $this->plan->id,
        ]);

        $response->assertForbidden();
    });

    it('fails when subscription already exists', function () {
        Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/subscription', [
            'plan_id' => $this->plan->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['subscription']);
    });

    it('validates plan_id is required', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/subscription', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_id']);
    });

    it('validates plan_id exists', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/subscription', [
            'plan_id' => '00000000-0000-0000-0000-000000000000',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_id']);
    });

    it('creates yearly subscription', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/subscription', [
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'yearly',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.billing_cycle', BillingCycle::YEARLY->value);
    });
});

describe('PUT /api/v1/billing/subscription/plan', function () {
    it('changes plan for owner', function () {
        Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        $newPlan = PlanDefinition::factory()->business()->create();

        Sanctum::actingAs($this->owner);

        $response = $this->putJson('/api/v1/billing/subscription/plan', [
            'plan_id' => $newPlan->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.plan_id', $newPlan->id);
    });

    it('denies admin from changing plan', function () {
        Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        $newPlan = PlanDefinition::factory()->business()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->putJson('/api/v1/billing/subscription/plan', [
            'plan_id' => $newPlan->id,
        ]);

        $response->assertForbidden();
    });

    it('returns 404 when no subscription exists', function () {
        $newPlan = PlanDefinition::factory()->business()->create();

        Sanctum::actingAs($this->owner);

        $response = $this->putJson('/api/v1/billing/subscription/plan', [
            'plan_id' => $newPlan->id,
        ]);

        $response->assertNotFound();
    });

    it('fails when changing to same plan', function () {
        Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->putJson('/api/v1/billing/subscription/plan', [
            'plan_id' => $this->plan->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_id']);
    });
});

describe('POST /api/v1/billing/subscription/cancel', function () {
    it('cancels subscription for owner', function () {
        Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/subscription/cancel');

        $response->assertOk()
            ->assertJsonPath('data.cancel_at_period_end', true);
    });

    it('cancels subscription immediately when at_period_end is false', function () {
        Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/subscription/cancel', [
            'at_period_end' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', SubscriptionStatus::CANCELLED->value);
    });

    it('denies admin from cancelling subscription', function () {
        Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/billing/subscription/cancel');

        $response->assertForbidden();
    });

    it('fails when already cancelled (returns not found since cancelled subscriptions are not current)', function () {
        Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->cancelled()
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/subscription/cancel');

        // CANCELLED subscriptions are not returned by getCurrentForTenant,
        // so the controller returns 404 (not found)
        $response->assertNotFound();
    });
});

describe('POST /api/v1/billing/subscription/reactivate', function () {
    it('reactivates cancelled subscription for owner', function () {
        Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create([
                'cancelled_at' => now(),
                'cancel_at_period_end' => true,
            ]);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/subscription/reactivate');

        $response->assertOk()
            ->assertJsonPath('data.cancel_at_period_end', false)
            ->assertJsonPath('data.status', SubscriptionStatus::ACTIVE->value);
    });

    it('denies admin from reactivating subscription', function () {
        Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create([
                'cancelled_at' => now(),
                'cancel_at_period_end' => true,
            ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/billing/subscription/reactivate');

        $response->assertForbidden();
    });

    it('fails when subscription is not cancelled', function () {
        Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/billing/subscription/reactivate');

        $response->assertUnprocessable();
    });
});
