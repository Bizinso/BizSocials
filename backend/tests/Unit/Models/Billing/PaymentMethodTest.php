<?php

declare(strict_types=1);

/**
 * PaymentMethod Model Unit Tests
 *
 * Tests for the PaymentMethod model which represents
 * stored payment methods for tenants.
 *
 * @see \App\Models\Billing\PaymentMethod
 */

use App\Enums\Billing\PaymentMethodType;
use App\Models\Billing\PaymentMethod;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('has correct table name', function (): void {
    $paymentMethod = new PaymentMethod();

    expect($paymentMethod->getTable())->toBe('payment_methods');
});

test('uses uuid primary key', function (): void {
    $paymentMethod = PaymentMethod::factory()->create();

    expect($paymentMethod->id)->not->toBeNull()
        ->and(strlen($paymentMethod->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $paymentMethod = new PaymentMethod();
    $fillable = $paymentMethod->getFillable();

    expect($fillable)->toContain('tenant_id')
        ->and($fillable)->toContain('razorpay_token_id')
        ->and($fillable)->toContain('type')
        ->and($fillable)->toContain('is_default')
        ->and($fillable)->toContain('details')
        ->and($fillable)->toContain('expires_at');
});

test('type casts to enum', function (): void {
    $paymentMethod = PaymentMethod::factory()->card()->create();

    expect($paymentMethod->type)->toBeInstanceOf(PaymentMethodType::class)
        ->and($paymentMethod->type)->toBe(PaymentMethodType::CARD);
});

test('is_default casts to boolean', function (): void {
    $defaultMethod = PaymentMethod::factory()->default()->create();
    $nonDefaultMethod = PaymentMethod::factory()->create(['is_default' => false]);

    expect($defaultMethod->is_default)->toBeTrue()
        ->and($nonDefaultMethod->is_default)->toBeFalse();
});

test('details casts to array', function (): void {
    $paymentMethod = PaymentMethod::factory()->card()->create();

    expect($paymentMethod->details)->toBeArray();
});

test('expires_at casts to datetime', function (): void {
    $paymentMethod = PaymentMethod::factory()->card()->create();

    expect($paymentMethod->expires_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('tenant relationship returns belongs to', function (): void {
    $paymentMethod = new PaymentMethod();

    expect($paymentMethod->tenant())->toBeInstanceOf(BelongsTo::class);
});

test('tenant relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $paymentMethod = PaymentMethod::factory()->forTenant($tenant)->create();

    expect($paymentMethod->tenant)->toBeInstanceOf(Tenant::class)
        ->and($paymentMethod->tenant->id)->toBe($tenant->id);
});

test('scope forTenant filters by tenant', function (): void {
    $tenant = Tenant::factory()->create();
    PaymentMethod::factory()->forTenant($tenant)->create();
    PaymentMethod::factory()->count(2)->create();

    $methods = PaymentMethod::forTenant($tenant->id)->get();

    expect($methods)->toHaveCount(1)
        ->and($methods->first()->tenant_id)->toBe($tenant->id);
});

test('scope default filters correctly', function (): void {
    PaymentMethod::factory()->default()->create();
    PaymentMethod::factory()->count(2)->create(['is_default' => false]);

    $defaultMethods = PaymentMethod::default()->get();

    expect($defaultMethods)->toHaveCount(1)
        ->and($defaultMethods->first()->is_default)->toBeTrue();
});

test('scope ofType filters by type', function (): void {
    PaymentMethod::factory()->count(2)->card()->create();
    PaymentMethod::factory()->count(3)->upi()->create();

    $cardMethods = PaymentMethod::ofType(PaymentMethodType::CARD)->get();

    expect($cardMethods)->toHaveCount(2)
        ->and($cardMethods->every(fn ($m) => $m->type === PaymentMethodType::CARD))->toBeTrue();
});

test('isExpired returns true when past expires_at', function (): void {
    $expired = PaymentMethod::factory()->expired()->create();
    $valid = PaymentMethod::factory()->card()->create();
    $noExpiry = PaymentMethod::factory()->upi()->create();

    expect($expired->isExpired())->toBeTrue()
        ->and($valid->isExpired())->toBeFalse()
        ->and($noExpiry->isExpired())->toBeFalse();
});

test('isDefault returns correct value', function (): void {
    $defaultMethod = PaymentMethod::factory()->default()->create();
    $nonDefaultMethod = PaymentMethod::factory()->create(['is_default' => false]);

    expect($defaultMethod->isDefault())->toBeTrue()
        ->and($nonDefaultMethod->isDefault())->toBeFalse();
});

test('setAsDefault updates is_default and clears other defaults', function (): void {
    $tenant = Tenant::factory()->create();
    $method1 = PaymentMethod::factory()->forTenant($tenant)->default()->create();
    $method2 = PaymentMethod::factory()->forTenant($tenant)->create(['is_default' => false]);

    $method2->setAsDefault();
    $method1->refresh();

    expect($method2->is_default)->toBeTrue()
        ->and($method1->is_default)->toBeFalse();
});

test('getDisplayName returns correct format for card', function (): void {
    $paymentMethod = PaymentMethod::factory()->create([
        'type' => PaymentMethodType::CARD,
        'details' => [
            'brand' => 'Visa',
            'last4' => '4242',
        ],
    ]);

    expect($paymentMethod->getDisplayName())->toBe('Visa ending in 4242');
});

test('getDisplayName returns correct format for upi', function (): void {
    $paymentMethod = PaymentMethod::factory()->create([
        'type' => PaymentMethodType::UPI,
        'details' => [
            'vpa' => 'john@upi',
        ],
    ]);

    expect($paymentMethod->getDisplayName())->toBe('UPI - john@upi');
});

test('getDisplayName returns correct format for netbanking', function (): void {
    $paymentMethod = PaymentMethod::factory()->create([
        'type' => PaymentMethodType::NETBANKING,
        'details' => [
            'bank' => 'HDFC',
        ],
    ]);

    expect($paymentMethod->getDisplayName())->toBe('Net Banking - HDFC');
});

test('getDisplayName returns correct format for wallet', function (): void {
    $paymentMethod = PaymentMethod::factory()->create([
        'type' => PaymentMethodType::WALLET,
        'details' => [
            'provider' => 'PayTM',
        ],
    ]);

    expect($paymentMethod->getDisplayName())->toBe('Wallet - PayTM');
});

test('getDisplayName returns correct format for emandate', function (): void {
    $paymentMethod = PaymentMethod::factory()->create([
        'type' => PaymentMethodType::EMANDATE,
        'details' => [
            'bank' => 'ICICI',
        ],
    ]);

    expect($paymentMethod->getDisplayName())->toBe('e-Mandate - ICICI');
});

test('getLast4 returns last4 for cards', function (): void {
    $cardMethod = PaymentMethod::factory()->create([
        'type' => PaymentMethodType::CARD,
        'details' => ['last4' => '1234'],
    ]);

    $upiMethod = PaymentMethod::factory()->upi()->create();

    expect($cardMethod->getLast4())->toBe('1234')
        ->and($upiMethod->getLast4())->toBeNull();
});

test('getExpiryDate returns formatted expiry for cards', function (): void {
    $paymentMethod = PaymentMethod::factory()->create([
        'type' => PaymentMethodType::CARD,
        'details' => [
            'exp_month' => 3,
            'exp_year' => 2027,
        ],
    ]);

    expect($paymentMethod->getExpiryDate())->toBe('03/27');
});

test('getExpiryDate returns null for non-cards', function (): void {
    $upiMethod = PaymentMethod::factory()->upi()->create();

    expect($upiMethod->getExpiryDate())->toBeNull();
});

test('factory creates valid card model', function (): void {
    $paymentMethod = PaymentMethod::factory()->card()->create();

    expect($paymentMethod)->toBeInstanceOf(PaymentMethod::class)
        ->and($paymentMethod->id)->not->toBeNull()
        ->and($paymentMethod->tenant_id)->not->toBeNull()
        ->and($paymentMethod->type)->toBe(PaymentMethodType::CARD)
        ->and($paymentMethod->details)->toBeArray()
        ->and($paymentMethod->details)->toHaveKey('last4')
        ->and($paymentMethod->details)->toHaveKey('brand');
});

test('factory creates valid upi model', function (): void {
    $paymentMethod = PaymentMethod::factory()->upi()->create();

    expect($paymentMethod)->toBeInstanceOf(PaymentMethod::class)
        ->and($paymentMethod->type)->toBe(PaymentMethodType::UPI)
        ->and($paymentMethod->details)->toHaveKey('vpa');
});

test('factory creates valid netbanking model', function (): void {
    $paymentMethod = PaymentMethod::factory()->netbanking()->create();

    expect($paymentMethod)->toBeInstanceOf(PaymentMethod::class)
        ->and($paymentMethod->type)->toBe(PaymentMethodType::NETBANKING)
        ->and($paymentMethod->details)->toHaveKey('bank');
});

test('factory creates valid wallet model', function (): void {
    $paymentMethod = PaymentMethod::factory()->wallet()->create();

    expect($paymentMethod)->toBeInstanceOf(PaymentMethod::class)
        ->and($paymentMethod->type)->toBe(PaymentMethodType::WALLET)
        ->and($paymentMethod->details)->toHaveKey('provider');
});

test('factory creates valid emandate model', function (): void {
    $paymentMethod = PaymentMethod::factory()->emandate()->create();

    expect($paymentMethod)->toBeInstanceOf(PaymentMethod::class)
        ->and($paymentMethod->type)->toBe(PaymentMethodType::EMANDATE)
        ->and($paymentMethod->details)->toHaveKey('bank');
});
