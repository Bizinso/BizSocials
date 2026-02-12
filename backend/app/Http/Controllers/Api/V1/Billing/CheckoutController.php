<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Billing;

use App\Data\Billing\CheckoutData;
use App\Enums\Billing\PaymentStatus;
use App\Enums\Billing\SubscriptionStatus;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Billing\InitiateCheckoutRequest;
use App\Http\Requests\Billing\VerifyPaymentRequest;
use App\Models\Billing\Payment;
use App\Models\Billing\Subscription;
use App\Models\Platform\PlanDefinition;
use App\Services\Billing\RazorpayService;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class CheckoutController extends Controller
{
    public function __construct(
        private readonly RazorpayService $razorpayService,
        private readonly SubscriptionService $subscriptionService,
    ) {}

    /**
     * Initiate a Razorpay checkout session.
     */
    public function initiate(InitiateCheckoutRequest $request): JsonResponse
    {
        $tenant = $request->user()->currentTenant;
        $plan = PlanDefinition::findOrFail($request->validated('plan_id'));
        $billingCycle = $request->validated('billing_cycle', 'monthly');

        // Create Razorpay customer
        $customerId = $this->razorpayService->createCustomer($tenant);

        // Get the appropriate Razorpay plan ID
        $razorpayPlanId = $billingCycle === 'yearly'
            ? $plan->razorpay_plan_id_inr
            : $plan->razorpay_plan_id_inr;

        // Create Razorpay subscription
        $rzpSubscription = $this->razorpayService->createSubscription(
            $customerId,
            $razorpayPlanId ?? '',
            $plan->trial_days > 0 ? $plan->trial_days : null,
        );

        // Calculate amount
        $amount = $billingCycle === 'yearly'
            ? (float) $plan->price_inr_yearly
            : (float) $plan->price_inr_monthly;

        // Create local subscription record
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::CREATED,
            'billing_cycle' => $billingCycle,
            'currency' => 'INR',
            'amount' => $amount,
            'razorpay_subscription_id' => $rzpSubscription->id,
            'razorpay_customer_id' => $customerId,
            'current_period_start' => now(),
            'current_period_end' => $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth(),
            'trial_start' => $plan->trial_days > 0 ? now() : null,
            'trial_end' => $plan->trial_days > 0 ? now()->addDays($plan->trial_days) : null,
            'cancel_at_period_end' => false,
        ]);

        return $this->success(CheckoutData::from([
            'subscription_id' => $subscription->id,
            'razorpay_subscription_id' => $rzpSubscription->id,
            'razorpay_key_id' => config('services.razorpay.key_id'),
            'plan_name' => $plan->name,
            'amount' => (int) ($amount * 100), // paise
            'currency' => 'INR',
        ]), 'Checkout initiated');
    }

    /**
     * Verify a Razorpay payment after checkout completion.
     */
    public function verify(VerifyPaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Verify HMAC signature
        $isValid = $this->razorpayService->verifyPaymentSignature(
            $validated['razorpay_subscription_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature'],
        );

        if (!$isValid) {
            return $this->error('Payment verification failed', 400);
        }

        $subscription = Subscription::where('razorpay_subscription_id', $validated['razorpay_subscription_id'])->first();

        if ($subscription === null) {
            return $this->notFound('Subscription not found');
        }

        $result = DB::transaction(function () use ($subscription, $validated) {
            // Activate subscription
            $subscription = $this->subscriptionService->activate($subscription, $validated['razorpay_payment_id']);

            // Update tenant plan
            $subscription->tenant()->update(['plan_id' => $subscription->plan_id]);

            // Create payment record (idempotent)
            Payment::firstOrCreate(
                ['razorpay_payment_id' => $validated['razorpay_payment_id']],
                [
                    'tenant_id' => $subscription->tenant_id,
                    'subscription_id' => $subscription->id,
                    'status' => PaymentStatus::CAPTURED,
                    'amount' => $subscription->amount,
                    'currency' => $subscription->currency->value ?? 'INR',
                    'method' => 'razorpay',
                    'captured_at' => now(),
                ],
            );

            return $subscription;
        });

        return $this->success($result->load('plan'), 'Payment verified successfully');
    }
}
