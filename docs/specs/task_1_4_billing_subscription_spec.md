# Task 1.4: Billing & Subscription Migrations - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 1.4 Billing & Subscription Migrations
- **Dependencies**: Task 1.2 (Tenant Management) - COMPLETED

---

## 1. Overview

This task implements the billing and subscription infrastructure integrated with Razorpay. These entities track tenant subscriptions, invoices, payments, and stored payment methods.

### Entities to Implement
1. **Subscription** - Tenant subscription to a plan
2. **Invoice** - Billing records with GST support
3. **Payment** - Payment transactions
4. **PaymentMethod** - Stored payment methods (tokenized)

---

## 2. Enums

### 2.1 SubscriptionStatus Enum
**File**: `app/Enums/Billing/SubscriptionStatus.php`

```php
enum SubscriptionStatus: string
{
    case CREATED = 'created';           // Just created, awaiting payment
    case AUTHENTICATED = 'authenticated'; // Payment authenticated
    case ACTIVE = 'active';             // Active subscription
    case PENDING = 'pending';           // Payment due, grace period
    case HALTED = 'halted';             // Payment failed multiple times
    case CANCELLED = 'cancelled';       // Cancelled by user
    case COMPLETED = 'completed';       // Natural end (yearly plan)
    case EXPIRED = 'expired';           // Authentication expired

    public function label(): string;
    public function isActive(): bool;  // ACTIVE only
    public function hasAccess(): bool; // ACTIVE, PENDING, AUTHENTICATED
    public function isFinal(): bool;   // CANCELLED, COMPLETED, EXPIRED
}
```

### 2.2 BillingCycle Enum
**File**: `app/Enums/Billing/BillingCycle.php`

```php
enum BillingCycle: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    public function label(): string;
    public function intervalMonths(): int;  // 1 or 12
    public function discountLabel(): string;  // "2 months free" for yearly
}
```

### 2.3 InvoiceStatus Enum
**File**: `app/Enums/Billing/InvoiceStatus.php`

```php
enum InvoiceStatus: string
{
    case DRAFT = 'draft';       // Being prepared
    case ISSUED = 'issued';     // Sent to customer
    case PAID = 'paid';         // Payment received
    case CANCELLED = 'cancelled'; // Cancelled
    case EXPIRED = 'expired';   // Past due date

    public function label(): string;
    public function isPaid(): bool;
    public function isPayable(): bool;  // ISSUED only
}
```

### 2.4 PaymentStatus Enum
**File**: `app/Enums/Billing/PaymentStatus.php`

```php
enum PaymentStatus: string
{
    case CREATED = 'created';       // Payment initiated
    case AUTHORIZED = 'authorized'; // Authorized, not captured
    case CAPTURED = 'captured';     // Payment successful
    case FAILED = 'failed';         // Payment failed
    case REFUNDED = 'refunded';     // Refunded

    public function label(): string;
    public function isSuccessful(): bool;  // CAPTURED only
    public function isFinal(): bool;       // CAPTURED, FAILED, REFUNDED
}
```

### 2.5 PaymentMethodType Enum
**File**: `app/Enums/Billing/PaymentMethodType.php`

```php
enum PaymentMethodType: string
{
    case CARD = 'card';
    case UPI = 'upi';
    case NETBANKING = 'netbanking';
    case WALLET = 'wallet';
    case EMANDATE = 'emandate';

    public function label(): string;
    public function icon(): string;  // e.g., 'credit-card', 'smartphone'
    public function supportsRecurring(): bool;  // CARD, EMANDATE support recurring
}
```

### 2.6 Currency Enum
**File**: `app/Enums/Billing/Currency.php`

```php
enum Currency: string
{
    case INR = 'INR';
    case USD = 'USD';

    public function label(): string;
    public function symbol(): string;  // ₹ or $
    public function minorUnits(): int; // 100 (paise/cents)
}
```

---

## 3. Migrations

### 3.1 Create Subscriptions Table
**File**: `database/migrations/2026_02_06_400001_create_subscriptions_table.php`

```php
Schema::create('subscriptions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id');
    $table->uuid('plan_id');
    $table->string('status', 20)->default('created');  // SubscriptionStatus
    $table->string('billing_cycle', 20);  // BillingCycle
    $table->string('currency', 3);  // Currency
    $table->decimal('amount', 10, 2);
    $table->string('razorpay_subscription_id', 100)->nullable()->unique();
    $table->string('razorpay_customer_id', 100)->nullable();
    $table->timestamp('current_period_start')->nullable();
    $table->timestamp('current_period_end')->nullable();
    $table->timestamp('trial_start')->nullable();
    $table->timestamp('trial_end')->nullable();
    $table->timestamp('cancelled_at')->nullable();
    $table->boolean('cancel_at_period_end')->default(false);
    $table->timestamp('ended_at')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('status');
    $table->index('current_period_end');
    $table->index('razorpay_subscription_id');

    // Foreign keys
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->cascadeOnDelete();

    $table->foreign('plan_id')
        ->references('id')
        ->on('plan_definitions')
        ->restrictOnDelete();
});
```

### 3.2 Create Invoices Table
**File**: `database/migrations/2026_02_06_400002_create_invoices_table.php`

```php
Schema::create('invoices', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id');
    $table->uuid('subscription_id')->nullable();
    $table->string('invoice_number', 50)->unique();
    $table->string('razorpay_invoice_id', 100)->nullable()->unique();
    $table->string('status', 20)->default('draft');  // InvoiceStatus
    $table->string('currency', 3);
    $table->decimal('subtotal', 10, 2);
    $table->decimal('tax_amount', 10, 2)->default(0);
    $table->decimal('total', 10, 2);
    $table->decimal('amount_paid', 10, 2)->default(0);
    $table->decimal('amount_due', 10, 2);
    $table->json('gst_details')->nullable();  // CGST, SGST, IGST breakdown
    $table->json('billing_address');  // Address snapshot
    $table->json('line_items');  // Invoice items
    $table->timestamp('issued_at')->nullable();
    $table->timestamp('due_at')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->string('pdf_url', 500)->nullable();
    $table->timestamps();

    // Indexes
    $table->index('status');
    $table->index('issued_at');
    $table->index('due_at');

    // Foreign keys
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->cascadeOnDelete();

    $table->foreign('subscription_id')
        ->references('id')
        ->on('subscriptions')
        ->nullOnDelete();
});
```

**GST Details JSON Structure**:
```json
{
    "gstin": "27AABCU9603R1ZM",
    "place_of_supply": "Maharashtra",
    "cgst": 224.91,
    "sgst": 224.91,
    "igst": 0,
    "total_gst": 449.82
}
```

**Billing Address JSON Structure**:
```json
{
    "name": "Acme Corporation",
    "address_line1": "123 Business Park",
    "address_line2": "Suite 456",
    "city": "Mumbai",
    "state": "Maharashtra",
    "country": "IN",
    "postal_code": "400001",
    "gstin": "27AABCU9603R1ZM"
}
```

**Line Items JSON Structure**:
```json
[
    {
        "description": "Professional Plan - Monthly",
        "quantity": 1,
        "unit_price": 2499.00,
        "amount": 2499.00,
        "hsn_code": "998314"
    }
]
```

### 3.3 Create Payments Table
**File**: `database/migrations/2026_02_06_400003_create_payments_table.php`

```php
Schema::create('payments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id');
    $table->uuid('subscription_id')->nullable();
    $table->uuid('invoice_id')->nullable();
    $table->string('razorpay_payment_id', 100)->nullable()->unique();
    $table->string('razorpay_order_id', 100)->nullable();
    $table->string('status', 20)->default('created');  // PaymentStatus
    $table->decimal('amount', 10, 2);
    $table->string('currency', 3);
    $table->string('method', 50)->nullable();  // card, upi, netbanking
    $table->json('method_details')->nullable();  // Masked card details etc.
    $table->decimal('fee', 10, 2)->nullable();  // Razorpay fee
    $table->decimal('tax_on_fee', 10, 2)->nullable();  // GST on fee
    $table->string('error_code', 100)->nullable();
    $table->text('error_description')->nullable();
    $table->timestamp('captured_at')->nullable();
    $table->timestamp('refunded_at')->nullable();
    $table->decimal('refund_amount', 10, 2)->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('status');
    $table->index('razorpay_payment_id');
    $table->index('created_at');

    // Foreign keys
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->cascadeOnDelete();

    $table->foreign('subscription_id')
        ->references('id')
        ->on('subscriptions')
        ->nullOnDelete();

    $table->foreign('invoice_id')
        ->references('id')
        ->on('invoices')
        ->nullOnDelete();
});
```

### 3.4 Create Payment Methods Table
**File**: `database/migrations/2026_02_06_400004_create_payment_methods_table.php`

```php
Schema::create('payment_methods', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id');
    $table->string('razorpay_token_id', 100)->nullable();
    $table->string('type', 20);  // PaymentMethodType
    $table->boolean('is_default')->default(false);
    $table->json('details');  // Masked details
    $table->timestamp('expires_at')->nullable();  // For cards
    $table->timestamps();

    // Indexes
    $table->index(['tenant_id', 'is_default']);
    $table->index('type');

    // Foreign key
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->cascadeOnDelete();
});
```

**Card Details JSON**:
```json
{
    "last4": "4242",
    "brand": "Visa",
    "exp_month": 12,
    "exp_year": 2027,
    "name": "John Doe"
}
```

**UPI Details JSON**:
```json
{
    "vpa": "john@upi"
}
```

---

## 4. Models

### 4.1 Subscription Model
**File**: `app/Models/Billing/Subscription.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Enums\Billing\BillingCycle;
use App\Enums\Billing\Currency;
use App\Enums\Billing\SubscriptionStatus;
use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Subscription extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'subscriptions';

    protected $fillable = [...];

    protected function casts(): array;

    // Relationships
    public function tenant(): BelongsTo;
    public function plan(): BelongsTo;
    public function invoices(): HasMany;
    public function payments(): HasMany;

    // Scopes
    public function scopeActive(Builder $query): Builder;
    public function scopeForTenant(Builder $query, string $tenantId): Builder;

    // Helper methods
    public function isActive(): bool;
    public function hasAccess(): bool;
    public function isOnTrial(): bool;
    public function trialDaysRemaining(): int;
    public function daysUntilRenewal(): int;
    public function cancel(bool $atPeriodEnd = true): void;
    public function reactivate(): void;
    public function changePlan(PlanDefinition $newPlan): void;
    public function markAsActive(): void;
    public function markAsPending(): void;
    public function markAsHalted(): void;
    public function markAsCancelled(): void;
}
```

### 4.2 Invoice Model
**File**: `app/Models/Billing/Invoice.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Enums\Billing\Currency;
use App\Enums\Billing\InvoiceStatus;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Invoice extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'invoices';

    // Invoice number format: BIZ/YYYY-YY/NNNNN
    public const NUMBER_PREFIX = 'BIZ';

    protected $fillable = [...];

    protected function casts(): array;

    // Boot method for auto-generating invoice number
    protected static function boot(): void;

    // Relationships
    public function tenant(): BelongsTo;
    public function subscription(): BelongsTo;
    public function payments(): HasMany;

    // Scopes
    public function scopeForTenant(Builder $query, string $tenantId): Builder;
    public function scopePaid(Builder $query): Builder;
    public function scopeUnpaid(Builder $query): Builder;
    public function scopeOverdue(Builder $query): Builder;

    // Static methods
    public static function generateInvoiceNumber(): string;

    // Helper methods
    public function isPaid(): bool;
    public function isOverdue(): bool;
    public function getFormattedTotal(): string;  // "₹2,499.00"
    public function markAsPaid(): void;
    public function markAsCancelled(): void;
    public function calculateGst(string $customerState, string $businessState = 'Maharashtra'): array;
    public function addLineItem(array $item): void;
    public function getLineItems(): array;
}
```

### 4.3 Payment Model
**File**: `app/Models/Billing/Payment.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Enums\Billing\Currency;
use App\Enums\Billing\PaymentStatus;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'payments';

    protected $fillable = [...];

    protected function casts(): array;

    // Relationships
    public function tenant(): BelongsTo;
    public function subscription(): BelongsTo;
    public function invoice(): BelongsTo;

    // Scopes
    public function scopeForTenant(Builder $query, string $tenantId): Builder;
    public function scopeSuccessful(Builder $query): Builder;
    public function scopeFailed(Builder $query): Builder;

    // Helper methods
    public function isSuccessful(): bool;
    public function isFailed(): bool;
    public function isRefunded(): bool;
    public function markAsCaptured(): void;
    public function markAsFailed(string $errorCode, string $errorDescription): void;
    public function markAsRefunded(float $amount): void;
    public function getNetAmount(): float;  // amount - fee - tax_on_fee
}
```

### 4.4 PaymentMethod Model
**File**: `app/Models/Billing/PaymentMethod.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Enums\Billing\PaymentMethodType;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaymentMethod extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'payment_methods';

    protected $fillable = [...];

    protected function casts(): array;

    // Relationships
    public function tenant(): BelongsTo;

    // Scopes
    public function scopeForTenant(Builder $query, string $tenantId): Builder;
    public function scopeDefault(Builder $query): Builder;
    public function scopeOfType(Builder $query, PaymentMethodType $type): Builder;

    // Helper methods
    public function isExpired(): bool;
    public function isDefault(): bool;
    public function setAsDefault(): void;
    public function getDisplayName(): string;  // "Visa ending in 4242"
    public function getLast4(): ?string;
    public function getExpiryDate(): ?string;  // "12/27"
}
```

---

## 5. Factories

### 5.1 SubscriptionFactory
**File**: `database/factories/Billing/SubscriptionFactory.php`

State methods: `created()`, `authenticated()`, `active()`, `pending()`, `halted()`, `cancelled()`, `completed()`, `expired()`, `onTrial()`, `monthly()`, `yearly()`, `inr()`, `usd()`, `forTenant()`, `forPlan()`

### 5.2 InvoiceFactory
**File**: `database/factories/Billing/InvoiceFactory.php`

State methods: `draft()`, `issued()`, `paid()`, `cancelled()`, `expired()`, `overdue()`, `forTenant()`, `forSubscription()`, `withGst()`, `withLineItems()`

### 5.3 PaymentFactory
**File**: `database/factories/Billing/PaymentFactory.php`

State methods: `created()`, `authorized()`, `captured()`, `failed()`, `refunded()`, `forTenant()`, `forSubscription()`, `forInvoice()`, `card()`, `upi()`, `netbanking()`

### 5.4 PaymentMethodFactory
**File**: `database/factories/Billing/PaymentMethodFactory.php`

State methods: `card()`, `upi()`, `netbanking()`, `wallet()`, `emandate()`, `default()`, `expired()`, `forTenant()`

---

## 6. Seeders

### 6.1 SubscriptionSeeder
Create subscriptions for each active tenant matching their plan.

### 6.2 InvoiceSeeder
Create sample invoices (paid and pending).

### 6.3 PaymentSeeder
Create sample payments for paid invoices.

### 6.4 PaymentMethodSeeder
Create default payment methods for paying tenants.

### 6.5 BillingSeeder (Orchestrator)
Call all billing seeders in order.

---

## 7. Test Requirements

### 7.1 Enum Tests (6 files)
- `tests/Unit/Enums/Billing/SubscriptionStatusTest.php`
- `tests/Unit/Enums/Billing/BillingCycleTest.php`
- `tests/Unit/Enums/Billing/InvoiceStatusTest.php`
- `tests/Unit/Enums/Billing/PaymentStatusTest.php`
- `tests/Unit/Enums/Billing/PaymentMethodTypeTest.php`
- `tests/Unit/Enums/Billing/CurrencyTest.php`

### 7.2 Model Tests (4 files)
- `tests/Unit/Models/Billing/SubscriptionTest.php`
- `tests/Unit/Models/Billing/InvoiceTest.php`
- `tests/Unit/Models/Billing/PaymentTest.php`
- `tests/Unit/Models/Billing/PaymentMethodTest.php`

Test all methods, relationships, scopes, and business logic.

---

## 8. Implementation Checklist

- [ ] Create SubscriptionStatus enum
- [ ] Create BillingCycle enum
- [ ] Create InvoiceStatus enum
- [ ] Create PaymentStatus enum
- [ ] Create PaymentMethodType enum
- [ ] Create Currency enum
- [ ] Create subscriptions migration
- [ ] Create invoices migration
- [ ] Create payments migration
- [ ] Create payment_methods migration
- [ ] Create Subscription model
- [ ] Create Invoice model
- [ ] Create Payment model
- [ ] Create PaymentMethod model
- [ ] Create SubscriptionFactory
- [ ] Create InvoiceFactory
- [ ] Create PaymentFactory
- [ ] Create PaymentMethodFactory
- [ ] Create SubscriptionSeeder
- [ ] Create InvoiceSeeder
- [ ] Create PaymentSeeder
- [ ] Create PaymentMethodSeeder
- [ ] Create BillingSeeder orchestrator
- [ ] Update DatabaseSeeder
- [ ] Create all enum tests
- [ ] Create all model tests
- [ ] Run migrations and seeders
- [ ] All tests pass

---

## 9. Notes

1. **Razorpay Integration**: This task only creates the database schema. Razorpay service integration will be done in a later task.

2. **Invoice Number**: Auto-generated in format `BIZ/2026-27/00001` where:
   - `BIZ` is the company prefix
   - `2026-27` is the financial year (April to March in India)
   - `00001` is a sequential number

3. **GST Calculation**: 18% total (9% CGST + 9% SGST for same state, 18% IGST for different state)

4. **Currency Handling**: Amounts stored in major units (rupees/dollars), not minor units (paise/cents)

5. **PCI Compliance**: Never store raw card numbers. Only store Razorpay tokens and masked details (last4, brand, expiry).
