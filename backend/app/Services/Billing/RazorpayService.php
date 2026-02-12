<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use App\Services\BaseService;
use Razorpay\Api\Api;

class RazorpayService extends BaseService
{
    private Api $api;

    public function __construct()
    {
        $this->api = new Api(
            config('services.razorpay.key_id'),
            config('services.razorpay.key_secret'),
        );
    }

    /**
     * Create or retrieve a Razorpay customer for a tenant.
     */
    public function createCustomer(Tenant $tenant): string
    {
        // Check if tenant already has a Razorpay customer ID
        $existingCustomerId = $tenant->getSetting('razorpay_customer_id');
        if ($existingCustomerId) {
            return $existingCustomerId;
        }

        $owner = $tenant->users()->where('role_in_tenant', 'owner')->first();

        $customer = $this->api->customer->create([
            'name' => $tenant->name,
            'email' => $owner?->email ?? '',
            'notes' => [
                'tenant_id' => $tenant->id,
            ],
        ]);

        $tenant->setSetting('razorpay_customer_id', $customer->id);

        $this->log('Razorpay customer created', [
            'tenant_id' => $tenant->id,
            'razorpay_customer_id' => $customer->id,
        ]);

        return $customer->id;
    }

    /**
     * Create a Razorpay subscription.
     */
    public function createSubscription(string $customerId, string $planId, ?int $trialDays = null): object
    {
        $params = [
            'plan_id' => $planId,
            'customer_id' => $customerId,
            'total_count' => 120, // Max billing cycles
            'customer_notify' => 1,
        ];

        if ($trialDays && $trialDays > 0) {
            $params['start_at'] = now()->addDays($trialDays)->getTimestamp();
        }

        $subscription = $this->api->subscription->create($params);

        $this->log('Razorpay subscription created', [
            'razorpay_subscription_id' => $subscription->id,
            'customer_id' => $customerId,
            'plan_id' => $planId,
        ]);

        return $subscription;
    }

    /**
     * Cancel a Razorpay subscription.
     */
    public function cancelSubscription(string $subscriptionId, bool $atPeriodEnd = true): void
    {
        $this->api->subscription->fetch($subscriptionId)->cancel([
            'cancel_at_cycle_end' => $atPeriodEnd ? 1 : 0,
        ]);

        $this->log('Razorpay subscription cancelled', [
            'razorpay_subscription_id' => $subscriptionId,
            'at_period_end' => $atPeriodEnd,
        ]);
    }

    /**
     * Verify Razorpay webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $webhookSecret = config('services.razorpay.webhook_secret');

        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Fetch a Razorpay payment by ID.
     */
    public function fetchPayment(string $paymentId): object
    {
        return $this->api->payment->fetch($paymentId);
    }

    /**
     * Fetch a Razorpay subscription by ID.
     */
    public function fetchSubscription(string $subscriptionId): object
    {
        return $this->api->subscription->fetch($subscriptionId);
    }

    /**
     * Create a Razorpay plan from a PlanDefinition.
     */
    public function createPlan(PlanDefinition $plan, string $period, string $currency = 'INR'): object
    {
        $amount = $period === 'yearly'
            ? (int) ($plan->price_inr_yearly * 100)
            : (int) ($plan->price_inr_monthly * 100);

        if ($currency === 'USD') {
            $amount = $period === 'yearly'
                ? (int) ($plan->price_usd_yearly * 100)
                : (int) ($plan->price_usd_monthly * 100);
        }

        $interval = $period === 'yearly' ? 12 : 1;

        $razorpayPlan = $this->api->plan->create([
            'period' => 'monthly',
            'interval' => $interval,
            'item' => [
                'name' => $plan->name . ' (' . ucfirst($period) . ')',
                'amount' => $amount,
                'currency' => $currency,
                'description' => $plan->description ?? '',
            ],
            'notes' => [
                'plan_definition_id' => $plan->id,
                'plan_code' => $plan->code->value,
                'billing_period' => $period,
            ],
        ]);

        $this->log('Razorpay plan created', [
            'plan_definition_id' => $plan->id,
            'razorpay_plan_id' => $razorpayPlan->id,
            'period' => $period,
            'currency' => $currency,
        ]);

        return $razorpayPlan;
    }

    /**
     * Verify Razorpay payment signature (checkout flow).
     */
    public function verifyPaymentSignature(string $subscriptionId, string $paymentId, string $signature): bool
    {
        $expectedSignature = hash_hmac(
            'sha256',
            $paymentId . '|' . $subscriptionId,
            config('services.razorpay.key_secret'),
        );

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get the Razorpay API instance.
     */
    public function getApi(): Api
    {
        return $this->api;
    }
}
