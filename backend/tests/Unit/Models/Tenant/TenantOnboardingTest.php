<?php

declare(strict_types=1);

/**
 * TenantOnboarding Model Unit Tests
 *
 * Tests for the TenantOnboarding model which tracks the
 * onboarding progress for tenants.
 *
 * @see \App\Models\Tenant\TenantOnboarding
 */

use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantOnboarding;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;

test('has correct table name', function (): void {
    $onboarding = new TenantOnboarding();

    expect($onboarding->getTable())->toBe('tenant_onboarding');
});

test('uses uuid primary key', function (): void {
    $onboarding = TenantOnboarding::factory()->create();

    expect($onboarding->id)->not->toBeNull()
        ->and(strlen($onboarding->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $onboarding = new TenantOnboarding();
    $fillable = $onboarding->getFillable();

    expect($fillable)->toContain('tenant_id')
        ->and($fillable)->toContain('current_step')
        ->and($fillable)->toContain('steps_completed')
        ->and($fillable)->toContain('started_at')
        ->and($fillable)->toContain('completed_at')
        ->and($fillable)->toContain('abandoned_at')
        ->and($fillable)->toContain('metadata');
});

test('STEPS constant contains all expected steps', function (): void {
    $steps = TenantOnboarding::STEPS;

    expect($steps)->toBeArray()
        ->and($steps)->toHaveCount(11)
        ->and($steps)->toContain('account_created')
        ->and($steps)->toContain('email_verified')
        ->and($steps)->toContain('organization_completed')
        ->and($steps)->toContain('business_type_selected')
        ->and($steps)->toContain('profile_completed')
        ->and($steps)->toContain('plan_selected')
        ->and($steps)->toContain('payment_completed')
        ->and($steps)->toContain('first_workspace_created')
        ->and($steps)->toContain('first_social_account_connected')
        ->and($steps)->toContain('first_post_created')
        ->and($steps)->toContain('tour_completed');
});

test('steps_completed casts to array', function (): void {
    $onboarding = TenantOnboarding::factory()->inProgress(3)->create();

    expect($onboarding->steps_completed)->toBeArray();
});

test('started_at casts to datetime', function (): void {
    $onboarding = TenantOnboarding::factory()->create();

    expect($onboarding->started_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('completed_at casts to datetime', function (): void {
    $onboarding = TenantOnboarding::factory()->completed()->create();

    expect($onboarding->completed_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('abandoned_at casts to datetime', function (): void {
    $onboarding = TenantOnboarding::factory()->abandoned()->create();

    expect($onboarding->abandoned_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('metadata casts to array', function (): void {
    $onboarding = TenantOnboarding::factory()->withMetadata([
        'referral_source' => 'google',
    ])->create();

    expect($onboarding->metadata)->toBeArray()
        ->and($onboarding->metadata['referral_source'])->toBe('google');
});

test('tenant relationship returns belongs to', function (): void {
    $onboarding = new TenantOnboarding();

    expect($onboarding->tenant())->toBeInstanceOf(BelongsTo::class);
});

test('tenant relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $onboarding = TenantOnboarding::factory()->forTenant($tenant)->create();

    expect($onboarding->tenant)->toBeInstanceOf(Tenant::class)
        ->and($onboarding->tenant->id)->toBe($tenant->id);
});

test('completeStep adds step to completed array', function (): void {
    $onboarding = TenantOnboarding::factory()->justStarted()->create();

    $onboarding->completeStep('email_verified');

    expect($onboarding->steps_completed)->toContain('account_created')
        ->and($onboarding->steps_completed)->toContain('email_verified');
});

test('completeStep updates current step', function (): void {
    $onboarding = TenantOnboarding::factory()->justStarted()->create();

    $onboarding->completeStep('email_verified');

    expect($onboarding->current_step)->toBe('organization_completed');
});

test('completeStep ignores invalid steps', function (): void {
    $onboarding = TenantOnboarding::factory()->justStarted()->create();
    $originalCount = count($onboarding->steps_completed);

    $onboarding->completeStep('invalid_step');

    expect($onboarding->steps_completed)->toHaveCount($originalCount);
});

test('completeStep does not duplicate steps', function (): void {
    $onboarding = TenantOnboarding::factory()->justStarted()->create();

    $onboarding->completeStep('account_created');
    $onboarding->completeStep('account_created');

    $accountCreatedCount = array_count_values($onboarding->steps_completed)['account_created'] ?? 0;

    expect($accountCreatedCount)->toBe(1);
});

test('isStepCompleted checks completed array', function (): void {
    $onboarding = TenantOnboarding::factory()->inProgress(3)->create();

    expect($onboarding->isStepCompleted('account_created'))->toBeTrue()
        ->and($onboarding->isStepCompleted('tour_completed'))->toBeFalse();
});

test('getCompletedStepsCount returns correct count', function (): void {
    $onboarding = TenantOnboarding::factory()->inProgress(5)->create();

    expect($onboarding->getCompletedStepsCount())->toBe(5);
});

test('getCompletedStepsCount returns zero for empty array', function (): void {
    $onboarding = TenantOnboarding::factory()->create([
        'steps_completed' => [],
    ]);

    expect($onboarding->getCompletedStepsCount())->toBe(0);
});

test('getProgressPercentage calculates correctly', function (): void {
    $onboarding = TenantOnboarding::factory()->inProgress(5)->create();

    // 5 out of 11 steps = 45.5%
    expect($onboarding->getProgressPercentage())->toBe(45.5);
});

test('getProgressPercentage returns 100 for completed', function (): void {
    $onboarding = TenantOnboarding::factory()->completed()->create();

    expect($onboarding->getProgressPercentage())->toBe(100.0);
});

test('getProgressPercentage returns 0 for no steps', function (): void {
    $onboarding = TenantOnboarding::factory()->create([
        'steps_completed' => [],
    ]);

    expect($onboarding->getProgressPercentage())->toBe(0.0);
});

test('isComplete checks completed_at', function (): void {
    $completed = TenantOnboarding::factory()->completed()->create();
    $inProgress = TenantOnboarding::factory()->inProgress()->create();

    expect($completed->isComplete())->toBeTrue()
        ->and($inProgress->isComplete())->toBeFalse();
});

test('isAbandoned checks abandoned_at', function (): void {
    $abandoned = TenantOnboarding::factory()->abandoned()->create();
    $inProgress = TenantOnboarding::factory()->inProgress()->create();

    expect($abandoned->isAbandoned())->toBeTrue()
        ->and($inProgress->isAbandoned())->toBeFalse();
});

test('markComplete sets completed_at', function (): void {
    $onboarding = TenantOnboarding::factory()->inProgress()->create();

    $onboarding->markComplete();

    expect($onboarding->completed_at)->not->toBeNull()
        ->and($onboarding->completed_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('markAbandoned sets abandoned_at', function (): void {
    $onboarding = TenantOnboarding::factory()->inProgress()->create();

    $onboarding->markAbandoned();

    expect($onboarding->abandoned_at)->not->toBeNull()
        ->and($onboarding->abandoned_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('getNextStep returns next uncompleted step', function (): void {
    $onboarding = TenantOnboarding::factory()->inProgress(3)->create();

    $nextStep = $onboarding->getNextStep();

    expect($nextStep)->toBe(TenantOnboarding::STEPS[3]);
});

test('getNextStep returns null when all completed', function (): void {
    $onboarding = TenantOnboarding::factory()->completed()->create();

    expect($onboarding->getNextStep())->toBeNull();
});

test('getMetadata retrieves from metadata json', function (): void {
    $onboarding = TenantOnboarding::factory()->withMetadata([
        'referral_source' => 'google',
        'nested' => ['key' => 'value'],
    ])->create();

    expect($onboarding->getMetadata('referral_source'))->toBe('google')
        ->and($onboarding->getMetadata('nested.key'))->toBe('value')
        ->and($onboarding->getMetadata('nonexistent', 'default'))->toBe('default');
});

test('setMetadata updates metadata json', function (): void {
    $onboarding = TenantOnboarding::factory()->create([
        'metadata' => ['key1' => 'value1'],
    ]);

    $onboarding->setMetadata('key2', 'value2');
    $onboarding->setMetadata('nested.deep', 'nested_value');

    $onboarding->refresh();

    expect($onboarding->metadata['key1'])->toBe('value1')
        ->and($onboarding->metadata['key2'])->toBe('value2')
        ->and($onboarding->metadata['nested']['deep'])->toBe('nested_value');
});

test('one onboarding per tenant unique constraint', function (): void {
    $tenant = Tenant::factory()->create();
    TenantOnboarding::factory()->forTenant($tenant)->create();

    expect(fn () => TenantOnboarding::factory()->forTenant($tenant)->create())
        ->toThrow(QueryException::class);
});

test('factory creates valid model', function (): void {
    $onboarding = TenantOnboarding::factory()->create();

    expect($onboarding)->toBeInstanceOf(TenantOnboarding::class)
        ->and($onboarding->id)->not->toBeNull()
        ->and($onboarding->tenant_id)->not->toBeNull()
        ->and($onboarding->current_step)->toBeString()
        ->and($onboarding->started_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('factory justStarted state works', function (): void {
    $onboarding = TenantOnboarding::factory()->justStarted()->create();

    expect($onboarding->current_step)->toBe('email_verified')
        ->and($onboarding->steps_completed)->toBe(['account_created'])
        ->and($onboarding->completed_at)->toBeNull();
});

test('factory completed state works', function (): void {
    $onboarding = TenantOnboarding::factory()->completed()->create();

    expect($onboarding->steps_completed)->toBe(TenantOnboarding::STEPS)
        ->and($onboarding->completed_at)->not->toBeNull();
});

test('factory abandoned state works', function (): void {
    $onboarding = TenantOnboarding::factory()->abandoned()->create();

    expect($onboarding->abandoned_at)->not->toBeNull()
        ->and($onboarding->completed_at)->toBeNull();
});
