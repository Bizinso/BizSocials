<?php

declare(strict_types=1);

/**
 * FeatureFlag Model Unit Tests
 *
 * Tests for the FeatureFlag model which represents feature toggles
 * for gradual rollout of new features with support for percentage-based
 * rollout, plan-based access control, and tenant-specific overrides.
 *
 * @see \App\Models\Platform\FeatureFlag
 */

use App\Enums\Platform\PlanCode;
use App\Models\Platform\FeatureFlag;

test('can create feature flag', function (): void {
    $flag = FeatureFlag::create([
        'key' => 'test.feature',
        'name' => 'Test Feature',
        'description' => 'A test feature flag',
        'is_enabled' => true,
        'rollout_percentage' => 50,
    ]);

    expect($flag)->toBeInstanceOf(FeatureFlag::class)
        ->and($flag->key)->toBe('test.feature')
        ->and($flag->name)->toBe('Test Feature')
        ->and($flag->is_enabled)->toBeTrue()
        ->and($flag->rollout_percentage)->toBe(50)
        ->and($flag->id)->not->toBeNull();
});

test('key must be unique', function (): void {
    FeatureFlag::factory()->create(['key' => 'unique.feature']);

    expect(fn () => FeatureFlag::factory()->create(['key' => 'unique.feature']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('is enabled for tenant with allowed tenants', function (): void {
    $tenantId = 'tenant-123';

    $flag = FeatureFlag::factory()->enabled()->create([
        'allowed_tenants' => [$tenantId, 'tenant-456'],
        'allowed_plans' => null,
        'rollout_percentage' => 0, // Would normally be disabled
    ]);

    // Tenant in allowlist should have access regardless of rollout
    expect($flag->isEnabledForTenant($tenantId, PlanCode::FREE->value))->toBeTrue()
        ->and($flag->isEnabledForTenant('tenant-789', PlanCode::FREE->value))->toBeFalse();
});

test('is enabled for tenant with allowed plans', function (): void {
    $flag = FeatureFlag::factory()->enabled()->fullRollout()->create([
        'allowed_plans' => [PlanCode::PROFESSIONAL->value, PlanCode::BUSINESS->value, PlanCode::ENTERPRISE->value],
        'allowed_tenants' => null,
    ]);

    // Only specified plans should have access
    expect($flag->isEnabledForTenant('tenant-1', PlanCode::PROFESSIONAL->value))->toBeTrue()
        ->and($flag->isEnabledForTenant('tenant-1', PlanCode::BUSINESS->value))->toBeTrue()
        ->and($flag->isEnabledForTenant('tenant-1', PlanCode::ENTERPRISE->value))->toBeTrue()
        ->and($flag->isEnabledForTenant('tenant-1', PlanCode::FREE->value))->toBeFalse()
        ->and($flag->isEnabledForTenant('tenant-1', PlanCode::STARTER->value))->toBeFalse();
});

test('is enabled returns false when globally disabled', function (): void {
    $flag = FeatureFlag::factory()->disabled()->create([
        'allowed_tenants' => ['tenant-123'],
        'rollout_percentage' => 100,
    ]);

    // Even with tenant allowlist and 100% rollout, disabled flag returns false
    expect($flag->isEnabledForTenant('tenant-123', PlanCode::ENTERPRISE->value))->toBeFalse();
});

test('rollout percentage consistent for same identifier', function (): void {
    $flag = FeatureFlag::factory()->enabled()->create([
        'key' => 'consistent.rollout.test',
        'rollout_percentage' => 50,
        'allowed_plans' => null,
        'allowed_tenants' => null,
    ]);

    $tenantId = 'consistent-tenant-id';

    // Call multiple times - should always return the same result
    $results = [];
    for ($i = 0; $i < 10; $i++) {
        $results[] = $flag->isEnabledWithRollout($tenantId);
    }

    // All results should be identical
    expect(count(array_unique($results)))->toBe(1);
});

test('rollout percentage produces different results for different identifiers', function (): void {
    $flag = FeatureFlag::factory()->enabled()->create([
        'key' => 'rollout.distribution.test',
        'rollout_percentage' => 50,
        'allowed_plans' => null,
        'allowed_tenants' => null,
    ]);

    // Test with many tenants to verify distribution
    $enabledCount = 0;
    $totalTests = 100;

    for ($i = 0; $i < $totalTests; $i++) {
        if ($flag->isEnabledWithRollout("tenant-{$i}")) {
            $enabledCount++;
        }
    }

    // With 50% rollout, roughly half should be enabled (allow margin of error)
    expect($enabledCount)->toBeGreaterThan(20)
        ->and($enabledCount)->toBeLessThan(80);
});

test('scope enabled returns only enabled', function (): void {
    FeatureFlag::factory()->count(3)->enabled()->create();
    FeatureFlag::factory()->count(2)->disabled()->create();

    $enabledFlags = FeatureFlag::enabled()->get();

    expect($enabledFlags)->toHaveCount(3)
        ->and($enabledFlags->every(fn ($flag) => $flag->is_enabled))->toBeTrue();
});

test('arrays cast correctly', function (): void {
    $flag = FeatureFlag::factory()->create([
        'allowed_plans' => [PlanCode::STARTER->value, PlanCode::PROFESSIONAL->value],
        'allowed_tenants' => ['tenant-1', 'tenant-2', 'tenant-3'],
        'metadata' => ['version' => '1.0', 'author' => 'test'],
    ]);

    expect($flag->allowed_plans)->toBeArray()
        ->and($flag->allowed_plans)->toHaveCount(2)
        ->and($flag->allowed_tenants)->toBeArray()
        ->and($flag->allowed_tenants)->toHaveCount(3)
        ->and($flag->metadata)->toBeArray()
        ->and($flag->metadata['version'])->toBe('1.0');
});

test('100 percent rollout always enabled', function (): void {
    $flag = FeatureFlag::factory()->enabled()->fullRollout()->create([
        'allowed_plans' => null,
        'allowed_tenants' => null,
    ]);

    // Any tenant should be enabled with 100% rollout
    for ($i = 0; $i < 10; $i++) {
        expect($flag->isEnabledWithRollout("random-tenant-{$i}"))->toBeTrue();
    }
});

test('zero percent rollout always disabled', function (): void {
    $flag = FeatureFlag::factory()->enabled()->rollout(0)->create([
        'allowed_plans' => null,
        'allowed_tenants' => null,
    ]);

    // No tenant should be enabled with 0% rollout (unless in allowlist)
    for ($i = 0; $i < 10; $i++) {
        expect($flag->isEnabledWithRollout("random-tenant-{$i}"))->toBeFalse();
    }
});

test('get by key returns flag', function (): void {
    $created = FeatureFlag::factory()->create(['key' => 'test.get.by.key']);

    $found = FeatureFlag::getByKey('test.get.by.key');

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($created->id);
});

test('get by key returns null when not found', function (): void {
    $found = FeatureFlag::getByKey('nonexistent.key');

    expect($found)->toBeNull();
});

test('static is enabled checks correctly', function (): void {
    FeatureFlag::factory()->enabled()->fullRollout()->create([
        'key' => 'static.check.feature',
        'allowed_plans' => null,
        'allowed_tenants' => null,
    ]);

    expect(FeatureFlag::isEnabled('static.check.feature', 'tenant-1', PlanCode::FREE->value))->toBeTrue()
        ->and(FeatureFlag::isEnabled('nonexistent.feature', 'tenant-1', PlanCode::FREE->value))->toBeFalse();
});

test('factory creates valid model', function (): void {
    $flag = FeatureFlag::factory()->create();

    expect($flag)->toBeInstanceOf(FeatureFlag::class)
        ->and($flag->id)->not->toBeNull()
        ->and($flag->key)->toBeString()
        ->and($flag->name)->toBeString()
        ->and($flag->is_enabled)->toBeBool()
        ->and($flag->rollout_percentage)->toBeInt()
        ->and($flag->rollout_percentage)->toBeGreaterThanOrEqual(0)
        ->and($flag->rollout_percentage)->toBeLessThanOrEqual(100);
});
