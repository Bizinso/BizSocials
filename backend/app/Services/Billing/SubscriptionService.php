<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Data\Billing\CreateSubscriptionData;
use App\Enums\Billing\Currency;
use App\Enums\Billing\SubscriptionStatus;
use App\Models\Billing\Subscription;
use App\Models\Platform\PlanDefinition;
use App\Models\Tenant\Tenant;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class SubscriptionService extends BaseService
{
    public function __construct(
        private readonly RazorpayService $razorpayService,
    ) {}
    /**
     * Get the current active subscription for a tenant.
     */
    public function getCurrentForTenant(Tenant $tenant): ?Subscription
    {
        return Subscription::forTenant($tenant->id)
            ->whereIn('status', [
                SubscriptionStatus::ACTIVE,
                SubscriptionStatus::PENDING,
                SubscriptionStatus::AUTHENTICATED,
                SubscriptionStatus::CREATED,
            ])
            ->with('plan')
            ->first();
    }

    /**
     * Get the active subscription for a tenant.
     */
    public function getActiveForTenant(Tenant $tenant): ?Subscription
    {
        return Subscription::forTenant($tenant->id)
            ->active()
            ->with('plan')
            ->first();
    }

    /**
     * Create a new subscription for a tenant.
     */
    public function create(Tenant $tenant, PlanDefinition $plan, CreateSubscriptionData $data): Subscription
    {
        return $this->transaction(function () use ($tenant, $plan, $data) {
            // Check if tenant already has an active subscription
            $existing = $this->getCurrentForTenant($tenant);
            if ($existing !== null) {
                throw ValidationException::withMessages([
                    'subscription' => ['Tenant already has an active subscription.'],
                ]);
            }

            // Get the amount based on billing cycle and currency
            $currency = Currency::INR; // Default to INR
            $amount = $data->billing_cycle->value === 'yearly'
                ? (float) $plan->price_inr_yearly
                : (float) $plan->price_inr_monthly;

            // Calculate period dates
            $periodStart = now();
            $periodEnd = $data->billing_cycle->value === 'yearly'
                ? $periodStart->copy()->addYear()
                : $periodStart->copy()->addMonth();

            // Calculate trial dates if applicable
            $trialEnd = null;
            if ($plan->trial_days > 0) {
                $trialEnd = now()->addDays($plan->trial_days);
            }

            // Create Razorpay customer and subscription
            $customerId = $this->razorpayService->createCustomer($tenant);

            $razorpayPlanId = $data->billing_cycle->value === 'yearly'
                ? $plan->razorpay_plan_id_inr
                : $plan->razorpay_plan_id_inr;

            $rzpSubscription = $this->razorpayService->createSubscription(
                $customerId,
                $razorpayPlanId ?? '',
                $plan->trial_days > 0 ? $plan->trial_days : null,
            );

            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::ACTIVE,
                'billing_cycle' => $data->billing_cycle,
                'currency' => $currency,
                'amount' => $amount,
                'razorpay_subscription_id' => $rzpSubscription->id,
                'razorpay_customer_id' => $customerId,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'trial_start' => $trialEnd !== null ? now() : null,
                'trial_end' => $trialEnd,
                'cancel_at_period_end' => false,
            ]);

            // Update tenant's plan_id
            $tenant->update(['plan_id' => $plan->id]);

            $this->log('Subscription created', [
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
            ]);

            return $subscription->load('plan');
        });
    }

    /**
     * Change the subscription plan.
     */
    public function changePlan(Subscription $subscription, PlanDefinition $newPlan): Subscription
    {
        return $this->transaction(function () use ($subscription, $newPlan) {
            if ($subscription->plan_id === $newPlan->id) {
                throw ValidationException::withMessages([
                    'plan_id' => ['Already subscribed to this plan.'],
                ]);
            }

            // Update amount based on new plan
            $amount = $subscription->billing_cycle->value === 'yearly'
                ? (float) $newPlan->price_inr_yearly
                : (float) $newPlan->price_inr_monthly;

            $subscription->update([
                'plan_id' => $newPlan->id,
                'amount' => $amount,
            ]);

            // Update tenant's plan_id
            $subscription->tenant()->update(['plan_id' => $newPlan->id]);

            $this->log('Subscription plan changed', [
                'subscription_id' => $subscription->id,
                'new_plan_id' => $newPlan->id,
            ]);

            return $subscription->fresh(['plan']);
        });
    }

    /**
     * Cancel a subscription.
     */
    public function cancel(Subscription $subscription, bool $atPeriodEnd = true): Subscription
    {
        return $this->transaction(function () use ($subscription, $atPeriodEnd) {
            if ($subscription->status === SubscriptionStatus::CANCELLED) {
                throw ValidationException::withMessages([
                    'subscription' => ['Subscription is already cancelled.'],
                ]);
            }

            if (in_array($subscription->status, [SubscriptionStatus::COMPLETED, SubscriptionStatus::EXPIRED], true)) {
                throw ValidationException::withMessages([
                    'subscription' => ['Cannot cancel a completed or expired subscription.'],
                ]);
            }

            // Cancel on Razorpay
            if ($subscription->razorpay_subscription_id) {
                $this->razorpayService->cancelSubscription(
                    $subscription->razorpay_subscription_id,
                    $atPeriodEnd,
                );
            }

            $subscription->cancel($atPeriodEnd);

            $this->log('Subscription cancelled', [
                'subscription_id' => $subscription->id,
                'at_period_end' => $atPeriodEnd,
            ]);

            return $subscription->fresh(['plan']);
        });
    }

    /**
     * Reactivate a cancelled subscription.
     */
    public function reactivate(Subscription $subscription): Subscription
    {
        return $this->transaction(function () use ($subscription) {
            // Can only reactivate if cancelled with cancel_at_period_end = true
            // and the period hasn't ended yet
            if ($subscription->cancelled_at === null) {
                throw ValidationException::withMessages([
                    'subscription' => ['Subscription is not cancelled.'],
                ]);
            }

            if ($subscription->status === SubscriptionStatus::CANCELLED && $subscription->ended_at !== null) {
                throw ValidationException::withMessages([
                    'subscription' => ['Cannot reactivate an ended subscription.'],
                ]);
            }

            if (!$subscription->cancel_at_period_end) {
                throw ValidationException::withMessages([
                    'subscription' => ['Cannot reactivate an immediately cancelled subscription.'],
                ]);
            }

            // Handle reactivation directly for active subscriptions pending cancellation
            $subscription->cancelled_at = null;
            $subscription->cancel_at_period_end = false;
            if ($subscription->status !== SubscriptionStatus::ACTIVE) {
                $subscription->status = SubscriptionStatus::ACTIVE;
            }
            $subscription->save();

            $this->log('Subscription reactivated', [
                'subscription_id' => $subscription->id,
            ]);

            return $subscription->fresh(['plan']);
        });
    }

    /**
     * Activate a subscription after successful checkout payment.
     */
    public function activate(Subscription $subscription, string $paymentId): Subscription
    {
        return $this->transaction(function () use ($subscription, $paymentId) {
            $subscription->update([
                'status' => SubscriptionStatus::ACTIVE,
                'current_period_start' => now(),
            ]);

            $this->log('Subscription activated via checkout', [
                'subscription_id' => $subscription->id,
                'payment_id' => $paymentId,
            ]);

            return $subscription->fresh(['plan']);
        });
    }

    /**
     * Get subscription history for a tenant.
     *
     * @return Collection<int, Subscription>
     */
    public function getHistory(Tenant $tenant): Collection
    {
        return Subscription::forTenant($tenant->id)
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
