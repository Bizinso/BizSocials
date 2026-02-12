# Task 2.7: Billing Services & API - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 2.7 Billing Services & API
- **Dependencies**: Task 2.1, Task 2.3, Task 1.6 (Billing Migrations)

---

## 1. Overview

This task implements billing management services for subscriptions, invoices, payments, and payment methods. Uses Razorpay as payment provider (stubbed for now).

### Components to Implement
1. **SubscriptionService** - Subscription management
2. **InvoiceService** - Invoice management
3. **PaymentMethodService** - Payment method management
4. **BillingService** - High-level billing operations
5. **Controllers** - API endpoints
6. **Data Classes** - Request/response DTOs

---

## 2. Services

### 2.1 SubscriptionService
**File**: `app/Services/Billing/SubscriptionService.php`

```php
final class SubscriptionService extends BaseService
{
    public function getCurrentForTenant(Tenant $tenant): ?Subscription;
    public function create(Tenant $tenant, PlanDefinition $plan, CreateSubscriptionData $data): Subscription;
    public function changePlan(Subscription $subscription, PlanDefinition $newPlan): Subscription;
    public function cancel(Subscription $subscription, bool $atPeriodEnd = true): Subscription;
    public function reactivate(Subscription $subscription): Subscription;
    public function getHistory(Tenant $tenant): Collection;
}
```

### 2.2 InvoiceService
**File**: `app/Services/Billing/InvoiceService.php`

```php
final class InvoiceService extends BaseService
{
    public function listForTenant(Tenant $tenant, array $filters = []): LengthAwarePaginator;
    public function get(string $id): Invoice;
    public function getByTenant(Tenant $tenant, string $id): Invoice;
    public function create(Subscription $subscription, CreateInvoiceData $data): Invoice;
    public function markAsPaid(Invoice $invoice, string $paymentId): Invoice;
    public function downloadUrl(Invoice $invoice): string;
}
```

### 2.3 PaymentMethodService
**File**: `app/Services/Billing/PaymentMethodService.php`

```php
final class PaymentMethodService extends BaseService
{
    public function listForTenant(Tenant $tenant): Collection;
    public function get(string $id): PaymentMethod;
    public function add(Tenant $tenant, AddPaymentMethodData $data): PaymentMethod;
    public function setDefault(PaymentMethod $method): PaymentMethod;
    public function remove(PaymentMethod $method): void;
}
```

### 2.4 BillingService
**File**: `app/Services/Billing/BillingService.php`

```php
final class BillingService extends BaseService
{
    public function getBillingSummary(Tenant $tenant): BillingSummaryData;
    public function getUsage(Tenant $tenant): UsageData;
    public function getUpgradeOptions(Tenant $tenant): Collection;
}
```

---

## 3. Data Classes

### 3.1 Billing Data
**Directory**: `app/Data/Billing/`

```php
// SubscriptionData.php
final class SubscriptionData extends Data
{
    public function __construct(
        public string $id,
        public string $tenant_id,
        public string $plan_id,
        public string $plan_name,
        public string $status,
        public string $billing_cycle,
        public string $currency,
        public string $amount,
        public ?string $current_period_start,
        public ?string $current_period_end,
        public ?string $trial_end,
        public bool $is_on_trial,
        public int $trial_days_remaining,
        public int $days_until_renewal,
        public bool $cancel_at_period_end,
        public ?string $cancelled_at,
        public string $created_at,
    ) {}

    public static function fromModel(Subscription $subscription): self;
}

// CreateSubscriptionData.php
final class CreateSubscriptionData extends Data
{
    public function __construct(
        #[Required]
        public string $plan_id,
        public BillingCycle $billing_cycle = BillingCycle::MONTHLY,
        public ?string $payment_method_id = null,
    ) {}
}

// InvoiceData.php
final class InvoiceData extends Data
{
    public function __construct(
        public string $id,
        public string $subscription_id,
        public string $invoice_number,
        public string $status,
        public string $currency,
        public string $subtotal,
        public string $tax,
        public string $total,
        public ?string $due_date,
        public ?string $paid_at,
        public ?string $razorpay_invoice_id,
        public string $created_at,
    ) {}

    public static function fromModel(Invoice $invoice): self;
}

// PaymentMethodData.php
final class PaymentMethodData extends Data
{
    public function __construct(
        public string $id,
        public string $type,
        public bool $is_default,
        public ?string $card_last_four,
        public ?string $card_brand,
        public ?string $card_exp_month,
        public ?string $card_exp_year,
        public ?string $bank_name,
        public ?string $upi_id,
        public string $created_at,
    ) {}

    public static function fromModel(PaymentMethod $method): self;
}

// AddPaymentMethodData.php
final class AddPaymentMethodData extends Data
{
    public function __construct(
        #[Required]
        public PaymentMethodType $type,
        public bool $is_default = false,
        // Card details (stubbed)
        public ?string $card_token = null,
        // UPI details
        public ?string $upi_id = null,
    ) {}
}

// BillingSummaryData.php
final class BillingSummaryData extends Data
{
    public function __construct(
        public ?SubscriptionData $current_subscription,
        public ?string $next_billing_date,
        public ?string $next_billing_amount,
        public int $total_invoices,
        public string $total_paid,
        public ?PaymentMethodData $default_payment_method,
    ) {}
}

// UsageData.php
final class UsageData extends Data
{
    public function __construct(
        public int $workspaces_used,
        public int $workspaces_limit,
        public int $social_accounts_used,
        public int $social_accounts_limit,
        public int $team_members_used,
        public int $team_members_limit,
        public int $posts_this_month,
        public ?int $posts_limit,
    ) {}
}

// ChangePlanData.php
final class ChangePlanData extends Data
{
    public function __construct(
        #[Required]
        public string $plan_id,
    ) {}
}
```

---

## 4. Controllers

### 4.1 SubscriptionController
**File**: `app/Http/Controllers/Api/V1/Billing/SubscriptionController.php`

Endpoints:
- `GET /billing/subscription` - Get current subscription
- `POST /billing/subscription` - Create subscription
- `PUT /billing/subscription/plan` - Change plan
- `POST /billing/subscription/cancel` - Cancel subscription
- `POST /billing/subscription/reactivate` - Reactivate

### 4.2 InvoiceController
**File**: `app/Http/Controllers/Api/V1/Billing/InvoiceController.php`

Endpoints:
- `GET /billing/invoices` - List invoices
- `GET /billing/invoices/{id}` - Get invoice
- `GET /billing/invoices/{id}/download` - Download invoice PDF

### 4.3 PaymentMethodController
**File**: `app/Http/Controllers/Api/V1/Billing/PaymentMethodController.php`

Endpoints:
- `GET /billing/payment-methods` - List payment methods
- `POST /billing/payment-methods` - Add payment method
- `PUT /billing/payment-methods/{id}/default` - Set as default
- `DELETE /billing/payment-methods/{id}` - Remove payment method

### 4.4 BillingController
**File**: `app/Http/Controllers/Api/V1/Billing/BillingController.php`

Endpoints:
- `GET /billing/summary` - Get billing summary
- `GET /billing/usage` - Get usage statistics
- `GET /billing/plans` - Get available plans for upgrade

---

## 5. Routes

```php
Route::middleware('auth:sanctum')->prefix('billing')->group(function () {
    // Summary and usage
    Route::get('/summary', [BillingController::class, 'summary']);
    Route::get('/usage', [BillingController::class, 'usage']);
    Route::get('/plans', [BillingController::class, 'plans']);

    // Subscription
    Route::get('/subscription', [SubscriptionController::class, 'show']);
    Route::post('/subscription', [SubscriptionController::class, 'store']);
    Route::put('/subscription/plan', [SubscriptionController::class, 'changePlan']);
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
    Route::post('/subscription/reactivate', [SubscriptionController::class, 'reactivate']);

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download']);

    // Payment methods
    Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
    Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
    Route::put('/payment-methods/{paymentMethod}/default', [PaymentMethodController::class, 'setDefault']);
    Route::delete('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy']);
});
```

---

## 6. Form Requests

**Directory**: `app/Http/Requests/Billing/`

- `CreateSubscriptionRequest.php`
- `ChangePlanRequest.php`
- `AddPaymentMethodRequest.php`

---

## 7. Test Requirements

### Feature Tests
- `tests/Feature/Api/Billing/SubscriptionTest.php`
- `tests/Feature/Api/Billing/InvoiceTest.php`
- `tests/Feature/Api/Billing/PaymentMethodTest.php`
- `tests/Feature/Api/Billing/BillingTest.php`

### Unit Tests
- `tests/Unit/Services/Billing/SubscriptionServiceTest.php`
- `tests/Unit/Services/Billing/InvoiceServiceTest.php`
- `tests/Unit/Services/Billing/PaymentMethodServiceTest.php`
- `tests/Unit/Services/Billing/BillingServiceTest.php`

---

## 8. Implementation Checklist

- [ ] Create SubscriptionService
- [ ] Create InvoiceService
- [ ] Create PaymentMethodService
- [ ] Create BillingService
- [ ] Create Billing Data classes
- [ ] Create SubscriptionController
- [ ] Create InvoiceController
- [ ] Create PaymentMethodController
- [ ] Create BillingController
- [ ] Create Form Requests
- [ ] Update routes
- [ ] Create feature tests
- [ ] Create unit tests
- [ ] All tests pass

---

## 9. Business Rules

### Permission Rules
- Only tenant Owner/Admin can manage billing
- All tenant members can view subscription status

### Subscription Rules
- Only one active subscription per tenant
- Cannot create subscription if one already exists
- Cannot cancel already cancelled subscription
- Only cancelled subscriptions with cancel_at_period_end can be reactivated

### Payment Method Rules
- At least one payment method required for subscription
- Cannot delete default payment method if it's the only one
- Setting new default removes default from previous
