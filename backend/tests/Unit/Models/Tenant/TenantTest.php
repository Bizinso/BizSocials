<?php

declare(strict_types=1);

/**
 * Tenant Model Unit Tests
 *
 * Tests for the Tenant model which represents customer
 * organizations in the multi-tenant platform.
 *
 * @see \App\Models\Tenant\Tenant
 */

use App\Enums\Tenant\TenantStatus;
use App\Enums\Tenant\TenantType;
use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantOnboarding;
use App\Models\Tenant\TenantProfile;
use App\Models\Tenant\TenantUsage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;

test('has correct table name', function (): void {
    $tenant = new Tenant();

    expect($tenant->getTable())->toBe('tenants');
});

test('uses uuid primary key', function (): void {
    $tenant = Tenant::factory()->create();

    expect($tenant->id)->not->toBeNull()
        ->and(strlen($tenant->id))->toBe(36);
});

test('uses soft deletes', function (): void {
    $tenant = new Tenant();

    expect(in_array(SoftDeletes::class, class_uses_recursive($tenant), true))->toBeTrue();
});

test('has correct fillable attributes', function (): void {
    $tenant = new Tenant();
    $fillable = $tenant->getFillable();

    expect($fillable)->toContain('name')
        ->and($fillable)->toContain('slug')
        ->and($fillable)->toContain('type')
        ->and($fillable)->toContain('status')
        ->and($fillable)->toContain('owner_user_id')
        ->and($fillable)->toContain('plan_id')
        ->and($fillable)->toContain('trial_ends_at')
        ->and($fillable)->toContain('settings')
        ->and($fillable)->toContain('onboarding_completed_at')
        ->and($fillable)->toContain('metadata');
});

test('type casts to enum', function (): void {
    $tenant = Tenant::factory()->b2bEnterprise()->create();

    expect($tenant->type)->toBeInstanceOf(TenantType::class)
        ->and($tenant->type)->toBe(TenantType::B2B_ENTERPRISE);
});

test('status casts to enum', function (): void {
    $tenant = Tenant::factory()->active()->create();

    expect($tenant->status)->toBeInstanceOf(TenantStatus::class)
        ->and($tenant->status)->toBe(TenantStatus::ACTIVE);
});

test('trial_ends_at casts to datetime', function (): void {
    $tenant = Tenant::factory()->onTrial(14)->create();

    expect($tenant->trial_ends_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('onboarding_completed_at casts to datetime', function (): void {
    $tenant = Tenant::factory()->onboardingCompleted()->create();

    expect($tenant->onboarding_completed_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('settings casts to array', function (): void {
    $settings = [
        'timezone' => 'Asia/Kolkata',
        'language' => 'en',
    ];

    $tenant = Tenant::factory()->withSettings($settings)->create();

    expect($tenant->settings)->toBeArray()
        ->and($tenant->settings['timezone'])->toBe('Asia/Kolkata');
});

test('metadata casts to array', function (): void {
    $tenant = Tenant::factory()->create([
        'metadata' => ['key' => 'value'],
    ]);

    expect($tenant->metadata)->toBeArray()
        ->and($tenant->metadata['key'])->toBe('value');
});

test('plan relationship returns belongs to', function (): void {
    $tenant = new Tenant();

    expect($tenant->plan())->toBeInstanceOf(BelongsTo::class);
});

test('plan relationship works correctly', function (): void {
    $plan = PlanDefinition::factory()->create();
    $tenant = Tenant::factory()->withPlan($plan)->create();

    expect($tenant->plan)->toBeInstanceOf(PlanDefinition::class)
        ->and($tenant->plan->id)->toBe($plan->id);
});

test('profile relationship returns has one', function (): void {
    $tenant = new Tenant();

    expect($tenant->profile())->toBeInstanceOf(HasOne::class);
});

test('profile relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $profile = TenantProfile::factory()->forTenant($tenant)->create();

    expect($tenant->profile)->toBeInstanceOf(TenantProfile::class)
        ->and($tenant->profile->id)->toBe($profile->id);
});

test('onboarding relationship returns has one', function (): void {
    $tenant = new Tenant();

    expect($tenant->onboarding())->toBeInstanceOf(HasOne::class);
});

test('onboarding relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $onboarding = TenantOnboarding::factory()->forTenant($tenant)->create();

    expect($tenant->onboarding)->toBeInstanceOf(TenantOnboarding::class)
        ->and($tenant->onboarding->id)->toBe($onboarding->id);
});

test('usageRecords relationship returns has many', function (): void {
    $tenant = new Tenant();

    expect($tenant->usageRecords())->toBeInstanceOf(HasMany::class);
});

test('usageRecords relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    // Create with unique metric keys to avoid constraint violation
    TenantUsage::factory()->forTenant($tenant)->forMetric('posts_published')->create();
    TenantUsage::factory()->forTenant($tenant)->forMetric('users_count')->create();
    TenantUsage::factory()->forTenant($tenant)->forMetric('api_calls')->create();

    expect($tenant->usageRecords)->toHaveCount(3)
        ->and($tenant->usageRecords->first())->toBeInstanceOf(TenantUsage::class);
});

test('scope active filters correctly', function (): void {
    Tenant::factory()->count(3)->active()->create();
    Tenant::factory()->count(2)->suspended()->create();

    $activeTenants = Tenant::active()->get();

    expect($activeTenants)->toHaveCount(3)
        ->and($activeTenants->every(fn ($t) => $t->status === TenantStatus::ACTIVE))->toBeTrue();
});

test('scope ofType filters by type', function (): void {
    Tenant::factory()->count(2)->b2bEnterprise()->create();
    Tenant::factory()->count(3)->individual()->create();

    $enterprises = Tenant::ofType(TenantType::B2B_ENTERPRISE)->get();

    expect($enterprises)->toHaveCount(2)
        ->and($enterprises->every(fn ($t) => $t->type === TenantType::B2B_ENTERPRISE))->toBeTrue();
});

test('scope onTrial filters tenants on trial', function (): void {
    Tenant::factory()->count(2)->onTrial(14)->create();
    Tenant::factory()->count(3)->create(['trial_ends_at' => null]);
    Tenant::factory()->trialExpired()->create();

    $onTrial = Tenant::onTrial()->get();

    expect($onTrial)->toHaveCount(2)
        ->and($onTrial->every(fn ($t) => $t->trial_ends_at?->isFuture()))->toBeTrue();
});

test('isActive returns true only for active status', function (): void {
    $active = Tenant::factory()->active()->create();
    $pending = Tenant::factory()->pending()->create();
    $suspended = Tenant::factory()->suspended()->create();

    expect($active->isActive())->toBeTrue()
        ->and($pending->isActive())->toBeFalse()
        ->and($suspended->isActive())->toBeFalse();
});

test('isOnTrial returns true if trial ends at is in future', function (): void {
    $onTrial = Tenant::factory()->onTrial(14)->create();
    $notOnTrial = Tenant::factory()->create(['trial_ends_at' => null]);
    $expiredTrial = Tenant::factory()->trialExpired()->create();

    expect($onTrial->isOnTrial())->toBeTrue()
        ->and($notOnTrial->isOnTrial())->toBeFalse()
        ->and($expiredTrial->isOnTrial())->toBeFalse();
});

test('trialDaysRemaining calculates correctly', function (): void {
    $tenant = Tenant::factory()->create([
        'trial_ends_at' => now()->addDays(10),
    ]);

    // Allow for timing variance (could be 9 or 10 depending on time of day)
    expect($tenant->trialDaysRemaining())->toBeGreaterThanOrEqual(9)
        ->and($tenant->trialDaysRemaining())->toBeLessThanOrEqual(10);
});

test('trialDaysRemaining returns zero when not on trial', function (): void {
    $tenant = Tenant::factory()->create(['trial_ends_at' => null]);

    expect($tenant->trialDaysRemaining())->toBe(0);
});

test('trialDaysRemaining returns zero when trial expired', function (): void {
    $tenant = Tenant::factory()->trialExpired()->create();

    expect($tenant->trialDaysRemaining())->toBe(0);
});

test('hasCompletedOnboarding checks onboarding completed at', function (): void {
    $completed = Tenant::factory()->onboardingCompleted()->create();
    $notCompleted = Tenant::factory()->onboardingNotCompleted()->create();

    expect($completed->hasCompletedOnboarding())->toBeTrue()
        ->and($notCompleted->hasCompletedOnboarding())->toBeFalse();
});

test('getSetting retrieves nested settings', function (): void {
    $tenant = Tenant::factory()->create([
        'settings' => [
            'timezone' => 'Asia/Kolkata',
            'notifications' => [
                'email' => true,
                'digest' => 'daily',
            ],
        ],
    ]);

    expect($tenant->getSetting('timezone'))->toBe('Asia/Kolkata')
        ->and($tenant->getSetting('notifications.email'))->toBeTrue()
        ->and($tenant->getSetting('notifications.digest'))->toBe('daily')
        ->and($tenant->getSetting('nonexistent', 'default'))->toBe('default');
});

test('setSetting updates nested settings', function (): void {
    $tenant = Tenant::factory()->create([
        'settings' => ['timezone' => 'UTC'],
    ]);

    $tenant->setSetting('timezone', 'Asia/Kolkata');
    $tenant->setSetting('notifications.email', false);

    $tenant->refresh();

    expect($tenant->settings['timezone'])->toBe('Asia/Kolkata')
        ->and($tenant->settings['notifications']['email'])->toBeFalse();
});

test('activate changes status to active', function (): void {
    $tenant = Tenant::factory()->pending()->create();

    $tenant->activate();

    expect($tenant->status)->toBe(TenantStatus::ACTIVE);
});

test('suspend changes status to suspended', function (): void {
    $tenant = Tenant::factory()->active()->create();

    $tenant->suspend('Payment failed');

    expect($tenant->status)->toBe(TenantStatus::SUSPENDED)
        ->and($tenant->metadata['suspension_reason'])->toBe('Payment failed')
        ->and($tenant->metadata['suspended_at'])->not->toBeNull();
});

test('suspend without reason still works', function (): void {
    $tenant = Tenant::factory()->active()->create();

    $tenant->suspend();

    expect($tenant->status)->toBe(TenantStatus::SUSPENDED);
});

test('terminate changes status to terminated', function (): void {
    $tenant = Tenant::factory()->active()->create();

    $tenant->terminate();

    expect($tenant->status)->toBe(TenantStatus::TERMINATED)
        ->and($tenant->metadata['terminated_at'])->not->toBeNull();
});

test('slug must be unique', function (): void {
    Tenant::factory()->create(['slug' => 'unique-slug']);

    expect(fn () => Tenant::factory()->create(['slug' => 'unique-slug']))
        ->toThrow(QueryException::class);
});

test('factory creates valid model', function (): void {
    $tenant = Tenant::factory()->create();

    expect($tenant)->toBeInstanceOf(Tenant::class)
        ->and($tenant->id)->not->toBeNull()
        ->and($tenant->name)->toBeString()
        ->and($tenant->slug)->toBeString()
        ->and($tenant->type)->toBeInstanceOf(TenantType::class)
        ->and($tenant->status)->toBeInstanceOf(TenantStatus::class)
        ->and($tenant->settings)->toBeArray();
});

test('soft delete works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $tenantId = $tenant->id;

    $tenant->delete();

    expect(Tenant::find($tenantId))->toBeNull()
        ->and(Tenant::withTrashed()->find($tenantId))->not->toBeNull()
        ->and(Tenant::withTrashed()->find($tenantId)->deleted_at)->not->toBeNull();
});
