<?php

declare(strict_types=1);

/**
 * PlanDefinition Model Unit Tests
 *
 * Tests for the PlanDefinition model which represents subscription
 * plan definitions with pricing, features, and limits for each plan tier.
 *
 * @see \App\Models\Platform\PlanDefinition
 */

use App\Enums\Platform\PlanCode;
use App\Models\Platform\PlanDefinition;
use App\Models\Platform\PlanLimit;

test('can create plan', function (): void {
    $plan = PlanDefinition::create([
        'code' => PlanCode::STARTER,
        'name' => 'Starter Plan',
        'description' => 'Entry-level plan',
        'price_inr_monthly' => 999.00,
        'price_inr_yearly' => 9590.00,
        'price_usd_monthly' => 15.00,
        'price_usd_yearly' => 144.00,
        'trial_days' => 14,
        'is_active' => true,
        'is_public' => true,
        'sort_order' => 2,
        'features' => ['Feature 1', 'Feature 2'],
    ]);

    expect($plan)->toBeInstanceOf(PlanDefinition::class)
        ->and($plan->code)->toBe(PlanCode::STARTER)
        ->and($plan->name)->toBe('Starter Plan')
        ->and((float) $plan->price_inr_monthly)->toBe(999.00)
        ->and($plan->trial_days)->toBe(14)
        ->and($plan->id)->not->toBeNull();
});

test('code must be unique', function (): void {
    PlanDefinition::factory()->free()->create();

    expect(fn () => PlanDefinition::factory()->free()->create())
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('code casts to enum', function (): void {
    $plan = PlanDefinition::factory()->professional()->create();

    expect($plan->code)->toBeInstanceOf(PlanCode::class)
        ->and($plan->code)->toBe(PlanCode::PROFESSIONAL);
});

test('has many plan limits', function (): void {
    $plan = PlanDefinition::factory()->create();

    PlanLimit::factory()->forPlan($plan)->maxWorkspaces(5)->create();
    PlanLimit::factory()->forPlan($plan)->maxUsers(10)->create();
    PlanLimit::factory()->forPlan($plan)->maxSocialAccounts(15)->create();

    expect($plan->limits)->toHaveCount(3)
        ->and($plan->limits->first())->toBeInstanceOf(PlanLimit::class);
});

test('get limit returns value', function (): void {
    $plan = PlanDefinition::factory()->create();

    PlanLimit::factory()->forPlan($plan)->limit('max_workspaces', 5)->create();

    expect($plan->getLimit('max_workspaces'))->toBe(5);
});

test('get limit returns negative one for unlimited', function (): void {
    $plan = PlanDefinition::factory()->create();

    PlanLimit::factory()->forPlan($plan)->limit('max_posts_per_month', PlanLimit::UNLIMITED)->create();

    expect($plan->getLimit('max_posts_per_month'))->toBe(-1)
        ->and($plan->isLimitUnlimited('max_posts_per_month'))->toBeTrue();
});

test('get limit returns negative one when not found', function (): void {
    $plan = PlanDefinition::factory()->create();

    // No limits created
    expect($plan->getLimit('nonexistent_limit'))->toBe(-1);
});

test('scope active filters correctly', function (): void {
    PlanDefinition::factory()->count(3)->create(['is_active' => true]);
    PlanDefinition::factory()->count(2)->inactive()->create();

    $activePlans = PlanDefinition::active()->get();

    expect($activePlans)->toHaveCount(3)
        ->and($activePlans->every(fn ($plan) => $plan->is_active))->toBeTrue();
});

test('scope public filters correctly', function (): void {
    PlanDefinition::factory()->count(2)->create(['is_public' => true]);
    PlanDefinition::factory()->count(3)->private()->create();

    $publicPlans = PlanDefinition::public()->get();

    expect($publicPlans)->toHaveCount(2)
        ->and($publicPlans->every(fn ($plan) => $plan->is_public))->toBeTrue();
});

test('yearly discount calculated correctly', function (): void {
    // Set config to use INR
    config(['app.default_currency' => 'INR']);

    $plan = PlanDefinition::factory()->create([
        'price_inr_monthly' => 1000.00,
        'price_inr_yearly' => 9600.00, // 12 * 1000 = 12000, 9600 is 20% off
    ]);

    // Expected: ((12000 - 9600) / 12000) * 100 = 20%
    expect($plan->yearly_discount_percent)->toBe(20.0);
});

test('yearly discount returns zero for free plan', function (): void {
    config(['app.default_currency' => 'INR']);

    $plan = PlanDefinition::factory()->free()->create();

    expect($plan->yearly_discount_percent)->toBe(0.0);
});

test('monthly price accessor returns correct currency', function (): void {
    $plan = PlanDefinition::factory()->create([
        'price_inr_monthly' => 1000.00,
        'price_usd_monthly' => 15.00,
    ]);

    config(['app.default_currency' => 'INR']);
    expect($plan->monthly_price)->toBe(1000.00);

    config(['app.default_currency' => 'USD']);
    expect($plan->monthly_price)->toBe(15.00);
});

test('yearly price accessor returns correct currency', function (): void {
    $plan = PlanDefinition::factory()->create([
        'price_inr_yearly' => 10000.00,
        'price_usd_yearly' => 150.00,
    ]);

    config(['app.default_currency' => 'INR']);
    expect($plan->yearly_price)->toBe(10000.00);

    config(['app.default_currency' => 'USD']);
    expect($plan->yearly_price)->toBe(150.00);
});

test('features casts to array', function (): void {
    $features = ['Feature 1', 'Feature 2', 'Feature 3'];

    $plan = PlanDefinition::factory()->withFeatures($features)->create();

    expect($plan->features)->toBeArray()
        ->and($plan->features)->toHaveCount(3)
        ->and($plan->features)->toBe($features);
});

test('metadata casts to array', function (): void {
    $plan = PlanDefinition::factory()->create([
        'metadata' => ['custom_key' => 'custom_value', 'nested' => ['data' => true]],
    ]);

    expect($plan->metadata)->toBeArray()
        ->and($plan->metadata['custom_key'])->toBe('custom_value')
        ->and($plan->metadata['nested']['data'])->toBeTrue();
});

test('get by code returns plan', function (): void {
    $created = PlanDefinition::factory()->starter()->create();

    $found = PlanDefinition::getByCode(PlanCode::STARTER);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($created->id);
});

test('get by code returns null when not found', function (): void {
    // Don't create any plans
    $found = PlanDefinition::getByCode(PlanCode::ENTERPRISE);

    expect($found)->toBeNull();
});

test('get public plans returns active public plans ordered', function (): void {
    PlanDefinition::factory()->free()->create(['sort_order' => 1]);
    PlanDefinition::factory()->starter()->create(['sort_order' => 2]);
    PlanDefinition::factory()->professional()->create(['sort_order' => 3]);
    PlanDefinition::factory()->business()->inactive()->create(['sort_order' => 4]);
    PlanDefinition::factory()->enterprise()->private()->create(['sort_order' => 5]);

    $publicPlans = PlanDefinition::getPublicPlans();

    expect($publicPlans)->toHaveCount(3)
        ->and($publicPlans->first()->code)->toBe(PlanCode::FREE)
        ->and($publicPlans->last()->code)->toBe(PlanCode::PROFESSIONAL);
});

test('boolean casts work correctly', function (): void {
    $activePlan = PlanDefinition::factory()->create(['is_active' => true]);
    $inactivePlan = PlanDefinition::factory()->inactive()->create();
    $publicPlan = PlanDefinition::factory()->create(['is_public' => true]);
    $privatePlan = PlanDefinition::factory()->private()->create();

    expect($activePlan->is_active)->toBeTrue()
        ->and($inactivePlan->is_active)->toBeFalse()
        ->and($publicPlan->is_public)->toBeTrue()
        ->and($privatePlan->is_public)->toBeFalse();
});

test('factory creates valid model', function (): void {
    $plan = PlanDefinition::factory()->create();

    expect($plan)->toBeInstanceOf(PlanDefinition::class)
        ->and($plan->id)->not->toBeNull()
        ->and($plan->code)->toBeInstanceOf(PlanCode::class)
        ->and($plan->name)->toBeString()
        ->and($plan->features)->toBeArray()
        ->and($plan->is_active)->toBeBool()
        ->and($plan->is_public)->toBeBool();
});
