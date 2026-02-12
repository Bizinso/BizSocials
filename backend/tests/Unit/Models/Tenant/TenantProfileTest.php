<?php

declare(strict_types=1);

/**
 * TenantProfile Model Unit Tests
 *
 * Tests for the TenantProfile model which stores business
 * profile information for tenants.
 *
 * @see \App\Models\Tenant\TenantProfile
 */

use App\Enums\Tenant\CompanySize;
use App\Enums\Tenant\VerificationStatus;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantProfile;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;

test('has correct table name', function (): void {
    $profile = new TenantProfile();

    expect($profile->getTable())->toBe('tenant_profiles');
});

test('uses uuid primary key', function (): void {
    $profile = TenantProfile::factory()->create();

    expect($profile->id)->not->toBeNull()
        ->and(strlen($profile->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $profile = new TenantProfile();
    $fillable = $profile->getFillable();

    expect($fillable)->toContain('tenant_id')
        ->and($fillable)->toContain('legal_name')
        ->and($fillable)->toContain('business_type')
        ->and($fillable)->toContain('industry')
        ->and($fillable)->toContain('company_size')
        ->and($fillable)->toContain('website')
        ->and($fillable)->toContain('address_line1')
        ->and($fillable)->toContain('address_line2')
        ->and($fillable)->toContain('city')
        ->and($fillable)->toContain('state')
        ->and($fillable)->toContain('country')
        ->and($fillable)->toContain('postal_code')
        ->and($fillable)->toContain('phone')
        ->and($fillable)->toContain('gstin')
        ->and($fillable)->toContain('pan')
        ->and($fillable)->toContain('tax_id')
        ->and($fillable)->toContain('verification_status')
        ->and($fillable)->toContain('verified_at');
});

test('company_size casts to enum', function (): void {
    $profile = TenantProfile::factory()->medium()->create();

    expect($profile->company_size)->toBeInstanceOf(CompanySize::class)
        ->and($profile->company_size)->toBe(CompanySize::MEDIUM);
});

test('verification_status casts to enum', function (): void {
    $profile = TenantProfile::factory()->verified()->create();

    expect($profile->verification_status)->toBeInstanceOf(VerificationStatus::class)
        ->and($profile->verification_status)->toBe(VerificationStatus::VERIFIED);
});

test('verified_at casts to datetime', function (): void {
    $profile = TenantProfile::factory()->verified()->create();

    expect($profile->verified_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('tenant relationship returns belongs to', function (): void {
    $profile = new TenantProfile();

    expect($profile->tenant())->toBeInstanceOf(BelongsTo::class);
});

test('tenant relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $profile = TenantProfile::factory()->forTenant($tenant)->create();

    expect($profile->tenant)->toBeInstanceOf(Tenant::class)
        ->and($profile->tenant->id)->toBe($tenant->id);
});

test('isVerified returns true for verified status', function (): void {
    $verified = TenantProfile::factory()->verified()->create();
    $pending = TenantProfile::factory()->pending()->create();
    $failed = TenantProfile::factory()->failed()->create();

    expect($verified->isVerified())->toBeTrue()
        ->and($pending->isVerified())->toBeFalse()
        ->and($failed->isVerified())->toBeFalse();
});

test('markAsVerified updates status and timestamp', function (): void {
    $profile = TenantProfile::factory()->pending()->create();

    $profile->markAsVerified();

    expect($profile->verification_status)->toBe(VerificationStatus::VERIFIED)
        ->and($profile->verified_at)->not->toBeNull()
        ->and($profile->verified_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('markAsFailed updates status', function (): void {
    $profile = TenantProfile::factory()->pending()->create();

    $profile->markAsFailed();

    expect($profile->verification_status)->toBe(VerificationStatus::FAILED);
});

test('getFullAddress concatenates address parts', function (): void {
    $profile = TenantProfile::factory()->create([
        'address_line1' => '123 Main St',
        'address_line2' => 'Suite 100',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'postal_code' => '400001',
        'country' => 'IN',
    ]);

    $address = $profile->getFullAddress();

    expect($address)->toBe('123 Main St, Suite 100, Mumbai, Maharashtra, 400001, IN');
});

test('getFullAddress handles missing parts', function (): void {
    $profile = TenantProfile::factory()->create([
        'address_line1' => '123 Main St',
        'address_line2' => null,
        'city' => 'Mumbai',
        'state' => null,
        'postal_code' => null,
        'country' => 'IN',
    ]);

    $address = $profile->getFullAddress();

    expect($address)->toBe('123 Main St, Mumbai, IN');
});

test('getFullAddress returns empty string when all parts are null', function (): void {
    $profile = TenantProfile::factory()->create([
        'address_line1' => null,
        'address_line2' => null,
        'city' => null,
        'state' => null,
        'postal_code' => null,
        'country' => null,
    ]);

    expect($profile->getFullAddress())->toBe('');
});

test('hasTaxInfo returns true when gstin is set', function (): void {
    $profile = TenantProfile::factory()->withIndianTax()->create();

    expect($profile->hasTaxInfo())->toBeTrue();
});

test('hasTaxInfo returns true when pan is set', function (): void {
    $profile = TenantProfile::factory()->create([
        'gstin' => null,
        'pan' => 'ABCDE1234F',
        'tax_id' => null,
    ]);

    expect($profile->hasTaxInfo())->toBeTrue();
});

test('hasTaxInfo returns true when tax_id is set', function (): void {
    $profile = TenantProfile::factory()->withInternationalTax()->create();

    expect($profile->hasTaxInfo())->toBeTrue();
});

test('hasTaxInfo returns false when no tax info', function (): void {
    $profile = TenantProfile::factory()->create([
        'gstin' => null,
        'pan' => null,
        'tax_id' => null,
    ]);

    expect($profile->hasTaxInfo())->toBeFalse();
});

test('one profile per tenant unique constraint', function (): void {
    $tenant = Tenant::factory()->create();
    TenantProfile::factory()->forTenant($tenant)->create();

    expect(fn () => TenantProfile::factory()->forTenant($tenant)->create())
        ->toThrow(QueryException::class);
});

test('factory creates valid model', function (): void {
    $profile = TenantProfile::factory()->create();

    expect($profile)->toBeInstanceOf(TenantProfile::class)
        ->and($profile->id)->not->toBeNull()
        ->and($profile->tenant_id)->not->toBeNull()
        ->and($profile->verification_status)->toBeInstanceOf(VerificationStatus::class);
});

test('factory withIndianTax sets correct fields', function (): void {
    $profile = TenantProfile::factory()->withIndianTax()->create();

    expect($profile->country)->toBe('IN')
        ->and($profile->gstin)->not->toBeNull()
        ->and($profile->pan)->not->toBeNull()
        ->and($profile->tax_id)->toBeNull();
});

test('factory withInternationalTax sets correct fields', function (): void {
    $profile = TenantProfile::factory()->withInternationalTax('US')->create();

    expect($profile->country)->toBe('US')
        ->and($profile->gstin)->toBeNull()
        ->and($profile->pan)->toBeNull()
        ->and($profile->tax_id)->not->toBeNull();
});

test('company size nullable', function (): void {
    $profile = TenantProfile::factory()->create([
        'company_size' => null,
    ]);

    expect($profile->company_size)->toBeNull();
});
