# BizSocials — Razorpay Billing Integration

**Version:** 1.0
**Date:** February 2026
**Status:** Draft
**Purpose:** Complete Razorpay integration specification for domestic and international payments

---

## 1. Overview

BizSocials uses Razorpay as the sole payment gateway for handling all subscription billing, supporting both domestic (India) and international payments.

### 1.1 Razorpay Products Used

| Product | Purpose |
|---------|---------|
| **Razorpay Subscriptions** | Recurring billing management |
| **Razorpay Payments** | One-time payments, payment collection |
| **Razorpay Checkout** | Payment UI (Standard/Custom) |
| **Razorpay Invoices** | Invoice generation |
| **Razorpay Webhooks** | Real-time event notifications |

---

## 2. Payment Methods Supported

### 2.1 Domestic (India)

| Method | Type | Notes |
|--------|------|-------|
| **UPI** | Instant | GPay, PhonePe, Paytm UPI, etc. |
| **Credit Cards** | Card | Visa, Mastercard, Amex, RuPay |
| **Debit Cards** | Card | All major banks |
| **Net Banking** | Bank | 50+ banks |
| **Wallets** | Wallet | Paytm, PhonePe, Mobikwik, etc. |
| **EMI** | Credit | Card EMI on subscriptions |
| **NACH/eMandate** | Recurring | Auto-debit for subscriptions |
| **UPI AutoPay** | Recurring | UPI mandate for recurring |

### 2.2 International

| Method | Type | Notes |
|--------|------|-------|
| **International Cards** | Card | Visa, Mastercard, Amex |
| **PayPal** | Wallet | Via Razorpay integration |

---

## 3. Subscription Plans Configuration

### 3.1 Plan Structure

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                         SUBSCRIPTION PLANS                                       │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  FREE TIER                                                                      │
│  ├── Price: ₹0 / $0                                                            │
│  ├── No Razorpay subscription (internal tracking only)                         │
│  └── Limits: 1 workspace, 2 accounts, 1 user, 30 posts/month                   │
│                                                                                 │
│  STARTER                                                                        │
│  ├── Monthly: ₹999 / $15                                                       │
│  ├── Yearly: ₹9,990 / $150 (2 months free)                                     │
│  ├── Trial: 14 days                                                            │
│  └── Limits: 3 workspaces, 10 accounts, 5 users, 150 posts/month              │
│                                                                                 │
│  PROFESSIONAL                                                                   │
│  ├── Monthly: ₹2,499 / $35                                                     │
│  ├── Yearly: ₹24,990 / $350 (2 months free)                                    │
│  ├── Trial: 14 days                                                            │
│  └── Limits: 10 workspaces, 25 accounts, 15 users, unlimited posts            │
│                                                                                 │
│  BUSINESS                                                                       │
│  ├── Monthly: ₹4,999 / $75                                                     │
│  ├── Yearly: ₹49,990 / $750 (2 months free)                                    │
│  ├── Trial: 14 days                                                            │
│  └── Limits: Unlimited workspaces, 100 accounts, unlimited users              │
│                                                                                 │
│  ENTERPRISE                                                                     │
│  ├── Price: Custom                                                             │
│  ├── Contact sales                                                              │
│  └── Custom limits and features                                                │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Razorpay Plan Objects

Each plan requires two Razorpay plan objects (INR and USD):

```json
// INR Plan (Professional Monthly)
{
  "period": "monthly",
  "interval": 1,
  "item": {
    "name": "BizSocials Professional",
    "description": "Professional plan - Monthly billing",
    "amount": 249900,
    "currency": "INR"
  },
  "notes": {
    "plan_code": "professional",
    "billing_cycle": "monthly"
  }
}

// USD Plan (Professional Monthly)
{
  "period": "monthly",
  "interval": 1,
  "item": {
    "name": "BizSocials Professional",
    "description": "Professional plan - Monthly billing",
    "amount": 3500,
    "currency": "USD"
  },
  "notes": {
    "plan_code": "professional",
    "billing_cycle": "monthly"
  }
}
```

---

## 4. Subscription Lifecycle

### 4.1 Subscription States

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                        SUBSCRIPTION LIFECYCLE                                    │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  ┌─────────┐     ┌──────────────┐     ┌────────┐                              │
│  │ CREATED │────▶│ AUTHENTICATED│────▶│ ACTIVE │                              │
│  └─────────┘     └──────────────┘     └────┬───┘                              │
│       │                                     │                                   │
│       │                                     │ Payment Failed                    │
│       ▼                                     ▼                                   │
│  ┌─────────┐                          ┌─────────┐                              │
│  │ EXPIRED │                          │ PENDING │                              │
│  └─────────┘                          └────┬────┘                              │
│                                            │                                    │
│                                            │ Retry Failed (3x)                  │
│                                            ▼                                    │
│                                       ┌─────────┐                              │
│                                       │ HALTED  │                              │
│                                       └────┬────┘                              │
│                                            │                                    │
│        User Cancels ────────────────────────┤                                   │
│                                            │                                    │
│                                            ▼                                    │
│                                      ┌───────────┐                             │
│                                      │ CANCELLED │                             │
│                                      └───────────┘                             │
│                                                                                 │
│  STATES:                                                                        │
│  • CREATED: Subscription created, awaiting payment                             │
│  • AUTHENTICATED: Payment method verified                                      │
│  • ACTIVE: Subscription active and paid                                        │
│  • PENDING: Payment due/processing                                             │
│  • HALTED: Payment failed after retries                                        │
│  • CANCELLED: User cancelled or terminated                                     │
│  • COMPLETED: Fixed-term subscription ended                                    │
│  • EXPIRED: Subscription expired without renewal                               │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

### 4.2 State Mapping

| Razorpay Status | BizSocials Action |
|-----------------|-------------------|
| `created` | Show payment pending |
| `authenticated` | Start trial if applicable |
| `active` | Full access granted |
| `pending` | Show payment reminder |
| `halted` | Grace period, limited access |
| `cancelled` | Access until period end |
| `completed` | Downgrade to free |
| `expired` | Downgrade to free |

---

## 5. Integration Architecture

### 5.1 Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                       PAYMENT FLOW ARCHITECTURE                                  │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  SUBSCRIPTION CREATION FLOW                                                     │
│                                                                                 │
│  ┌──────────┐    ┌───────────┐    ┌──────────┐    ┌───────────┐               │
│  │  Tenant  │───▶│ BizSocials│───▶│ Razorpay │───▶│  Payment  │               │
│  │  (User)  │    │  Backend  │    │   API    │    │  Gateway  │               │
│  └──────────┘    └───────────┘    └──────────┘    └───────────┘               │
│       │               │                 │               │                       │
│       │  1. Select    │                 │               │                       │
│       │     Plan      │                 │               │                       │
│       │──────────────▶│                 │               │                       │
│       │               │  2. Create      │               │                       │
│       │               │  Subscription   │               │                       │
│       │               │────────────────▶│               │                       │
│       │               │                 │               │                       │
│       │               │  3. Sub ID +    │               │                       │
│       │               │     Short URL   │               │                       │
│       │               │◀────────────────│               │                       │
│       │               │                 │               │                       │
│       │  4. Redirect  │                 │               │                       │
│       │     to Pay    │                 │               │                       │
│       │◀──────────────│                 │               │                       │
│       │               │                 │               │                       │
│       │  5. Complete  │                 │               │                       │
│       │     Payment   │                 │               │                       │
│       │─────────────────────────────────────────────────▶│                       │
│       │               │                 │               │                       │
│       │               │  6. Webhook:    │               │                       │
│       │               │  subscription   │               │                       │
│       │               │  .authenticated │               │                       │
│       │               │◀────────────────│               │                       │
│       │               │                 │               │                       │
│       │  7. Success   │                 │               │                       │
│       │     Page      │                 │               │                       │
│       │◀──────────────│                 │               │                       │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

### 5.2 API Integration

```php
// Service: RazorpayService.php

class RazorpayService
{
    private Api $razorpay;

    public function __construct()
    {
        $this->razorpay = new Api(
            config('services.razorpay.key_id'),
            config('services.razorpay.key_secret')
        );
    }

    /**
     * Create a subscription for a tenant
     */
    public function createSubscription(
        Tenant $tenant,
        PlanDefinition $plan,
        string $billingCycle,
        string $currency
    ): array {
        // Get or create Razorpay customer
        $customerId = $this->getOrCreateCustomer($tenant);

        // Get Razorpay plan ID
        $planId = $currency === 'INR'
            ? $plan->razorpay_plan_id_inr
            : $plan->razorpay_plan_id_usd;

        // Create subscription
        $subscription = $this->razorpay->subscription->create([
            'plan_id' => $planId,
            'customer_id' => $customerId,
            'total_count' => 120, // Max billing cycles
            'quantity' => 1,
            'customer_notify' => 1,
            'notes' => [
                'tenant_id' => $tenant->id,
                'plan_code' => $plan->code,
                'billing_cycle' => $billingCycle,
            ],
        ]);

        return [
            'subscription_id' => $subscription->id,
            'short_url' => $subscription->short_url,
            'status' => $subscription->status,
        ];
    }

    /**
     * Create subscription with trial
     */
    public function createSubscriptionWithTrial(
        Tenant $tenant,
        PlanDefinition $plan,
        string $billingCycle,
        string $currency,
        int $trialDays
    ): array {
        $customerId = $this->getOrCreateCustomer($tenant);

        $planId = $currency === 'INR'
            ? $plan->razorpay_plan_id_inr
            : $plan->razorpay_plan_id_usd;

        // Calculate trial end timestamp
        $trialEnd = now()->addDays($trialDays)->timestamp;

        $subscription = $this->razorpay->subscription->create([
            'plan_id' => $planId,
            'customer_id' => $customerId,
            'total_count' => 120,
            'quantity' => 1,
            'customer_notify' => 1,
            'start_at' => $trialEnd, // Billing starts after trial
            'notes' => [
                'tenant_id' => $tenant->id,
                'plan_code' => $plan->code,
                'billing_cycle' => $billingCycle,
                'trial_days' => $trialDays,
            ],
        ]);

        return [
            'subscription_id' => $subscription->id,
            'short_url' => $subscription->short_url,
            'status' => $subscription->status,
            'trial_end' => $trialEnd,
        ];
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(
        string $subscriptionId,
        bool $cancelAtPeriodEnd = true
    ): array {
        $subscription = $this->razorpay->subscription->fetch($subscriptionId);

        if ($cancelAtPeriodEnd) {
            // Cancel at end of current period
            $subscription->cancel(['cancel_at_cycle_end' => 1]);
        } else {
            // Immediate cancellation
            $subscription->cancel();
        }

        return [
            'status' => $subscription->status,
            'ended_at' => $subscription->ended_at,
        ];
    }

    /**
     * Change subscription plan
     */
    public function changePlan(
        string $subscriptionId,
        PlanDefinition $newPlan,
        string $currency
    ): array {
        $planId = $currency === 'INR'
            ? $newPlan->razorpay_plan_id_inr
            : $newPlan->razorpay_plan_id_usd;

        $subscription = $this->razorpay->subscription->fetch($subscriptionId);

        // Update subscription plan
        $subscription->update([
            'plan_id' => $planId,
            'schedule_change_at' => 'cycle_end', // or 'now' for immediate
        ]);

        return [
            'status' => $subscription->status,
            'plan_id' => $planId,
        ];
    }

    /**
     * Get or create Razorpay customer
     */
    private function getOrCreateCustomer(Tenant $tenant): string
    {
        // Check if customer already exists
        if ($tenant->razorpay_customer_id) {
            return $tenant->razorpay_customer_id;
        }

        $owner = $tenant->owner;
        $profile = $tenant->profile;

        $customer = $this->razorpay->customer->create([
            'name' => $owner->name,
            'email' => $owner->email,
            'contact' => $profile->phone ?? null,
            'gstin' => $profile->gstin ?? null,
            'notes' => [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
            ],
        ]);

        // Save customer ID
        $tenant->update(['razorpay_customer_id' => $customer->id]);

        return $customer->id;
    }
}
```

---

## 6. Webhook Handling

### 6.1 Webhook Events

| Event | Action |
|-------|--------|
| `subscription.authenticated` | Start trial/subscription |
| `subscription.activated` | Confirm active subscription |
| `subscription.charged` | Record payment, extend period |
| `subscription.pending` | Send payment reminder |
| `subscription.halted` | Start grace period |
| `subscription.cancelled` | Mark for end-of-period cancellation |
| `subscription.completed` | Downgrade to free |
| `subscription.updated` | Sync plan changes |
| `payment.authorized` | Log authorization |
| `payment.captured` | Confirm payment |
| `payment.failed` | Log failure, trigger retry |
| `refund.created` | Process refund |
| `invoice.generated` | Store invoice |
| `invoice.paid` | Mark invoice paid |

### 6.2 Webhook Handler

```php
// Controller: RazorpayWebhookController.php

class RazorpayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify webhook signature
        $webhookSecret = config('services.razorpay.webhook_secret');
        $webhookSignature = $request->header('X-Razorpay-Signature');
        $webhookBody = $request->getContent();

        try {
            Api::verifyWebhookSignature(
                $webhookBody,
                $webhookSignature,
                $webhookSecret
            );
        } catch (SignatureVerificationError $e) {
            Log::error('Razorpay webhook signature verification failed');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $payload = $request->all();
        $event = $payload['event'];
        $entity = $payload['payload']['subscription']['entity']
            ?? $payload['payload']['payment']['entity']
            ?? null;

        // Dispatch to appropriate handler
        match ($event) {
            'subscription.authenticated' => $this->handleSubscriptionAuthenticated($entity),
            'subscription.activated' => $this->handleSubscriptionActivated($entity),
            'subscription.charged' => $this->handleSubscriptionCharged($entity, $payload),
            'subscription.pending' => $this->handleSubscriptionPending($entity),
            'subscription.halted' => $this->handleSubscriptionHalted($entity),
            'subscription.cancelled' => $this->handleSubscriptionCancelled($entity),
            'subscription.completed' => $this->handleSubscriptionCompleted($entity),
            'payment.captured' => $this->handlePaymentCaptured($entity),
            'payment.failed' => $this->handlePaymentFailed($entity),
            'refund.created' => $this->handleRefundCreated($entity),
            default => Log::info("Unhandled Razorpay event: {$event}"),
        };

        return response()->json(['status' => 'ok']);
    }

    private function handleSubscriptionAuthenticated(array $subscription): void
    {
        $tenantId = $subscription['notes']['tenant_id'] ?? null;
        if (!$tenantId) return;

        $tenant = Tenant::find($tenantId);
        if (!$tenant) return;

        // Update subscription status
        $tenant->subscription()->updateOrCreate(
            ['razorpay_subscription_id' => $subscription['id']],
            [
                'status' => 'authenticated',
                'current_period_start' => Carbon::createFromTimestamp($subscription['current_start']),
                'current_period_end' => Carbon::createFromTimestamp($subscription['current_end']),
            ]
        );

        // Activate tenant if in trial
        if ($tenant->status === 'pending') {
            $tenant->update(['status' => 'active']);
        }

        // Send confirmation email
        Mail::to($tenant->owner)->send(new SubscriptionConfirmed($tenant));
    }

    private function handleSubscriptionCharged(array $subscription, array $payload): void
    {
        $tenantId = $subscription['notes']['tenant_id'] ?? null;
        if (!$tenantId) return;

        $tenant = Tenant::find($tenantId);
        if (!$tenant) return;

        $payment = $payload['payload']['payment']['entity'];

        // Record payment
        Payment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $tenant->subscription->id,
            'razorpay_payment_id' => $payment['id'],
            'status' => 'captured',
            'amount' => $payment['amount'] / 100,
            'currency' => $payment['currency'],
            'method' => $payment['method'],
            'captured_at' => now(),
        ]);

        // Update subscription period
        $tenant->subscription->update([
            'status' => 'active',
            'current_period_start' => Carbon::createFromTimestamp($subscription['current_start']),
            'current_period_end' => Carbon::createFromTimestamp($subscription['current_end']),
        ]);

        // Generate invoice
        $this->generateInvoice($tenant, $payment);
    }

    private function handleSubscriptionHalted(array $subscription): void
    {
        $tenantId = $subscription['notes']['tenant_id'] ?? null;
        if (!$tenantId) return;

        $tenant = Tenant::find($tenantId);
        if (!$tenant) return;

        // Update status
        $tenant->subscription->update(['status' => 'halted']);

        // Start grace period (7 days)
        $tenant->update([
            'status' => 'suspended',
            'settings->grace_period_ends' => now()->addDays(7),
        ]);

        // Send dunning email
        Mail::to($tenant->owner)->send(new PaymentFailed($tenant));
    }

    // ... other handlers
}
```

---

## 7. GST & Tax Handling

### 7.1 GST Calculation (India)

```php
class GSTCalculator
{
    const GST_RATE = 18; // 18% GST
    const HSN_SAC_CODE = '998314'; // IT Services

    public function calculate(float $baseAmount, string $customerState): array
    {
        $gstAmount = $baseAmount * (self::GST_RATE / 100);
        $totalAmount = $baseAmount + $gstAmount;

        // Determine CGST/SGST vs IGST based on state
        $bizinsoState = 'Maharashtra'; // Company registered state

        if (strtolower($customerState) === strtolower($bizinsoState)) {
            // Same state: CGST + SGST (9% each)
            return [
                'base_amount' => $baseAmount,
                'cgst_rate' => 9,
                'cgst_amount' => $gstAmount / 2,
                'sgst_rate' => 9,
                'sgst_amount' => $gstAmount / 2,
                'igst_rate' => 0,
                'igst_amount' => 0,
                'total_gst' => $gstAmount,
                'total_amount' => $totalAmount,
                'hsn_sac' => self::HSN_SAC_CODE,
            ];
        } else {
            // Different state: IGST (18%)
            return [
                'base_amount' => $baseAmount,
                'cgst_rate' => 0,
                'cgst_amount' => 0,
                'sgst_rate' => 0,
                'sgst_amount' => 0,
                'igst_rate' => 18,
                'igst_amount' => $gstAmount,
                'total_gst' => $gstAmount,
                'total_amount' => $totalAmount,
                'hsn_sac' => self::HSN_SAC_CODE,
            ];
        }
    }
}
```

### 7.2 Invoice Generation

```php
class InvoiceGenerator
{
    public function generate(Tenant $tenant, Payment $payment): Invoice
    {
        $profile = $tenant->profile;
        $gst = app(GSTCalculator::class)->calculate(
            $payment->amount,
            $profile->state
        );

        // Generate invoice number: BIZ/2026-27/00001
        $invoiceNumber = $this->generateInvoiceNumber();

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $tenant->subscription->id,
            'invoice_number' => $invoiceNumber,
            'status' => 'paid',
            'currency' => $payment->currency,
            'subtotal' => $gst['base_amount'],
            'tax_amount' => $gst['total_gst'],
            'total' => $gst['total_amount'],
            'amount_paid' => $gst['total_amount'],
            'amount_due' => 0,
            'gst_details' => [
                'gstin' => $profile->gstin,
                'place_of_supply' => $profile->state,
                'cgst' => $gst['cgst_amount'],
                'sgst' => $gst['sgst_amount'],
                'igst' => $gst['igst_amount'],
                'hsn_sac' => $gst['hsn_sac'],
            ],
            'billing_address' => [
                'name' => $tenant->name,
                'address' => $profile->address_line1,
                'city' => $profile->city,
                'state' => $profile->state,
                'country' => $profile->country,
                'postal_code' => $profile->postal_code,
                'gstin' => $profile->gstin,
            ],
            'line_items' => [
                [
                    'description' => "{$tenant->subscription->plan->name} - {$tenant->subscription->billing_cycle}",
                    'hsn_sac' => $gst['hsn_sac'],
                    'quantity' => 1,
                    'unit_price' => $gst['base_amount'],
                    'amount' => $gst['base_amount'],
                ],
            ],
            'issued_at' => now(),
            'paid_at' => now(),
        ]);

        // Generate PDF
        $pdfUrl = $this->generatePDF($invoice);
        $invoice->update(['pdf_url' => $pdfUrl]);

        // Send invoice email
        Mail::to($tenant->owner)->send(new InvoiceMail($invoice));

        return $invoice;
    }
}
```

---

## 8. Payment UI Integration

### 8.1 Razorpay Checkout (Frontend)

```javascript
// PaymentCheckout.vue

<template>
  <div class="payment-checkout">
    <div class="plan-summary">
      <h3>{{ plan.name }}</h3>
      <div class="price">
        <span class="amount">{{ formatCurrency(totalAmount) }}</span>
        <span class="period">/{{ billingCycle }}</span>
      </div>
      <div class="breakdown" v-if="currency === 'INR'">
        <div>Base: {{ formatCurrency(baseAmount) }}</div>
        <div>GST (18%): {{ formatCurrency(gstAmount) }}</div>
        <div class="total">Total: {{ formatCurrency(totalAmount) }}</div>
      </div>
    </div>

    <button
      @click="initiatePayment"
      :disabled="loading"
      class="pay-button"
    >
      {{ loading ? 'Processing...' : `Pay ${formatCurrency(totalAmount)}` }}
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps({
  plan: Object,
  billingCycle: String,
  currency: String,
})

const router = useRouter()
const loading = ref(false)

const baseAmount = computed(() => {
  return props.billingCycle === 'yearly'
    ? props.plan[`price_${props.currency.toLowerCase()}_yearly`]
    : props.plan[`price_${props.currency.toLowerCase()}_monthly`]
})

const gstAmount = computed(() => {
  if (props.currency !== 'INR') return 0
  return baseAmount.value * 0.18
})

const totalAmount = computed(() => {
  return baseAmount.value + gstAmount.value
})

async function initiatePayment() {
  loading.value = true

  try {
    // Create subscription on backend
    const response = await fetch('/api/v1/subscriptions', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getToken()}`,
      },
      body: JSON.stringify({
        plan_id: props.plan.id,
        billing_cycle: props.billingCycle,
        currency: props.currency,
      }),
    })

    const data = await response.json()

    // Open Razorpay checkout
    const options = {
      key: import.meta.env.VITE_RAZORPAY_KEY_ID,
      subscription_id: data.razorpay_subscription_id,
      name: 'BizSocials',
      description: `${props.plan.name} - ${props.billingCycle}`,
      image: '/logo.png',
      handler: function(response) {
        // Payment successful
        handlePaymentSuccess(response)
      },
      prefill: {
        name: data.customer_name,
        email: data.customer_email,
        contact: data.customer_phone,
      },
      notes: {
        tenant_id: data.tenant_id,
      },
      theme: {
        color: '#3B82F6',
      },
      modal: {
        ondismiss: function() {
          loading.value = false
        },
      },
    }

    const rzp = new window.Razorpay(options)
    rzp.open()

  } catch (error) {
    console.error('Payment initiation failed:', error)
    loading.value = false
  }
}

async function handlePaymentSuccess(response) {
  // Verify payment on backend
  await fetch('/api/v1/subscriptions/verify', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${getToken()}`,
    },
    body: JSON.stringify({
      razorpay_payment_id: response.razorpay_payment_id,
      razorpay_subscription_id: response.razorpay_subscription_id,
      razorpay_signature: response.razorpay_signature,
    }),
  })

  // Redirect to success page
  router.push('/subscription/success')
}
</script>
```

---

## 9. Billing Portal

### 9.1 Tenant Billing Page

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│  BILLING & SUBSCRIPTION                                                         │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  CURRENT PLAN                                                                   │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │  Professional Plan                                     [Change Plan]     │   │
│  │  ₹2,499/month (billed monthly)                                          │   │
│  │                                                                          │   │
│  │  Next billing date: March 15, 2026                                      │   │
│  │  Payment method: Visa ending in 4242                   [Update]          │   │
│  │                                                                          │   │
│  │  [Cancel Subscription]                                                   │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
│  USAGE                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │  Workspaces:      3 / 10                           ████████░░░░ 30%     │   │
│  │  Social Accounts: 12 / 25                          ██████████░░ 48%     │   │
│  │  Team Members:    8 / 15                           █████████░░░ 53%     │   │
│  │  Posts (month):   156 / ∞                          Unlimited             │   │
│  │  Storage:         2.3 GB / 10 GB                   ████░░░░░░░░ 23%     │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
│  BILLING HISTORY                                                                │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │  Date        │ Description              │ Amount    │ Status  │ Invoice │   │
│  ├─────────────────────────────────────────────────────────────────────────┤   │
│  │  Feb 15, 26  │ Professional - Monthly   │ ₹2,948.82│ Paid    │ [PDF]   │   │
│  │  Jan 15, 26  │ Professional - Monthly   │ ₹2,948.82│ Paid    │ [PDF]   │   │
│  │  Dec 15, 25  │ Professional - Monthly   │ ₹2,948.82│ Paid    │ [PDF]   │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
│  BILLING DETAILS                                                                │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │  Company: Acme Agency Pvt Ltd                        [Edit]              │   │
│  │  GSTIN: 27AABCU9603R1ZM                                                 │   │
│  │  Address: 123 Business Park, Mumbai, MH 400001                          │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 10. Error Handling & Recovery

### 10.1 Payment Failure Handling

| Failure Type | Retry | User Action | Grace Period |
|--------------|-------|-------------|--------------|
| Card declined | 3x over 5 days | Update card | 7 days |
| Insufficient funds | 3x over 5 days | Add funds | 7 days |
| Card expired | No auto-retry | Update card | 7 days |
| Bank error | 3x over 5 days | Try again | 7 days |
| UPI timeout | 1x immediately | Try again | None |

### 10.2 Dunning Emails

| Day | Email | Subject |
|-----|-------|---------|
| 0 | Payment Failed | Your payment failed - action required |
| 3 | Reminder | Update your payment method |
| 5 | Urgent | Your account will be suspended |
| 7 | Final | Your account has been suspended |

---

## 11. Refunds

### 11.1 Refund Policy

| Scenario | Refund Amount | Timeline |
|----------|---------------|----------|
| Within 7 days of signup | Full refund | 5-7 business days |
| Cancel mid-cycle | No refund (access until period end) | N/A |
| Service issue | Prorated refund | Case by case |
| Duplicate payment | Full refund | Immediate |

### 11.2 Refund Processing

```php
class RefundService
{
    public function processRefund(Payment $payment, float $amount, string $reason): Refund
    {
        $razorpay = app(RazorpayService::class);

        // Create refund in Razorpay
        $refund = $razorpay->createRefund(
            $payment->razorpay_payment_id,
            $amount * 100, // Amount in paise
            [
                'reason' => $reason,
                'tenant_id' => $payment->tenant_id,
            ]
        );

        // Record refund
        return Refund::create([
            'payment_id' => $payment->id,
            'tenant_id' => $payment->tenant_id,
            'razorpay_refund_id' => $refund['id'],
            'amount' => $amount,
            'reason' => $reason,
            'status' => $refund['status'],
        ]);
    }
}
```

---

## 12. Configuration

### 12.1 Environment Variables

```env
# Razorpay Configuration
RAZORPAY_KEY_ID=rzp_live_xxxxxxxxxx
RAZORPAY_KEY_SECRET=xxxxxxxxxxxxxxxxxx
RAZORPAY_WEBHOOK_SECRET=xxxxxxxxxxxxxxxxxx

# For test mode
RAZORPAY_TEST_KEY_ID=rzp_test_xxxxxxxxxx
RAZORPAY_TEST_KEY_SECRET=xxxxxxxxxxxxxxxxxx
```

### 12.2 Config File

```php
// config/services.php

return [
    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],
];
```

---

**Document Version:** 1.0
**Last Updated:** February 2026
**Status:** Draft
