<?php

declare(strict_types=1);

/**
 * PlanLimit Model Unit Tests
 *
 * Tests for the PlanLimit model which represents limits/quotas
 * for each subscription plan. Each limit has a key and numeric value,
 * where -1 indicates unlimited.
 *
 * @see \App\Models\Platform\PlanLimit
 */

use App\Models\Platform\PlanDefinition;
use App\Models\Platform\PlanLimit;

test('can create plan limit', function (): void {
    $plan = PlanDefinition::factory()->create();

    $limit = PlanLimit::create([
        'plan_id' => $plan->id,
        'limit_key' => 'max_workspaces',
        'limit_value' => 10,
    ]);

    expect($limit)->toBeInstanceOf(PlanLimit::class)
        ->and($limit->plan_id)->toBe($plan->id)
        ->and($limit->limit_key)->toBe('max_workspaces')
        ->and($limit->limit_value)->toBe(10)
        ->and($limit->id)->not->toBeNull();
});

test('plan id and limit key unique together', function (): void {
    $plan = PlanDefinition::factory()->create();

    PlanLimit::factory()->forPlan($plan)->limit('max_users', 5)->create();

    expect(fn () => PlanLimit::factory()->forPlan($plan)->limit('max_users', 10)->create())
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('same limit key allowed for different plans', function (): void {
    $plan1 = PlanDefinition::factory()->create();
    $plan2 = PlanDefinition::factory()->create();

    $limit1 = PlanLimit::factory()->forPlan($plan1)->limit('max_users', 5)->create();
    $limit2 = PlanLimit::factory()->forPlan($plan2)->limit('max_users', 10)->create();

    expect($limit1)->toBeInstanceOf(PlanLimit::class)
        ->and($limit2)->toBeInstanceOf(PlanLimit::class)
        ->and($limit1->limit_value)->toBe(5)
        ->and($limit2->limit_value)->toBe(10);
});

test('belongs to plan definition', function (): void {
    $plan = PlanDefinition::factory()->create();

    $limit = PlanLimit::factory()->forPlan($plan)->create();

    expect($limit->plan)->toBeInstanceOf(PlanDefinition::class)
        ->and($limit->plan->id)->toBe($plan->id);
});

test('is unlimited returns true for negative one', function (): void {
    $limit = PlanLimit::factory()->create([
        'limit_value' => PlanLimit::UNLIMITED,
    ]);

    expect($limit->isUnlimited())->toBeTrue()
        ->and($limit->limit_value)->toBe(-1);
});

test('is unlimited returns false for positive value', function (): void {
    $limit = PlanLimit::factory()->create([
        'limit_value' => 100,
    ]);

    expect($limit->isUnlimited())->toBeFalse();
});

test('is unlimited returns false for zero', function (): void {
    $limit = PlanLimit::factory()->create([
        'limit_value' => 0,
    ]);

    expect($limit->isUnlimited())->toBeFalse();
});

test('cascades on plan delete', function (): void {
    $plan = PlanDefinition::factory()->create();

    PlanLimit::factory()->forPlan($plan)->maxWorkspaces(5)->create();
    PlanLimit::factory()->forPlan($plan)->maxUsers(10)->create();
    PlanLimit::factory()->forPlan($plan)->maxSocialAccounts(15)->create();

    expect(PlanLimit::where('plan_id', $plan->id)->count())->toBe(3);

    $plan->delete();

    expect(PlanLimit::where('plan_id', $plan->id)->count())->toBe(0);
});

test('exceeds limit returns true when value exceeds limit', function (): void {
    $limit = PlanLimit::factory()->create([
        'limit_value' => 10,
    ]);

    expect($limit->exceedsLimit(11))->toBeTrue()
        ->and($limit->exceedsLimit(15))->toBeTrue()
        ->and($limit->exceedsLimit(100))->toBeTrue();
});

test('exceeds limit returns false when value within limit', function (): void {
    $limit = PlanLimit::factory()->create([
        'limit_value' => 10,
    ]);

    expect($limit->exceedsLimit(10))->toBeFalse()
        ->and($limit->exceedsLimit(5))->toBeFalse()
        ->and($limit->exceedsLimit(0))->toBeFalse();
});

test('exceeds limit returns false for unlimited', function (): void {
    $limit = PlanLimit::factory()->unlimited()->create();

    expect($limit->exceedsLimit(999999))->toBeFalse();
});

test('get remaining quota returns correct value', function (): void {
    $limit = PlanLimit::factory()->create([
        'limit_value' => 100,
    ]);

    expect($limit->getRemainingQuota(30))->toBe(70)
        ->and($limit->getRemainingQuota(100))->toBe(0)
        ->and($limit->getRemainingQuota(150))->toBe(0);
});

test('get remaining quota returns null for unlimited', function (): void {
    $limit = PlanLimit::factory()->unlimited()->create();

    expect($limit->getRemainingQuota(999999))->toBeNull();
});

test('is valid limit key returns true for valid keys', function (): void {
    foreach (PlanLimit::LIMIT_KEYS as $key) {
        expect(PlanLimit::isValidLimitKey($key))->toBeTrue();
    }
});

test('is valid limit key returns false for invalid keys', function (): void {
    expect(PlanLimit::isValidLimitKey('invalid_key'))->toBeFalse()
        ->and(PlanLimit::isValidLimitKey('random'))->toBeFalse()
        ->and(PlanLimit::isValidLimitKey(''))->toBeFalse();
});

test('limit value casts to integer', function (): void {
    $limit = PlanLimit::factory()->create([
        'limit_value' => 50,
    ]);

    expect($limit->limit_value)->toBeInt()
        ->and($limit->limit_value)->toBe(50);
});

test('unlimited constant equals negative one', function (): void {
    expect(PlanLimit::UNLIMITED)->toBe(-1);
});

test('limit keys constant contains expected keys', function (): void {
    expect(PlanLimit::LIMIT_KEYS)->toContain('max_workspaces')
        ->and(PlanLimit::LIMIT_KEYS)->toContain('max_users')
        ->and(PlanLimit::LIMIT_KEYS)->toContain('max_social_accounts')
        ->and(PlanLimit::LIMIT_KEYS)->toContain('max_posts_per_month')
        ->and(PlanLimit::LIMIT_KEYS)->toContain('max_scheduled_posts')
        ->and(PlanLimit::LIMIT_KEYS)->toContain('max_storage_gb')
        ->and(PlanLimit::LIMIT_KEYS)->toContain('ai_requests_per_month')
        ->and(PlanLimit::LIMIT_KEYS)->toContain('analytics_history_days');
});

test('factory creates valid model', function (): void {
    $limit = PlanLimit::factory()->create();

    expect($limit)->toBeInstanceOf(PlanLimit::class)
        ->and($limit->id)->not->toBeNull()
        ->and($limit->plan_id)->not->toBeNull()
        ->and($limit->limit_key)->toBeString()
        ->and(in_array($limit->limit_key, PlanLimit::LIMIT_KEYS, true))->toBeTrue()
        ->and($limit->limit_value)->toBeInt();
});

test('factory helper methods work correctly', function (): void {
    $plan = PlanDefinition::factory()->create();

    $workspaceLimit = PlanLimit::factory()->forPlan($plan)->maxWorkspaces(5)->create();
    $userLimit = PlanLimit::factory()->forPlan($plan)->maxUsers(10)->create();
    $unlimitedLimit = PlanLimit::factory()->forPlan($plan)->limit('max_posts_per_month', -1)->unlimited()->create();

    expect($workspaceLimit->limit_key)->toBe('max_workspaces')
        ->and($workspaceLimit->limit_value)->toBe(5)
        ->and($userLimit->limit_key)->toBe('max_users')
        ->and($userLimit->limit_value)->toBe(10)
        ->and($unlimitedLimit->isUnlimited())->toBeTrue();
});
