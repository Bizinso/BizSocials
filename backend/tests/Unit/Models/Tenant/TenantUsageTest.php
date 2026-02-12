<?php

declare(strict_types=1);

/**
 * TenantUsage Model Unit Tests
 *
 * Tests for the TenantUsage model which tracks usage metrics
 * for tenants per billing period.
 *
 * @see \App\Models\Tenant\TenantUsage
 */

use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantUsage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

test('has correct table name', function (): void {
    $usage = new TenantUsage();

    expect($usage->getTable())->toBe('tenant_usage');
});

test('uses uuid primary key', function (): void {
    $usage = TenantUsage::factory()->create();

    expect($usage->id)->not->toBeNull()
        ->and(strlen($usage->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $usage = new TenantUsage();
    $fillable = $usage->getFillable();

    expect($fillable)->toContain('tenant_id')
        ->and($fillable)->toContain('period_start')
        ->and($fillable)->toContain('period_end')
        ->and($fillable)->toContain('metric_key')
        ->and($fillable)->toContain('metric_value');
});

test('METRIC_KEYS constant contains all expected keys', function (): void {
    $keys = TenantUsage::METRIC_KEYS;

    expect($keys)->toBeArray()
        ->and($keys)->toHaveCount(8)
        ->and($keys)->toContain('workspaces_count')
        ->and($keys)->toContain('users_count')
        ->and($keys)->toContain('social_accounts_count')
        ->and($keys)->toContain('posts_published')
        ->and($keys)->toContain('posts_scheduled')
        ->and($keys)->toContain('storage_bytes_used')
        ->and($keys)->toContain('api_calls')
        ->and($keys)->toContain('ai_requests');
});

test('period_start casts to date', function (): void {
    $usage = TenantUsage::factory()->create();

    expect($usage->period_start)->toBeInstanceOf(Carbon::class);
});

test('period_end casts to date', function (): void {
    $usage = TenantUsage::factory()->create();

    expect($usage->period_end)->toBeInstanceOf(Carbon::class);
});

test('metric_value casts to integer', function (): void {
    $usage = TenantUsage::factory()->withValue(100)->create();

    expect($usage->metric_value)->toBeInt()
        ->and($usage->metric_value)->toBe(100);
});

test('tenant relationship returns belongs to', function (): void {
    $usage = new TenantUsage();

    expect($usage->tenant())->toBeInstanceOf(BelongsTo::class);
});

test('tenant relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $usage = TenantUsage::factory()->forTenant($tenant)->create();

    expect($usage->tenant)->toBeInstanceOf(Tenant::class)
        ->and($usage->tenant->id)->toBe($tenant->id);
});

test('scope forTenant filters by tenant', function (): void {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    // Create with unique metric keys to avoid constraint violation
    TenantUsage::factory()->forTenant($tenant1)->forMetric('posts_published')->create();
    TenantUsage::factory()->forTenant($tenant1)->forMetric('users_count')->create();
    TenantUsage::factory()->forTenant($tenant1)->forMetric('api_calls')->create();
    TenantUsage::factory()->forTenant($tenant2)->forMetric('posts_published')->create();
    TenantUsage::factory()->forTenant($tenant2)->forMetric('users_count')->create();

    $tenant1Usage = TenantUsage::forTenant($tenant1->id)->get();

    expect($tenant1Usage)->toHaveCount(3)
        ->and($tenant1Usage->every(fn ($u) => $u->tenant_id === $tenant1->id))->toBeTrue();
});

test('scope forPeriod filters by date range', function (): void {
    $tenant = Tenant::factory()->create();

    // Create with unique metric keys for each period
    TenantUsage::factory()->forTenant($tenant)->forCurrentPeriod()->forMetric('posts_published')->create();
    TenantUsage::factory()->forTenant($tenant)->forCurrentPeriod()->forMetric('users_count')->create();
    TenantUsage::factory()->forTenant($tenant)->forPreviousPeriod()->forMetric('posts_published')->create();
    TenantUsage::factory()->forTenant($tenant)->forPreviousPeriod()->forMetric('users_count')->create();
    TenantUsage::factory()->forTenant($tenant)->forPreviousPeriod()->forMetric('api_calls')->create();

    $periodStart = Carbon::now()->startOfMonth()->toDateString();
    $periodEnd = Carbon::now()->endOfMonth()->toDateString();

    $currentPeriod = TenantUsage::forPeriod($periodStart, $periodEnd)->get();

    expect($currentPeriod)->toHaveCount(2);
});

test('scope forMetric filters by metric key', function (): void {
    $tenant = Tenant::factory()->create();

    // Create usage records for different tenants to avoid unique constraint
    $tenant2 = Tenant::factory()->create();
    TenantUsage::factory()->forTenant($tenant)->forMetric('posts_published')->create();
    TenantUsage::factory()->forTenant($tenant2)->forMetric('posts_published')->create();
    TenantUsage::factory()->forTenant($tenant)->forMetric('api_calls')->create();
    TenantUsage::factory()->forTenant($tenant2)->forMetric('api_calls')->create();
    TenantUsage::factory()->forTenant(Tenant::factory()->create())->forMetric('api_calls')->create();

    $postsUsage = TenantUsage::forMetric('posts_published')->get();

    expect($postsUsage)->toHaveCount(2)
        ->and($postsUsage->every(fn ($u) => $u->metric_key === 'posts_published'))->toBeTrue();
});

test('scope currentPeriod uses current billing period', function (): void {
    $tenant = Tenant::factory()->create();

    // Create with unique metric keys
    TenantUsage::factory()->forTenant($tenant)->forCurrentPeriod()->forMetric('posts_published')->create();
    TenantUsage::factory()->forTenant($tenant)->forCurrentPeriod()->forMetric('users_count')->create();
    TenantUsage::factory()->forTenant($tenant)->forPreviousPeriod()->forMetric('posts_published')->create();
    TenantUsage::factory()->forTenant($tenant)->forPreviousPeriod()->forMetric('users_count')->create();
    TenantUsage::factory()->forTenant($tenant)->forPreviousPeriod()->forMetric('api_calls')->create();

    $current = TenantUsage::currentPeriod()->get();

    expect($current)->toHaveCount(2);
});

test('incrementMetric creates or updates record', function (): void {
    $tenant = Tenant::factory()->create();

    TenantUsage::incrementMetric($tenant->id, 'posts_published', 5);

    $usage = TenantUsage::forTenant($tenant->id)->forMetric('posts_published')->first();

    expect($usage)->not->toBeNull()
        ->and($usage->metric_value)->toBe(5);

    // Increment again
    TenantUsage::incrementMetric($tenant->id, 'posts_published', 3);

    $usage->refresh();

    expect($usage->metric_value)->toBe(8);
});

test('incrementMetric defaults to 1', function (): void {
    $tenant = Tenant::factory()->create();

    TenantUsage::incrementMetric($tenant->id, 'api_calls');
    TenantUsage::incrementMetric($tenant->id, 'api_calls');

    $usage = TenantUsage::forTenant($tenant->id)->forMetric('api_calls')->first();

    expect($usage->metric_value)->toBe(2);
});

test('decrementMetric reduces value', function (): void {
    $tenant = Tenant::factory()->create();

    TenantUsage::setMetric($tenant->id, 'workspaces_count', 10);
    TenantUsage::decrementMetric($tenant->id, 'workspaces_count', 3);

    $value = TenantUsage::getMetric($tenant->id, 'workspaces_count');

    expect($value)->toBe(7);
});

test('decrementMetric does not go below 0', function (): void {
    $tenant = Tenant::factory()->create();

    TenantUsage::setMetric($tenant->id, 'workspaces_count', 5);
    TenantUsage::decrementMetric($tenant->id, 'workspaces_count', 10);

    $value = TenantUsage::getMetric($tenant->id, 'workspaces_count');

    expect($value)->toBe(0);
});

test('decrementMetric defaults to 1', function (): void {
    $tenant = Tenant::factory()->create();

    TenantUsage::setMetric($tenant->id, 'users_count', 5);
    TenantUsage::decrementMetric($tenant->id, 'users_count');

    $value = TenantUsage::getMetric($tenant->id, 'users_count');

    expect($value)->toBe(4);
});

test('setMetric sets absolute value', function (): void {
    $tenant = Tenant::factory()->create();

    TenantUsage::setMetric($tenant->id, 'storage_bytes_used', 1000000);

    $value = TenantUsage::getMetric($tenant->id, 'storage_bytes_used');

    expect($value)->toBe(1000000);

    // Set again with different value
    TenantUsage::setMetric($tenant->id, 'storage_bytes_used', 2000000);

    $value = TenantUsage::getMetric($tenant->id, 'storage_bytes_used');

    expect($value)->toBe(2000000);
});

test('getMetric retrieves current value', function (): void {
    $tenant = Tenant::factory()->create();

    TenantUsage::setMetric($tenant->id, 'ai_requests', 500);

    $value = TenantUsage::getMetric($tenant->id, 'ai_requests');

    expect($value)->toBe(500);
});

test('getMetric returns 0 if not found', function (): void {
    $tenant = Tenant::factory()->create();

    $value = TenantUsage::getMetric($tenant->id, 'nonexistent_metric');

    expect($value)->toBe(0);
});

test('getCurrentPeriodUsage returns all metrics for tenant', function (): void {
    $tenant = Tenant::factory()->create();

    TenantUsage::setMetric($tenant->id, 'posts_published', 100);
    TenantUsage::setMetric($tenant->id, 'users_count', 10);
    TenantUsage::setMetric($tenant->id, 'api_calls', 5000);

    $usage = TenantUsage::getCurrentPeriodUsage($tenant->id);

    expect($usage)->toBeArray()
        ->and($usage['posts_published'])->toBe(100)
        ->and($usage['users_count'])->toBe(10)
        ->and($usage['api_calls'])->toBe(5000)
        ->and($usage['storage_bytes_used'])->toBe(0) // Not set, defaults to 0
        ->and(array_keys($usage))->toBe(TenantUsage::METRIC_KEYS);
});

test('getCurrentPeriodUsage returns all zeros for new tenant', function (): void {
    $tenant = Tenant::factory()->create();

    $usage = TenantUsage::getCurrentPeriodUsage($tenant->id);

    expect($usage)->toBeArray()
        ->and(array_sum($usage))->toBe(0);
});

test('unique constraint on tenant period metric', function (): void {
    $tenant = Tenant::factory()->create();
    $periodStart = Carbon::now()->startOfMonth()->toDateString();
    $periodEnd = Carbon::now()->endOfMonth()->toDateString();

    TenantUsage::create([
        'tenant_id' => $tenant->id,
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'metric_key' => 'posts_published',
        'metric_value' => 100,
    ]);

    expect(fn () => TenantUsage::create([
        'tenant_id' => $tenant->id,
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'metric_key' => 'posts_published',
        'metric_value' => 200,
    ]))->toThrow(QueryException::class);
});

test('same metric key allowed for different periods', function (): void {
    $tenant = Tenant::factory()->create();

    TenantUsage::factory()->forTenant($tenant)
        ->forCurrentPeriod()
        ->forMetric('posts_published')
        ->create();

    $usage = TenantUsage::factory()->forTenant($tenant)
        ->forPreviousPeriod()
        ->forMetric('posts_published')
        ->create();

    expect($usage)->toBeInstanceOf(TenantUsage::class);
});

test('factory creates valid model', function (): void {
    $usage = TenantUsage::factory()->create();

    expect($usage)->toBeInstanceOf(TenantUsage::class)
        ->and($usage->id)->not->toBeNull()
        ->and($usage->tenant_id)->not->toBeNull()
        ->and($usage->period_start)->toBeInstanceOf(Carbon::class)
        ->and($usage->period_end)->toBeInstanceOf(Carbon::class)
        ->and($usage->metric_key)->toBeString()
        ->and($usage->metric_value)->toBeInt();
});

test('factory highUsage state works', function (): void {
    $usage = TenantUsage::factory()->forMetric('posts_published')->highUsage()->create();

    expect($usage->metric_value)->toBeGreaterThanOrEqual(5000);
});

test('factory lowUsage state works', function (): void {
    $usage = TenantUsage::factory()->forMetric('posts_published')->lowUsage()->create();

    expect($usage->metric_value)->toBeLessThanOrEqual(50);
});
