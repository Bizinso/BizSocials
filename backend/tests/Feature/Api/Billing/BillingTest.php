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
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->plan = PlanDefinition::factory()->professional()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->member = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::MEMBER,
    ]);
});

describe('GET /api/v1/billing/summary', function () {
    it('returns billing summary without subscription', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/summary');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'current_subscription',
                    'next_billing_date',
                    'next_billing_amount',
                    'total_invoices',
                    'total_paid',
                    'default_payment_method',
                ],
            ])
            ->assertJsonPath('data.current_subscription', null);
    });

    it('returns billing summary with subscription', function () {
        $subscription = Subscription::factory()
            ->forTenant($this->tenant)
            ->forPlan($this->plan)
            ->active()
            ->create();

        $paymentMethod = PaymentMethod::factory()
            ->forTenant($this->tenant)
            ->default()
            ->create();

        Invoice::factory()
            ->forTenant($this->tenant)
            ->paid()
            ->count(3)
            ->create(['total' => 1000]);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/summary');

        $response->assertOk()
            ->assertJsonPath('data.current_subscription.id', $subscription->id)
            ->assertJsonPath('data.default_payment_method.id', $paymentMethod->id)
            ->assertJsonPath('data.total_invoices', 3);
    });

    it('allows member to view billing summary', function () {
        Sanctum::actingAs($this->member);

        $response = $this->getJson('/api/v1/billing/summary');

        $response->assertOk();
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/billing/summary');

        $response->assertUnauthorized();
    });
});

describe('GET /api/v1/billing/usage', function () {
    it('returns usage statistics', function () {
        $this->tenant->update(['plan_id' => $this->plan->id]);

        // Create some usage data
        Workspace::factory()
            ->for($this->tenant)
            ->count(2)
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/usage');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'workspaces_used',
                    'workspaces_limit',
                    'social_accounts_used',
                    'social_accounts_limit',
                    'team_members_used',
                    'team_members_limit',
                    'posts_this_month',
                    'posts_limit',
                ],
            ]);
    });

    it('returns correct workspace count', function () {
        Workspace::factory()
            ->for($this->tenant)
            ->active()
            ->count(3)
            ->create();

        // Deleted workspace shouldn't be counted (use deleted status instead of archived_at)
        Workspace::factory()
            ->for($this->tenant)
            ->deleted()
            ->create();

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/usage');

        $response->assertOk()
            ->assertJsonPath('data.workspaces_used', 3);
    });

    it('returns correct team members count', function () {
        User::factory()
            ->count(3)
            ->create(['tenant_id' => $this->tenant->id]);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/usage');

        $response->assertOk()
            // 3 new users + owner + member from beforeEach = 5
            ->assertJsonPath('data.team_members_used', 5);
    });

    it('allows member to view usage', function () {
        Sanctum::actingAs($this->member);

        $response = $this->getJson('/api/v1/billing/usage');

        $response->assertOk();
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/billing/usage');

        $response->assertUnauthorized();
    });
});

describe('GET /api/v1/billing/plans', function () {
    it('returns available plans', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/plans');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'description',
                        'price_inr_monthly',
                        'price_inr_yearly',
                        'price_usd_monthly',
                        'price_usd_yearly',
                        'trial_days',
                        'features',
                        'yearly_discount_percent',
                    ],
                ],
            ]);
    });

    it('does not return inactive plans', function () {
        // Create inactive plan with unique code
        $inactivePlan = PlanDefinition::factory()->enterprise()->inactive()->create();

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/plans');

        $response->assertOk();
        // Inactive plan should not be in results
        $plans = collect($response->json('data'));
        expect($plans->where('id', $inactivePlan->id)->count())->toBe(0);
    });

    it('does not return private plans', function () {
        // Create a private plan with unique code
        $privatePlan = PlanDefinition::factory()->starter()->private()->create();

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/billing/plans');

        $response->assertOk();
        // Private plan should not be in results
        $plans = collect($response->json('data'));
        expect($plans->where('id', $privatePlan->id)->count())->toBe(0);
    });

    it('allows member to view plans', function () {
        Sanctum::actingAs($this->member);

        $response = $this->getJson('/api/v1/billing/plans');

        $response->assertOk();
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/billing/plans');

        $response->assertUnauthorized();
    });
});
