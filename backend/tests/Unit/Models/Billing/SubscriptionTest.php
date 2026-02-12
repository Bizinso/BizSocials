<?php

declare(strict_types=1);

/**
 * Subscription Model Unit Tests
 *
 * Tests for the Subscription model which represents
 * tenant subscriptions to plans.
 *
 * @see \App\Models\Billing\Subscription
 */

use App\Enums\Billing\BillingCycle;
use App\Enums\Billing\Currency;
use App\Enums\Billing\SubscriptionStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\Payment;
use App\Models\Billing\Subscription;
use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

test('has correct table name', function (): void {
    $subscription = new Subscription();

    expect($subscription->getTable())->toBe('subscriptions');
});

test('uses uuid primary key', function (): void {
    $subscription = Subscription::factory()->create();

    expect($subscription->id)->not->toBeNull()
        ->and(strlen($subscription->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $subscription = new Subscription();
    $fillable = $subscription->getFillable();

    expect($fillable)->toContain('tenant_id')
        ->and($fillable)->toContain('plan_id')
        ->and($fillable)->toContain('status')
        ->and($fillable)->toContain('billing_cycle')
        ->and($fillable)->toContain('currency')
        ->and($fillable)->toContain('amount')
        ->and($fillable)->toContain('razorpay_subscription_id')
        ->and($fillable)->toContain('razorpay_customer_id')
        ->and($fillable)->toContain('current_period_start')
        ->and($fillable)->toContain('current_period_end')
        ->and($fillable)->toContain('trial_start')
        ->and($fillable)->toContain('trial_end')
        ->and($fillable)->toContain('cancelled_at')
        ->and($fillable)->toContain('cancel_at_period_end')
        ->and($fillable)->toContain('ended_at')
        ->and($fillable)->toContain('metadata');
});

test('status casts to enum', function (): void {
    $subscription = Subscription::factory()->active()->create();

    expect($subscription->status)->toBeInstanceOf(SubscriptionStatus::class)
        ->and($subscription->status)->toBe(SubscriptionStatus::ACTIVE);
});

test('billing_cycle casts to enum', function (): void {
    $subscription = Subscription::factory()->monthly()->create();

    expect($subscription->billing_cycle)->toBeInstanceOf(BillingCycle::class)
        ->and($subscription->billing_cycle)->toBe(BillingCycle::MONTHLY);
});

test('currency casts to enum', function (): void {
    $subscription = Subscription::factory()->inr()->create();

    expect($subscription->currency)->toBeInstanceOf(Currency::class)
        ->and($subscription->currency)->toBe(Currency::INR);
});

test('timestamp fields cast to datetime', function (): void {
    $subscription = Subscription::factory()->create([
        'current_period_start' => now(),
        'current_period_end' => now()->addMonth(),
    ]);

    expect($subscription->current_period_start)->toBeInstanceOf(\Carbon\Carbon::class)
        ->and($subscription->current_period_end)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('metadata casts to array', function (): void {
    $subscription = Subscription::factory()->create([
        'metadata' => ['key' => 'value'],
    ]);

    expect($subscription->metadata)->toBeArray()
        ->and($subscription->metadata['key'])->toBe('value');
});

test('tenant relationship returns belongs to', function (): void {
    $subscription = new Subscription();

    expect($subscription->tenant())->toBeInstanceOf(BelongsTo::class);
});

test('tenant relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $subscription = Subscription::factory()->forTenant($tenant)->create();

    expect($subscription->tenant)->toBeInstanceOf(Tenant::class)
        ->and($subscription->tenant->id)->toBe($tenant->id);
});

test('plan relationship returns belongs to', function (): void {
    $subscription = new Subscription();

    expect($subscription->plan())->toBeInstanceOf(BelongsTo::class);
});

test('plan relationship works correctly', function (): void {
    $plan = PlanDefinition::factory()->create();
    $subscription = Subscription::factory()->forPlan($plan)->create();

    expect($subscription->plan)->toBeInstanceOf(PlanDefinition::class)
        ->and($subscription->plan->id)->toBe($plan->id);
});

test('invoices relationship returns has many', function (): void {
    $subscription = new Subscription();

    expect($subscription->invoices())->toBeInstanceOf(HasMany::class);
});

test('invoices relationship works correctly', function (): void {
    $subscription = Subscription::factory()->create();
    Invoice::factory()->count(3)->forSubscription($subscription)->create();

    expect($subscription->invoices)->toHaveCount(3)
        ->and($subscription->invoices->first())->toBeInstanceOf(Invoice::class);
});

test('payments relationship returns has many', function (): void {
    $subscription = new Subscription();

    expect($subscription->payments())->toBeInstanceOf(HasMany::class);
});

test('payments relationship works correctly', function (): void {
    $subscription = Subscription::factory()->create();
    Payment::factory()->count(2)->forSubscription($subscription)->create();

    expect($subscription->payments)->toHaveCount(2)
        ->and($subscription->payments->first())->toBeInstanceOf(Payment::class);
});

test('scope active filters correctly', function (): void {
    Subscription::factory()->count(3)->active()->create();
    Subscription::factory()->count(2)->cancelled()->create();

    $activeSubscriptions = Subscription::active()->get();

    expect($activeSubscriptions)->toHaveCount(3)
        ->and($activeSubscriptions->every(fn ($s) => $s->status === SubscriptionStatus::ACTIVE))->toBeTrue();
});

test('scope forTenant filters by tenant', function (): void {
    $tenant = Tenant::factory()->create();
    Subscription::factory()->forTenant($tenant)->create();
    Subscription::factory()->count(2)->create();

    $subscriptions = Subscription::forTenant($tenant->id)->get();

    expect($subscriptions)->toHaveCount(1)
        ->and($subscriptions->first()->tenant_id)->toBe($tenant->id);
});

test('isActive returns true only for active status', function (): void {
    $active = Subscription::factory()->active()->create();
    $pending = Subscription::factory()->pending()->create();
    $cancelled = Subscription::factory()->cancelled()->create();

    expect($active->isActive())->toBeTrue()
        ->and($pending->isActive())->toBeFalse()
        ->and($cancelled->isActive())->toBeFalse();
});

test('hasAccess returns true for statuses with access', function (): void {
    $active = Subscription::factory()->active()->create();
    $pending = Subscription::factory()->pending()->create();
    $authenticated = Subscription::factory()->authenticated()->create();
    $halted = Subscription::factory()->halted()->create();

    expect($active->hasAccess())->toBeTrue()
        ->and($pending->hasAccess())->toBeTrue()
        ->and($authenticated->hasAccess())->toBeTrue()
        ->and($halted->hasAccess())->toBeFalse();
});

test('isOnTrial returns true if trial end is in future', function (): void {
    $onTrial = Subscription::factory()->onTrial(14)->create();
    $notOnTrial = Subscription::factory()->create(['trial_end' => null]);
    $trialExpired = Subscription::factory()->create(['trial_end' => now()->subDays(1)]);

    expect($onTrial->isOnTrial())->toBeTrue()
        ->and($notOnTrial->isOnTrial())->toBeFalse()
        ->and($trialExpired->isOnTrial())->toBeFalse();
});

test('trialDaysRemaining calculates correctly', function (): void {
    $subscription = Subscription::factory()->create([
        'trial_end' => now()->addDays(10),
    ]);

    expect($subscription->trialDaysRemaining())->toBeGreaterThanOrEqual(9)
        ->and($subscription->trialDaysRemaining())->toBeLessThanOrEqual(10);
});

test('trialDaysRemaining returns zero when not on trial', function (): void {
    $subscription = Subscription::factory()->create(['trial_end' => null]);

    expect($subscription->trialDaysRemaining())->toBe(0);
});

test('daysUntilRenewal calculates correctly', function (): void {
    $subscription = Subscription::factory()->create([
        'current_period_end' => now()->addDays(15),
    ]);

    expect($subscription->daysUntilRenewal())->toBeGreaterThanOrEqual(14)
        ->and($subscription->daysUntilRenewal())->toBeLessThanOrEqual(15);
});

test('daysUntilRenewal returns zero when no period end', function (): void {
    $subscription = Subscription::factory()->create(['current_period_end' => null]);

    expect($subscription->daysUntilRenewal())->toBe(0);
});

test('cancel at period end sets cancelled_at and flag', function (): void {
    $subscription = Subscription::factory()->active()->create();

    $subscription->cancel(true);

    expect($subscription->cancelled_at)->not->toBeNull()
        ->and($subscription->cancel_at_period_end)->toBeTrue()
        ->and($subscription->status)->toBe(SubscriptionStatus::ACTIVE);
});

test('cancel immediately sets status to cancelled', function (): void {
    $subscription = Subscription::factory()->active()->create();

    $subscription->cancel(false);

    expect($subscription->cancelled_at)->not->toBeNull()
        ->and($subscription->cancel_at_period_end)->toBeFalse()
        ->and($subscription->status)->toBe(SubscriptionStatus::CANCELLED)
        ->and($subscription->ended_at)->not->toBeNull();
});

test('reactivate clears cancellation', function (): void {
    $subscription = Subscription::factory()->create([
        'status' => SubscriptionStatus::CANCELLED,
        'cancelled_at' => now()->subDay(),
        'cancel_at_period_end' => true,
        'ended_at' => null,
    ]);

    $subscription->reactivate();

    expect($subscription->cancelled_at)->toBeNull()
        ->and($subscription->cancel_at_period_end)->toBeFalse()
        ->and($subscription->status)->toBe(SubscriptionStatus::ACTIVE);
});

test('changePlan updates plan_id', function (): void {
    $oldPlan = PlanDefinition::factory()->create();
    $newPlan = PlanDefinition::factory()->create();
    $subscription = Subscription::factory()->forPlan($oldPlan)->create();

    $subscription->changePlan($newPlan);

    expect($subscription->plan_id)->toBe($newPlan->id);
});

test('markAsActive updates status', function (): void {
    $subscription = Subscription::factory()->pending()->create();

    $subscription->markAsActive();

    expect($subscription->status)->toBe(SubscriptionStatus::ACTIVE);
});

test('markAsPending updates status', function (): void {
    $subscription = Subscription::factory()->active()->create();

    $subscription->markAsPending();

    expect($subscription->status)->toBe(SubscriptionStatus::PENDING);
});

test('markAsHalted updates status', function (): void {
    $subscription = Subscription::factory()->active()->create();

    $subscription->markAsHalted();

    expect($subscription->status)->toBe(SubscriptionStatus::HALTED);
});

test('markAsCancelled updates status and timestamps', function (): void {
    $subscription = Subscription::factory()->active()->create();

    $subscription->markAsCancelled();

    expect($subscription->status)->toBe(SubscriptionStatus::CANCELLED)
        ->and($subscription->cancelled_at)->not->toBeNull()
        ->and($subscription->ended_at)->not->toBeNull();
});

test('factory creates valid model', function (): void {
    $subscription = Subscription::factory()->create();

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->id)->not->toBeNull()
        ->and($subscription->tenant_id)->not->toBeNull()
        ->and($subscription->plan_id)->not->toBeNull()
        ->and($subscription->status)->toBeInstanceOf(SubscriptionStatus::class)
        ->and($subscription->billing_cycle)->toBeInstanceOf(BillingCycle::class)
        ->and($subscription->currency)->toBeInstanceOf(Currency::class);
});
