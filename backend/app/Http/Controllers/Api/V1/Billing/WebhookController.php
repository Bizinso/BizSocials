<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Billing;

use App\Enums\Billing\InvoiceStatus;
use App\Enums\Billing\PaymentStatus;
use App\Enums\Billing\SubscriptionStatus;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Billing\Invoice;
use App\Models\Billing\Payment;
use App\Models\Billing\Subscription;
use App\Services\Billing\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class WebhookController extends Controller
{
    public function __construct(
        private readonly RazorpayService $razorpayService,
    ) {}

    /**
     * Handle Razorpay webhook events.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature', '');

        if (!$this->razorpayService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('[RazorpayWebhook] Invalid signature');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Idempotency check — skip duplicate webhook deliveries
        $idempotencyKey = 'rzp_webhook_' . md5($payload);
        if (Cache::has($idempotencyKey)) {
            Log::debug('[RazorpayWebhook] Duplicate webhook skipped');
            return response()->json(['status' => 'ok']);
        }

        $event = $request->input('event');
        $payloadData = $request->input('payload', []);

        Log::info('[RazorpayWebhook] Event received', ['event' => $event]);

        try {
            match ($event) {
                'subscription.activated' => $this->handleSubscriptionActivated($payloadData),
                'subscription.charged' => $this->handleSubscriptionCharged($payloadData),
                'subscription.cancelled' => $this->handleSubscriptionCancelled($payloadData),
                'subscription.halted' => $this->handleSubscriptionHalted($payloadData),
                'subscription.completed' => $this->handleSubscriptionCompleted($payloadData),
                'subscription.resumed' => $this->handleSubscriptionResumed($payloadData),
                'subscription.pending' => $this->handleSubscriptionPending($payloadData),
                'payment.captured' => $this->handlePaymentCaptured($payloadData),
                'payment.failed' => $this->handlePaymentFailed($payloadData),
                default => Log::debug('[RazorpayWebhook] Unhandled event', ['event' => $event]),
            };
        } catch (\Throwable $e) {
            Log::error('[RazorpayWebhook] Error processing event', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }

        // Mark as processed for idempotency (1 hour TTL)
        Cache::put($idempotencyKey, true, 3600);

        return response()->json(['status' => 'ok']);
    }

    private function handleSubscriptionActivated(array $payload): void
    {
        $rzpSubscription = $payload['subscription']['entity'] ?? [];
        $subscriptionId = $rzpSubscription['id'] ?? null;

        if (!$subscriptionId) {
            return;
        }

        $subscription = Subscription::where('razorpay_subscription_id', $subscriptionId)->first();

        if ($subscription === null) {
            Log::warning('[RazorpayWebhook] Subscription not found', ['razorpay_id' => $subscriptionId]);
            return;
        }

        $subscription->markAsActive();
        $subscription->update([
            'current_period_start' => isset($rzpSubscription['current_start'])
                ? now()->setTimestamp($rzpSubscription['current_start'])
                : now(),
            'current_period_end' => isset($rzpSubscription['current_end'])
                ? now()->setTimestamp($rzpSubscription['current_end'])
                : null,
        ]);

        Log::info('[RazorpayWebhook] Subscription activated', ['subscription_id' => $subscription->id]);
    }

    private function handleSubscriptionCharged(array $payload): void
    {
        $rzpSubscription = $payload['subscription']['entity'] ?? [];
        $rzpPayment = $payload['payment']['entity'] ?? [];
        $subscriptionId = $rzpSubscription['id'] ?? null;

        if (!$subscriptionId) {
            return;
        }

        $subscription = Subscription::where('razorpay_subscription_id', $subscriptionId)->first();

        if ($subscription === null) {
            return;
        }

        DB::transaction(function () use ($subscription, $rzpSubscription, $rzpPayment) {
            // Update subscription period
            $subscription->update([
                'status' => SubscriptionStatus::ACTIVE,
                'current_period_start' => isset($rzpSubscription['current_start'])
                    ? now()->setTimestamp($rzpSubscription['current_start'])
                    : now(),
                'current_period_end' => isset($rzpSubscription['current_end'])
                    ? now()->setTimestamp($rzpSubscription['current_end'])
                    : null,
            ]);

            // Create payment record
            if (!empty($rzpPayment['id'])) {
                $existingPayment = Payment::where('razorpay_payment_id', $rzpPayment['id'])->first();

                if ($existingPayment === null) {
                    Payment::create([
                        'tenant_id' => $subscription->tenant_id,
                        'subscription_id' => $subscription->id,
                        'razorpay_payment_id' => $rzpPayment['id'],
                        'razorpay_order_id' => $rzpPayment['order_id'] ?? null,
                        'status' => PaymentStatus::CAPTURED,
                        'amount' => ($rzpPayment['amount'] ?? 0) / 100,
                        'currency' => $rzpPayment['currency'] ?? 'INR',
                        'method' => $rzpPayment['method'] ?? 'unknown',
                        'fee' => ($rzpPayment['fee'] ?? 0) / 100,
                        'tax_on_fee' => ($rzpPayment['tax'] ?? 0) / 100,
                        'captured_at' => now(),
                    ]);
                }
            }

            // Create/update invoice
            $invoice = Invoice::where('subscription_id', $subscription->id)
                ->where('status', InvoiceStatus::ISSUED)
                ->orderByDesc('created_at')
                ->first();

            if ($invoice) {
                $invoice->markAsPaid();
            }
        });

        Log::info('[RazorpayWebhook] Subscription charged', ['subscription_id' => $subscription->id]);
    }

    private function handleSubscriptionCancelled(array $payload): void
    {
        $rzpSubscription = $payload['subscription']['entity'] ?? [];
        $subscriptionId = $rzpSubscription['id'] ?? null;

        if (!$subscriptionId) {
            return;
        }

        $subscription = Subscription::where('razorpay_subscription_id', $subscriptionId)->first();

        if ($subscription === null) {
            return;
        }

        $subscription->markAsCancelled();

        Log::info('[RazorpayWebhook] Subscription cancelled', ['subscription_id' => $subscription->id]);
    }

    private function handleSubscriptionHalted(array $payload): void
    {
        $rzpSubscription = $payload['subscription']['entity'] ?? [];
        $subscriptionId = $rzpSubscription['id'] ?? null;

        if (!$subscriptionId) {
            return;
        }

        $subscription = Subscription::where('razorpay_subscription_id', $subscriptionId)->first();

        if ($subscription === null) {
            return;
        }

        $subscription->markAsHalted();

        Log::info('[RazorpayWebhook] Subscription halted', ['subscription_id' => $subscription->id]);
    }

    private function handleSubscriptionCompleted(array $payload): void
    {
        $rzpSubscription = $payload['subscription']['entity'] ?? [];
        $subscriptionId = $rzpSubscription['id'] ?? null;

        if (!$subscriptionId) {
            return;
        }

        $subscription = Subscription::where('razorpay_subscription_id', $subscriptionId)->first();

        if ($subscription === null) {
            return;
        }

        $subscription->update([
            'status' => SubscriptionStatus::COMPLETED,
            'ended_at' => now(),
        ]);

        Log::info('[RazorpayWebhook] Subscription completed', ['subscription_id' => $subscription->id]);
    }

    private function handleSubscriptionResumed(array $payload): void
    {
        $rzpSubscription = $payload['subscription']['entity'] ?? [];
        $subscriptionId = $rzpSubscription['id'] ?? null;

        if (!$subscriptionId) {
            return;
        }

        $subscription = Subscription::where('razorpay_subscription_id', $subscriptionId)->first();

        if ($subscription === null) {
            return;
        }

        $subscription->markAsActive();

        Log::info('[RazorpayWebhook] Subscription resumed', ['subscription_id' => $subscription->id]);
    }

    private function handleSubscriptionPending(array $payload): void
    {
        $rzpSubscription = $payload['subscription']['entity'] ?? [];
        $subscriptionId = $rzpSubscription['id'] ?? null;

        if (!$subscriptionId) {
            return;
        }

        $subscription = Subscription::where('razorpay_subscription_id', $subscriptionId)->first();

        if ($subscription === null) {
            return;
        }

        $subscription->markAsPending();

        Log::info('[RazorpayWebhook] Subscription pending', ['subscription_id' => $subscription->id]);
    }

    private function handlePaymentCaptured(array $payload): void
    {
        $rzpPayment = $payload['payment']['entity'] ?? [];
        $paymentId = $rzpPayment['id'] ?? null;

        if (!$paymentId) {
            return;
        }

        $payment = Payment::where('razorpay_payment_id', $paymentId)->first();

        if ($payment === null) {
            return;
        }

        $payment->markAsCaptured(
            fee: ($rzpPayment['fee'] ?? 0) / 100,
            tax: ($rzpPayment['tax'] ?? 0) / 100,
        );

        Log::info('[RazorpayWebhook] Payment captured', ['payment_id' => $payment->id]);
    }

    private function handlePaymentFailed(array $payload): void
    {
        $rzpPayment = $payload['payment']['entity'] ?? [];
        $paymentId = $rzpPayment['id'] ?? null;

        if (!$paymentId) {
            return;
        }

        $payment = Payment::where('razorpay_payment_id', $paymentId)->first();

        if ($payment !== null) {
            $payment->markAsFailed(
                errorCode: $rzpPayment['error_code'] ?? 'PAYMENT_FAILED',
                errorDescription: $rzpPayment['error_description'] ?? 'Payment failed',
            );
        }

        Log::info('[RazorpayWebhook] Payment failed', ['razorpay_payment_id' => $paymentId]);
    }
}
