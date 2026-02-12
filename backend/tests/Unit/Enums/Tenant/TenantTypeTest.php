<?php

declare(strict_types=1);

/**
 * TenantType Enum Unit Tests
 *
 * Tests for the TenantType enum which defines different
 * types of tenants/customers in the platform.
 *
 * @see \App\Enums\Tenant\TenantType
 */

use App\Enums\Tenant\TenantType;

test('has all expected cases', function (): void {
    $cases = TenantType::cases();

    expect($cases)->toHaveCount(6)
        ->and(TenantType::B2B_ENTERPRISE->value)->toBe('b2b_enterprise')
        ->and(TenantType::B2B_SMB->value)->toBe('b2b_smb')
        ->and(TenantType::B2C_BRAND->value)->toBe('b2c_brand')
        ->and(TenantType::INDIVIDUAL->value)->toBe('individual')
        ->and(TenantType::INFLUENCER->value)->toBe('influencer')
        ->and(TenantType::NON_PROFIT->value)->toBe('non_profit');
});

test('label returns correct labels', function (): void {
    expect(TenantType::B2B_ENTERPRISE->label())->toBe('B2B Enterprise')
        ->and(TenantType::B2B_SMB->label())->toBe('B2B SMB')
        ->and(TenantType::B2C_BRAND->label())->toBe('B2C Brand')
        ->and(TenantType::INDIVIDUAL->label())->toBe('Individual')
        ->and(TenantType::INFLUENCER->label())->toBe('Influencer')
        ->and(TenantType::NON_PROFIT->label())->toBe('Non-Profit');
});

test('description returns correct descriptions', function (): void {
    expect(TenantType::B2B_ENTERPRISE->description())->toBe('Large enterprise customers with complex needs')
        ->and(TenantType::B2B_SMB->description())->toBe('Small and medium business customers')
        ->and(TenantType::B2C_BRAND->description())->toBe('Consumer brands focusing on B2C marketing')
        ->and(TenantType::INDIVIDUAL->description())->toBe('Individual users and freelancers')
        ->and(TenantType::INFLUENCER->description())->toBe('Social media influencers and content creators')
        ->and(TenantType::NON_PROFIT->description())->toBe('Non-profit organizations with special pricing');
});

test('requiresBusinessProfile returns true for business types', function (): void {
    expect(TenantType::B2B_ENTERPRISE->requiresBusinessProfile())->toBeTrue()
        ->and(TenantType::B2B_SMB->requiresBusinessProfile())->toBeTrue()
        ->and(TenantType::B2C_BRAND->requiresBusinessProfile())->toBeTrue()
        ->and(TenantType::NON_PROFIT->requiresBusinessProfile())->toBeTrue();
});

test('requiresBusinessProfile returns false for individual types', function (): void {
    expect(TenantType::INDIVIDUAL->requiresBusinessProfile())->toBeFalse()
        ->and(TenantType::INFLUENCER->requiresBusinessProfile())->toBeFalse();
});

test('isB2B returns true only for B2B types', function (): void {
    expect(TenantType::B2B_ENTERPRISE->isB2B())->toBeTrue()
        ->and(TenantType::B2B_SMB->isB2B())->toBeTrue()
        ->and(TenantType::B2C_BRAND->isB2B())->toBeFalse()
        ->and(TenantType::INDIVIDUAL->isB2B())->toBeFalse()
        ->and(TenantType::INFLUENCER->isB2B())->toBeFalse()
        ->and(TenantType::NON_PROFIT->isB2B())->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = TenantType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(6)
        ->and($values)->toContain('b2b_enterprise')
        ->and($values)->toContain('b2b_smb')
        ->and($values)->toContain('b2c_brand')
        ->and($values)->toContain('individual')
        ->and($values)->toContain('influencer')
        ->and($values)->toContain('non_profit');
});

test('can create enum from string value', function (): void {
    $type = TenantType::from('b2b_enterprise');

    expect($type)->toBe(TenantType::B2B_ENTERPRISE);
});

test('tryFrom returns null for invalid value', function (): void {
    $type = TenantType::tryFrom('invalid');

    expect($type)->toBeNull();
});
