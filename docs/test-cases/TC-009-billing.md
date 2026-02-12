# TC-009: Billing & Subscription Test Cases

**Feature:** Stripe Billing Integration
**Priority:** Critical
**Related Docs:** [API Contract - Billing](../04_phase1_api_contract.md)

---

## Overview

Tests for subscription management, payment processing, plan limits, and billing lifecycle. Uses Stripe in test mode.

---

## Test Environment Setup

```
PLANS
├── FREE: 0/month, 1 workspace, 3 social accounts
├── STARTER: $19/month, 3 workspaces, 10 social accounts
├── PROFESSIONAL: $49/month, 10 workspaces, 25 social accounts
└── BUSINESS: $99/month, unlimited workspaces, 100 social accounts

WORKSPACE A (Starter Plan)
├── Owner: alice@acme.test
├── Subscription: sub_active_123 (ACTIVE)
├── Current Period: Feb 1 - Mar 1
└── Payment Method: card_4242 (Visa)

WORKSPACE B (Free Plan)
├── Owner: bob@beta.test
└── No subscription (free tier)

WORKSPACE C (Cancelled)
├── Owner: charlie@test.com
├── Subscription: sub_cancelled (CANCELLED)
└── Cancel at period end: Mar 1

STRIPE TEST CARDS
├── 4242424242424242: Success
├── 4000000000000002: Decline
├── 4000000000009995: Insufficient funds
└── 4000000000000341: Attach fails
```

---

## Unit Tests (Codex to implement)

### UT-009-001: Plan limits accessor
- **File:** `tests/Unit/Models/WorkspaceTest.php`
- **Description:** Verify plan limits are correctly retrieved
- **Test Pattern:**
```php
public function test_starter_plan_limits(): void
{
    $workspace = Workspace::factory()->create(['plan' => 'STARTER']);
    $limits = $workspace->planLimits;

    $this->assertEquals(3, $limits['max_workspaces']);
    $this->assertEquals(10, $limits['max_social_accounts']);
    $this->assertEquals(5, $limits['max_team_members']);
}
```
- **Status:** [ ] Pending

### UT-009-002: Plan limit enforcement - social accounts
- **File:** `tests/Unit/Services/PlanLimitServiceTest.php`
- **Description:** Verify cannot exceed social account limit
- **Status:** [ ] Pending

### UT-009-003: Plan limit enforcement - team members
- **File:** `tests/Unit/Services/PlanLimitServiceTest.php`
- **Description:** Verify cannot exceed team member limit
- **Status:** [ ] Pending

### UT-009-004: Subscription status mapping
- **File:** `tests/Unit/Models/SubscriptionTest.php`
- **Description:** Verify Stripe status maps to internal status
- **Status:** [ ] Pending

### UT-009-005: Trial period calculation
- **File:** `tests/Unit/Services/SubscriptionServiceTest.php`
- **Description:** Verify trial end date calculation
- **Status:** [ ] Pending

### UT-009-006: Proration calculation
- **File:** `tests/Unit/Services/SubscriptionServiceTest.php`
- **Description:** Verify proration on plan upgrade
- **Status:** [ ] Pending

### UT-009-007: Grace period enforcement
- **File:** `tests/Unit/Services/SubscriptionServiceTest.php`
- **Description:** Verify grace period after failed payment
- **Status:** [ ] Pending

### UT-009-008: Webhook signature validation
- **File:** `tests/Unit/Services/StripeWebhookServiceTest.php`
- **Description:** Verify Stripe webhook signature is validated
- **Status:** [ ] Pending

---

## Integration Tests (Codex to implement)

### IT-009-001: Get current subscription
- **File:** `tests/Feature/Api/V1/Billing/GetSubscriptionTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/subscription`
- **Expected:** 200 OK, subscription details
- **Status:** [ ] Pending

### IT-009-002: Get subscription - free workspace
- **File:** `tests/Feature/Api/V1/Billing/GetSubscriptionTest.php`
- **Setup:** Workspace on free plan
- **Expected:** 200 OK, `{ "plan": "FREE", "subscription": null }`
- **Status:** [ ] Pending

### IT-009-003: List available plans
- **File:** `tests/Feature/Api/V1/Billing/PlansTest.php`
- **Endpoint:** `GET /v1/plans`
- **Expected:** 200 OK, all plan details with pricing
- **Status:** [ ] Pending

### IT-009-004: Create subscription - Stripe checkout
- **File:** `tests/Feature/Api/V1/Billing/CreateSubscriptionTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/subscription`
- **Request:** `{ "plan": "STARTER" }`
- **Expected:** 200 OK, Stripe checkout session URL
- **Status:** [ ] Pending

### IT-009-005: Create subscription - non-owner forbidden
- **File:** `tests/Feature/Api/V1/Billing/CreateSubscriptionTest.php`
- **Setup:** Admin tries to subscribe
- **Expected:** 403 Forbidden (only owner can manage billing)
- **Status:** [ ] Pending

### IT-009-006: Update subscription - upgrade plan
- **File:** `tests/Feature/Api/V1/Billing/UpdateSubscriptionTest.php`
- **Endpoint:** `PATCH /v1/workspaces/{workspace_id}/subscription`
- **Request:** `{ "plan": "PROFESSIONAL" }`
- **Expected:** 200 OK, plan upgraded with proration
- **Status:** [ ] Pending

### IT-009-007: Update subscription - downgrade plan
- **File:** `tests/Feature/Api/V1/Billing/UpdateSubscriptionTest.php`
- **Request:** Downgrade from Professional to Starter
- **Expected:** 200 OK, scheduled for next billing cycle
- **Status:** [ ] Pending

### IT-009-008: Cancel subscription
- **File:** `tests/Feature/Api/V1/Billing/CancelSubscriptionTest.php`
- **Endpoint:** `DELETE /v1/workspaces/{workspace_id}/subscription`
- **Expected:** 200 OK, cancellation scheduled at period end
- **Status:** [ ] Pending

### IT-009-009: Cancel subscription - immediate
- **File:** `tests/Feature/Api/V1/Billing/CancelSubscriptionTest.php`
- **Request:** `{ "immediate": true }`
- **Expected:** 200 OK, immediate cancellation
- **Status:** [ ] Pending

### IT-009-010: Reactivate cancelled subscription
- **File:** `tests/Feature/Api/V1/Billing/ReactivateSubscriptionTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/subscription/reactivate`
- **Setup:** Subscription cancelled but not ended
- **Expected:** 200 OK, cancellation reversed
- **Status:** [ ] Pending

### IT-009-011: Get billing history
- **File:** `tests/Feature/Api/V1/Billing/BillingHistoryTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/billing/invoices`
- **Expected:** 200 OK, list of invoices
- **Status:** [ ] Pending

### IT-009-012: Get invoice PDF
- **File:** `tests/Feature/Api/V1/Billing/InvoiceTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/billing/invoices/{invoice_id}/pdf`
- **Expected:** 302 Redirect to Stripe hosted invoice PDF
- **Status:** [ ] Pending

### IT-009-013: Update payment method
- **File:** `tests/Feature/Api/V1/Billing/PaymentMethodTest.php`
- **Endpoint:** `POST /v1/workspaces/{workspace_id}/billing/payment-method`
- **Expected:** 200 OK, Stripe setup intent
- **Status:** [ ] Pending

### IT-009-014: Get billing portal URL
- **File:** `tests/Feature/Api/V1/Billing/PortalTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/billing/portal`
- **Expected:** 200 OK, Stripe billing portal URL
- **Status:** [ ] Pending

### IT-009-015: Check plan limits
- **File:** `tests/Feature/Api/V1/Billing/LimitsTest.php`
- **Endpoint:** `GET /v1/workspaces/{workspace_id}/limits`
- **Expected:** 200 OK, current usage vs limits
- **Status:** [ ] Pending

---

## Webhook Tests (Codex to implement)

### WH-009-001: Webhook - invoice.paid
- **File:** `tests/Feature/Webhooks/StripeWebhookTest.php`
- **Event:** `invoice.paid`
- **Expected:** Subscription marked as paid, period extended
- **Status:** [ ] Pending

### WH-009-002: Webhook - invoice.payment_failed
- **File:** `tests/Feature/Webhooks/StripeWebhookTest.php`
- **Event:** `invoice.payment_failed`
- **Expected:** Grace period started, notification sent
- **Status:** [ ] Pending

### WH-009-003: Webhook - customer.subscription.updated
- **File:** `tests/Feature/Webhooks/StripeWebhookTest.php`
- **Event:** `customer.subscription.updated`
- **Expected:** Local subscription record updated
- **Status:** [ ] Pending

### WH-009-004: Webhook - customer.subscription.deleted
- **File:** `tests/Feature/Webhooks/StripeWebhookTest.php`
- **Event:** `customer.subscription.deleted`
- **Expected:** Workspace downgraded to free
- **Status:** [ ] Pending

### WH-009-005: Webhook - checkout.session.completed
- **File:** `tests/Feature/Webhooks/StripeWebhookTest.php`
- **Event:** `checkout.session.completed`
- **Expected:** Subscription activated
- **Status:** [ ] Pending

### WH-009-006: Webhook - invalid signature
- **File:** `tests/Feature/Webhooks/StripeWebhookTest.php`
- **Setup:** Webhook with invalid signature
- **Expected:** 400 Bad Request
- **Status:** [ ] Pending

---

## E2E Tests (Codex to implement)

### E2E-009-001: Complete subscription flow
- **File:** `tests/e2e/billing/subscribe.spec.ts`
- **Steps:**
  1. Login as workspace owner (free plan)
  2. Navigate to Billing
  3. Click Upgrade to Starter
  4. Complete Stripe checkout (test card)
  5. Verify redirect to success page
  6. Verify plan updated
- **Status:** [ ] Pending

### E2E-009-002: Cancel subscription flow
- **File:** `tests/e2e/billing/cancel.spec.ts`
- **Steps:**
  1. Login as subscribed workspace owner
  2. Navigate to Billing
  3. Click Cancel Subscription
  4. Confirm cancellation
  5. Verify cancellation date shown
- **Status:** [ ] Pending

### E2E-009-003: Plan limit enforcement UI
- **File:** `tests/e2e/billing/limits.spec.ts`
- **Steps:**
  1. Login to workspace at social account limit
  2. Try to connect new account
  3. Verify upgrade prompt shown
  4. Verify blocked from connecting
- **Status:** [ ] Pending

---

## Manual Tests (Claude to execute)

### MT-009-001: Real Stripe checkout
- **Steps:**
  1. Use Stripe test mode
  2. Complete checkout with test card 4242...
  3. Verify subscription created in Stripe dashboard
  4. Verify webhook received
  5. Verify local status updated
- **Status:** [ ] Not tested

### MT-009-002: Failed payment recovery
- **Steps:**
  1. Create subscription with test card
  2. Trigger payment failure (via Stripe CLI)
  3. Verify grace period notification
  4. Update payment method
  5. Verify subscription recovered
- **Status:** [ ] Not tested

### MT-009-003: Plan downgrade data handling
- **Steps:**
  1. Workspace on Professional (25 accounts connected)
  2. Downgrade to Starter (10 limit)
  3. Verify warning shown
  4. Verify excess accounts marked for disconnection
- **Status:** [ ] Not tested

### MT-009-004: Invoice email delivery
- **Steps:**
  1. Trigger invoice generation
  2. Verify email received
  3. Verify invoice link works
  4. Verify PDF downloads correctly
- **Status:** [ ] Not tested

### MT-009-005: Billing portal functionality
- **Steps:**
  1. Access Stripe billing portal
  2. Update payment method
  3. View invoice history
  4. Cancel subscription
  5. Verify changes sync back
- **Status:** [ ] Not tested

---

## Security Tests (Claude to verify)

### ST-009-001: Billing access - owner only
- **Attack:** Non-owner tries to access billing
- **Expected:** 403 Forbidden
- **Status:** [ ] Not tested

### ST-009-002: Webhook replay attack
- **Attack:** Replay old webhook event
- **Expected:** Rejected by idempotency check
- **Status:** [ ] Not tested

### ST-009-003: Subscription manipulation
- **Attack:** Modify subscription ID in request
- **Expected:** Validated against Stripe
- **Status:** [ ] Not tested

### ST-009-004: Payment data not stored
- **Verify:** Card numbers never stored in database
- **Expected:** Only Stripe tokens stored
- **Status:** [ ] Not tested

### ST-009-005: Cross-workspace billing access
- **Attack:** Access Workspace B billing from Workspace A
- **Expected:** 403 Forbidden
- **Status:** [ ] Not tested

---

## Test Results Summary

| Category | Total | Passed | Failed | Pending |
|----------|:-----:|:------:|:------:|:-------:|
| Unit | 8 | - | - | 8 |
| Integration | 15 | - | - | 15 |
| Webhook Tests | 6 | - | - | 6 |
| E2E | 3 | - | - | 3 |
| Manual | 5 | - | - | 5 |
| Security | 5 | - | - | 5 |
| **Total** | **42** | **-** | **-** | **42** |

---

**Last Updated:** February 2026
**Status:** Draft
