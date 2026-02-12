<?php

declare(strict_types=1);

/**
 * Payment Model Unit Tests
 *
 * Tests for the Payment model which represents
 * payment transactions.
 *
 * @see \App\Models\Billing\Payment
 */

use App\Enums\Billing\Currency;
use App\Enums\Billing\PaymentStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\Payment;
use App\Models\Billing\Subscription;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('has correct table name', function (): void {
    $payment = new Payment();

    expect($payment->getTable())->toBe('payments');
});

test('uses uuid primary key', function (): void {
    $payment = Payment::factory()->create();

    expect($payment->id)->not->toBeNull()
        ->and(strlen($payment->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $payment = new Payment();
    $fillable = $payment->getFillable();

    expect($fillable)->toContain('tenant_id')
        ->and($fillable)->toContain('subscription_id')
        ->and($fillable)->toContain('invoice_id')
        ->and($fillable)->toContain('razorpay_payment_id')
        ->and($fillable)->toContain('razorpay_order_id')
        ->and($fillable)->toContain('status')
        ->and($fillable)->toContain('amount')
        ->and($fillable)->toContain('currency')
        ->and($fillable)->toContain('method')
        ->and($fillable)->toContain('method_details')
        ->and($fillable)->toContain('fee')
        ->and($fillable)->toContain('tax_on_fee')
        ->and($fillable)->toContain('error_code')
        ->and($fillable)->toContain('error_description')
        ->and($fillable)->toContain('captured_at')
        ->and($fillable)->toContain('refunded_at')
        ->and($fillable)->toContain('refund_amount')
        ->and($fillable)->toContain('metadata');
});

test('status casts to enum', function (): void {
    $payment = Payment::factory()->captured()->create();

    expect($payment->status)->toBeInstanceOf(PaymentStatus::class)
        ->and($payment->status)->toBe(PaymentStatus::CAPTURED);
});

test('currency casts to enum', function (): void {
    $payment = Payment::factory()->create(['currency' => Currency::INR]);

    expect($payment->currency)->toBeInstanceOf(Currency::class)
        ->and($payment->currency)->toBe(Currency::INR);
});

test('timestamp fields cast to datetime', function (): void {
    $payment = Payment::factory()->captured()->create();

    expect($payment->captured_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('json fields cast to array', function (): void {
    $payment = Payment::factory()->create();

    expect($payment->method_details)->toBeArray();
});

test('tenant relationship returns belongs to', function (): void {
    $payment = new Payment();

    expect($payment->tenant())->toBeInstanceOf(BelongsTo::class);
});

test('tenant relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $payment = Payment::factory()->forTenant($tenant)->create();

    expect($payment->tenant)->toBeInstanceOf(Tenant::class)
        ->and($payment->tenant->id)->toBe($tenant->id);
});

test('subscription relationship returns belongs to', function (): void {
    $payment = new Payment();

    expect($payment->subscription())->toBeInstanceOf(BelongsTo::class);
});

test('subscription relationship works correctly', function (): void {
    $subscription = Subscription::factory()->create();
    $payment = Payment::factory()->forSubscription($subscription)->create();

    expect($payment->subscription)->toBeInstanceOf(Subscription::class)
        ->and($payment->subscription->id)->toBe($subscription->id);
});

test('invoice relationship returns belongs to', function (): void {
    $payment = new Payment();

    expect($payment->invoice())->toBeInstanceOf(BelongsTo::class);
});

test('invoice relationship works correctly', function (): void {
    $invoice = Invoice::factory()->create();
    $payment = Payment::factory()->forInvoice($invoice)->create();

    expect($payment->invoice)->toBeInstanceOf(Invoice::class)
        ->and($payment->invoice->id)->toBe($invoice->id);
});

test('scope forTenant filters by tenant', function (): void {
    $tenant = Tenant::factory()->create();
    Payment::factory()->forTenant($tenant)->create();
    Payment::factory()->count(2)->create();

    $payments = Payment::forTenant($tenant->id)->get();

    expect($payments)->toHaveCount(1)
        ->and($payments->first()->tenant_id)->toBe($tenant->id);
});

test('scope successful filters correctly', function (): void {
    Payment::factory()->count(3)->captured()->create();
    Payment::factory()->count(2)->failed()->create();

    $successfulPayments = Payment::successful()->get();

    expect($successfulPayments)->toHaveCount(3)
        ->and($successfulPayments->every(fn ($p) => $p->status === PaymentStatus::CAPTURED))->toBeTrue();
});

test('scope failed filters correctly', function (): void {
    Payment::factory()->count(2)->captured()->create();
    Payment::factory()->count(3)->failed()->create();

    $failedPayments = Payment::failed()->get();

    expect($failedPayments)->toHaveCount(3)
        ->and($failedPayments->every(fn ($p) => $p->status === PaymentStatus::FAILED))->toBeTrue();
});

test('isSuccessful returns true only for captured status', function (): void {
    $captured = Payment::factory()->captured()->create();
    $failed = Payment::factory()->failed()->create();
    $created = Payment::factory()->created()->create();

    expect($captured->isSuccessful())->toBeTrue()
        ->and($failed->isSuccessful())->toBeFalse()
        ->and($created->isSuccessful())->toBeFalse();
});

test('isFailed returns true only for failed status', function (): void {
    $failed = Payment::factory()->failed()->create();
    $captured = Payment::factory()->captured()->create();
    $created = Payment::factory()->created()->create();

    expect($failed->isFailed())->toBeTrue()
        ->and($captured->isFailed())->toBeFalse()
        ->and($created->isFailed())->toBeFalse();
});

test('isRefunded returns true only for refunded status', function (): void {
    $refunded = Payment::factory()->refunded()->create();
    $captured = Payment::factory()->captured()->create();

    expect($refunded->isRefunded())->toBeTrue()
        ->and($captured->isRefunded())->toBeFalse();
});

test('markAsCaptured updates status and timestamp', function (): void {
    $payment = Payment::factory()->authorized()->create();

    $payment->markAsCaptured();

    expect($payment->status)->toBe(PaymentStatus::CAPTURED)
        ->and($payment->captured_at)->not->toBeNull();
});

test('markAsFailed updates status and error details', function (): void {
    $payment = Payment::factory()->created()->create();

    $payment->markAsFailed('BAD_REQUEST_ERROR', 'Invalid card number');

    expect($payment->status)->toBe(PaymentStatus::FAILED)
        ->and($payment->error_code)->toBe('BAD_REQUEST_ERROR')
        ->and($payment->error_description)->toBe('Invalid card number');
});

test('markAsRefunded updates status and refund details', function (): void {
    $payment = Payment::factory()->captured()->create(['amount' => 1000]);

    $payment->markAsRefunded(1000);

    expect($payment->status)->toBe(PaymentStatus::REFUNDED)
        ->and($payment->refunded_at)->not->toBeNull()
        ->and((float) $payment->refund_amount)->toBe(1000.0);
});

test('getNetAmount calculates correctly', function (): void {
    $payment = Payment::factory()->create([
        'amount' => 1000,
        'fee' => 20,
        'tax_on_fee' => 3.60,
    ]);

    expect($payment->getNetAmount())->toBe(976.40);
});

test('getNetAmount handles null fee', function (): void {
    $payment = Payment::factory()->create([
        'amount' => 1000,
        'fee' => null,
        'tax_on_fee' => null,
    ]);

    expect($payment->getNetAmount())->toBe(1000.0);
});

test('card payment has correct method details', function (): void {
    $payment = Payment::factory()->card()->create();

    expect($payment->method)->toBe('card')
        ->and($payment->method_details)->toHaveKey('last4')
        ->and($payment->method_details)->toHaveKey('brand')
        ->and($payment->method_details)->toHaveKey('exp_month')
        ->and($payment->method_details)->toHaveKey('exp_year');
});

test('upi payment has correct method details', function (): void {
    $payment = Payment::factory()->upi()->create();

    expect($payment->method)->toBe('upi')
        ->and($payment->method_details)->toHaveKey('vpa');
});

test('netbanking payment has correct method details', function (): void {
    $payment = Payment::factory()->netbanking()->create();

    expect($payment->method)->toBe('netbanking')
        ->and($payment->method_details)->toHaveKey('bank');
});

test('factory creates valid model', function (): void {
    $payment = Payment::factory()->create();

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->id)->not->toBeNull()
        ->and($payment->tenant_id)->not->toBeNull()
        ->and($payment->status)->toBeInstanceOf(PaymentStatus::class)
        ->and($payment->currency)->toBeInstanceOf(Currency::class)
        ->and($payment->amount)->not->toBeNull();
});
