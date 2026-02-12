<?php

declare(strict_types=1);

/**
 * Invoice Model Unit Tests
 *
 * Tests for the Invoice model which represents
 * billing invoices for tenants.
 *
 * @see \App\Models\Billing\Invoice
 */

use App\Enums\Billing\Currency;
use App\Enums\Billing\InvoiceStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\Payment;
use App\Models\Billing\Subscription;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;

test('has correct table name', function (): void {
    $invoice = new Invoice();

    expect($invoice->getTable())->toBe('invoices');
});

test('uses uuid primary key', function (): void {
    $invoice = Invoice::factory()->create();

    expect($invoice->id)->not->toBeNull()
        ->and(strlen($invoice->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $invoice = new Invoice();
    $fillable = $invoice->getFillable();

    expect($fillable)->toContain('tenant_id')
        ->and($fillable)->toContain('subscription_id')
        ->and($fillable)->toContain('invoice_number')
        ->and($fillable)->toContain('razorpay_invoice_id')
        ->and($fillable)->toContain('status')
        ->and($fillable)->toContain('currency')
        ->and($fillable)->toContain('subtotal')
        ->and($fillable)->toContain('tax_amount')
        ->and($fillable)->toContain('total')
        ->and($fillable)->toContain('amount_paid')
        ->and($fillable)->toContain('amount_due')
        ->and($fillable)->toContain('gst_details')
        ->and($fillable)->toContain('billing_address')
        ->and($fillable)->toContain('line_items')
        ->and($fillable)->toContain('issued_at')
        ->and($fillable)->toContain('due_at')
        ->and($fillable)->toContain('paid_at')
        ->and($fillable)->toContain('pdf_url');
});

test('status casts to enum', function (): void {
    $invoice = Invoice::factory()->issued()->create();

    expect($invoice->status)->toBeInstanceOf(InvoiceStatus::class)
        ->and($invoice->status)->toBe(InvoiceStatus::ISSUED);
});

test('currency casts to enum', function (): void {
    $invoice = Invoice::factory()->create(['currency' => Currency::INR]);

    expect($invoice->currency)->toBeInstanceOf(Currency::class)
        ->and($invoice->currency)->toBe(Currency::INR);
});

test('timestamp fields cast to datetime', function (): void {
    $invoice = Invoice::factory()->paid()->create();

    expect($invoice->issued_at)->toBeInstanceOf(\Carbon\Carbon::class)
        ->and($invoice->due_at)->toBeInstanceOf(\Carbon\Carbon::class)
        ->and($invoice->paid_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('json fields cast to array', function (): void {
    $invoice = Invoice::factory()->create();

    expect($invoice->gst_details)->toBeArray()
        ->and($invoice->billing_address)->toBeArray()
        ->and($invoice->line_items)->toBeArray();
});

test('tenant relationship returns belongs to', function (): void {
    $invoice = new Invoice();

    expect($invoice->tenant())->toBeInstanceOf(BelongsTo::class);
});

test('tenant relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $invoice = Invoice::factory()->forTenant($tenant)->create();

    expect($invoice->tenant)->toBeInstanceOf(Tenant::class)
        ->and($invoice->tenant->id)->toBe($tenant->id);
});

test('subscription relationship returns belongs to', function (): void {
    $invoice = new Invoice();

    expect($invoice->subscription())->toBeInstanceOf(BelongsTo::class);
});

test('subscription relationship works correctly', function (): void {
    $subscription = Subscription::factory()->create();
    $invoice = Invoice::factory()->forSubscription($subscription)->create();

    expect($invoice->subscription)->toBeInstanceOf(Subscription::class)
        ->and($invoice->subscription->id)->toBe($subscription->id);
});

test('payments relationship returns has many', function (): void {
    $invoice = new Invoice();

    expect($invoice->payments())->toBeInstanceOf(HasMany::class);
});

test('payments relationship works correctly', function (): void {
    $invoice = Invoice::factory()->create();
    Payment::factory()->count(2)->forInvoice($invoice)->create();

    expect($invoice->payments)->toHaveCount(2)
        ->and($invoice->payments->first())->toBeInstanceOf(Payment::class);
});

test('scope forTenant filters by tenant', function (): void {
    $tenant = Tenant::factory()->create();
    Invoice::factory()->forTenant($tenant)->create();
    Invoice::factory()->count(2)->create();

    $invoices = Invoice::forTenant($tenant->id)->get();

    expect($invoices)->toHaveCount(1)
        ->and($invoices->first()->tenant_id)->toBe($tenant->id);
});

test('scope paid filters correctly', function (): void {
    Invoice::factory()->count(3)->paid()->create();
    Invoice::factory()->count(2)->issued()->create();

    $paidInvoices = Invoice::paid()->get();

    expect($paidInvoices)->toHaveCount(3)
        ->and($paidInvoices->every(fn ($i) => $i->status === InvoiceStatus::PAID))->toBeTrue();
});

test('scope unpaid filters correctly', function (): void {
    Invoice::factory()->count(2)->paid()->create();
    Invoice::factory()->count(3)->issued()->create();

    $unpaidInvoices = Invoice::unpaid()->get();

    expect($unpaidInvoices)->toHaveCount(3)
        ->and($unpaidInvoices->every(fn ($i) => $i->status === InvoiceStatus::ISSUED))->toBeTrue();
});

test('scope overdue filters correctly', function (): void {
    Invoice::factory()->overdue()->create();
    Invoice::factory()->issued()->create(['due_at' => now()->addDays(10)]);
    Invoice::factory()->paid()->create();

    $overdueInvoices = Invoice::overdue()->get();

    expect($overdueInvoices)->toHaveCount(1);
});

test('generateInvoiceNumber creates valid format', function (): void {
    $invoiceNumber = Invoice::generateInvoiceNumber();

    // Format: BIZ/YYYY-YY/NNNNN
    expect($invoiceNumber)->toMatch('/^BIZ\/\d{4}-\d{2}\/\d{5}$/');
});

test('generateInvoiceNumber increments sequentially', function (): void {
    // Create first invoice using the generator
    $tenant = Tenant::factory()->create();
    $first = Invoice::create([
        'tenant_id' => $tenant->id,
        'invoice_number' => Invoice::generateInvoiceNumber(),
        'status' => InvoiceStatus::DRAFT,
        'currency' => Currency::INR,
        'subtotal' => 1000,
        'tax_amount' => 180,
        'total' => 1180,
        'amount_paid' => 0,
        'amount_due' => 1180,
        'billing_address' => ['name' => 'Test'],
        'line_items' => [],
    ]);

    // Create second invoice using the generator
    $second = Invoice::create([
        'tenant_id' => $tenant->id,
        'invoice_number' => Invoice::generateInvoiceNumber(),
        'status' => InvoiceStatus::DRAFT,
        'currency' => Currency::INR,
        'subtotal' => 1000,
        'tax_amount' => 180,
        'total' => 1180,
        'amount_paid' => 0,
        'amount_due' => 1180,
        'billing_address' => ['name' => 'Test'],
        'line_items' => [],
    ]);

    $firstNumber = (int) substr($first->invoice_number, -5);
    $secondNumber = (int) substr($second->invoice_number, -5);

    expect($secondNumber)->toBe($firstNumber + 1);
});

test('invoice number auto-generates on create', function (): void {
    $tenant = Tenant::factory()->create();
    $invoice = Invoice::create([
        'tenant_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
        'currency' => Currency::INR,
        'subtotal' => 1000,
        'tax_amount' => 180,
        'total' => 1180,
        'amount_paid' => 0,
        'amount_due' => 1180,
        'billing_address' => ['name' => 'Test'],
        'line_items' => [],
    ]);

    expect($invoice->invoice_number)->not->toBeNull()
        ->and($invoice->invoice_number)->toStartWith('BIZ/');
});

test('invoice_number must be unique', function (): void {
    $invoice1 = Invoice::factory()->create();

    expect(fn () => Invoice::factory()->create(['invoice_number' => $invoice1->invoice_number]))
        ->toThrow(QueryException::class);
});

test('isPaid returns true only for paid status', function (): void {
    $paid = Invoice::factory()->paid()->create();
    $issued = Invoice::factory()->issued()->create();
    $draft = Invoice::factory()->draft()->create();

    expect($paid->isPaid())->toBeTrue()
        ->and($issued->isPaid())->toBeFalse()
        ->and($draft->isPaid())->toBeFalse();
});

test('isOverdue returns true when past due date and issued', function (): void {
    $overdue = Invoice::factory()->overdue()->create();
    $notDue = Invoice::factory()->issued()->create(['due_at' => now()->addDays(10)]);
    $paid = Invoice::factory()->paid()->create(['due_at' => now()->subDays(5)]);

    expect($overdue->isOverdue())->toBeTrue()
        ->and($notDue->isOverdue())->toBeFalse()
        ->and($paid->isOverdue())->toBeFalse();
});

test('getFormattedTotal returns formatted amount with symbol', function (): void {
    $inrInvoice = Invoice::factory()->create([
        'currency' => Currency::INR,
        'total' => 2499.00,
    ]);

    $usdInvoice = Invoice::factory()->create([
        'currency' => Currency::USD,
        'total' => 49.99,
    ]);

    expect($inrInvoice->getFormattedTotal())->toBe('₹2,499.00')
        ->and($usdInvoice->getFormattedTotal())->toBe('$49.99');
});

test('markAsPaid updates status and amounts', function (): void {
    $invoice = Invoice::factory()->issued()->create([
        'total' => 1000,
        'amount_paid' => 0,
        'amount_due' => 1000,
    ]);

    $invoice->markAsPaid();

    expect($invoice->status)->toBe(InvoiceStatus::PAID)
        ->and($invoice->paid_at)->not->toBeNull()
        ->and((float) $invoice->amount_paid)->toBe(1000.0)
        ->and((float) $invoice->amount_due)->toBe(0.0);
});

test('markAsCancelled updates status', function (): void {
    $invoice = Invoice::factory()->issued()->create();

    $invoice->markAsCancelled();

    expect($invoice->status)->toBe(InvoiceStatus::CANCELLED);
});

test('calculateGst returns CGST and SGST for same state', function (): void {
    $invoice = Invoice::factory()->create(['subtotal' => 1000]);

    $gst = $invoice->calculateGst('Maharashtra', 'Maharashtra');

    expect($gst['cgst'])->toBe(90.0)
        ->and($gst['sgst'])->toBe(90.0)
        ->and($gst['igst'])->toBe(0.0)
        ->and($gst['total_gst'])->toBe(180.0)
        ->and($gst['place_of_supply'])->toBe('Maharashtra');
});

test('calculateGst returns IGST for different state', function (): void {
    $invoice = Invoice::factory()->create(['subtotal' => 1000]);

    $gst = $invoice->calculateGst('Delhi', 'Maharashtra');

    expect($gst['cgst'])->toBe(0.0)
        ->and($gst['sgst'])->toBe(0.0)
        ->and($gst['igst'])->toBe(180.0)
        ->and($gst['total_gst'])->toBe(180.0)
        ->and($gst['place_of_supply'])->toBe('Delhi');
});

test('addLineItem adds to line_items array', function (): void {
    $invoice = Invoice::factory()->create(['line_items' => []]);

    $invoice->addLineItem([
        'description' => 'Test Item',
        'quantity' => 1,
        'unit_price' => 500,
        'amount' => 500,
    ]);

    $invoice->refresh();

    expect($invoice->line_items)->toHaveCount(1)
        ->and($invoice->line_items[0]['description'])->toBe('Test Item');
});

test('getLineItems returns line items array', function (): void {
    $lineItems = [
        ['description' => 'Item 1', 'amount' => 100],
        ['description' => 'Item 2', 'amount' => 200],
    ];

    $invoice = Invoice::factory()->create(['line_items' => $lineItems]);

    expect($invoice->getLineItems())->toHaveCount(2)
        ->and($invoice->getLineItems()[0]['description'])->toBe('Item 1');
});

test('factory creates valid model', function (): void {
    $invoice = Invoice::factory()->create();

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->id)->not->toBeNull()
        ->and($invoice->tenant_id)->not->toBeNull()
        ->and($invoice->invoice_number)->not->toBeNull()
        ->and($invoice->status)->toBeInstanceOf(InvoiceStatus::class)
        ->and($invoice->currency)->toBeInstanceOf(Currency::class)
        ->and($invoice->billing_address)->toBeArray()
        ->and($invoice->line_items)->toBeArray();
});
