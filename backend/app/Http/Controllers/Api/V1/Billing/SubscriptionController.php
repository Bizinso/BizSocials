<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Billing;

use App\Data\Billing\CreateSubscriptionData;
use App\Data\Billing\SubscriptionData;
use App\Enums\Billing\BillingCycle;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Billing\ChangePlanRequest;
use App\Http\Requests\Billing\CreateSubscriptionRequest;
use App\Models\Platform\PlanDefinition;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    /**
     * Get the current subscription.
     *
     * GET /api/v1/billing/subscription
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $subscription = $this->subscriptionService->getCurrentForTenant($tenant);

        if ($subscription === null) {
            return $this->success(null, 'No active subscription');
        }

        return $this->success(
            SubscriptionData::fromModel($subscription),
            'Subscription retrieved'
        );
    }

    /**
     * Create a new subscription.
     *
     * POST /api/v1/billing/subscription
     */
    public function store(CreateSubscriptionRequest $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $plan = PlanDefinition::findOrFail($request->validated('plan_id'));

        $data = new CreateSubscriptionData(
            plan_id: $request->validated('plan_id'),
            billing_cycle: BillingCycle::tryFrom($request->validated('billing_cycle', 'monthly')) ?? BillingCycle::MONTHLY,
            payment_method_id: $request->validated('payment_method_id'),
        );

        $subscription = $this->subscriptionService->create($tenant, $plan, $data);

        return $this->created(
            SubscriptionData::fromModel($subscription),
            'Subscription created'
        );
    }

    /**
     * Change the subscription plan.
     *
     * PUT /api/v1/billing/subscription/plan
     */
    public function changePlan(ChangePlanRequest $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $subscription = $this->subscriptionService->getCurrentForTenant($tenant);

        if ($subscription === null) {
            return $this->notFound('No active subscription found');
        }

        $newPlan = PlanDefinition::findOrFail($request->validated('plan_id'));

        $subscription = $this->subscriptionService->changePlan($subscription, $newPlan);

        return $this->success(
            SubscriptionData::fromModel($subscription),
            'Plan changed successfully'
        );
    }

    /**
     * Cancel the subscription.
     *
     * POST /api/v1/billing/subscription/cancel
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();

        // Only owner can manage billing
        if (!$user->isOwner()) {
            return $this->forbidden('Only the account owner can manage billing');
        }

        $tenant = $user->tenant;
        $subscription = $this->subscriptionService->getCurrentForTenant($tenant);

        if ($subscription === null) {
            return $this->notFound('No active subscription found');
        }

        $atPeriodEnd = $request->boolean('at_period_end', true);
        $subscription = $this->subscriptionService->cancel($subscription, $atPeriodEnd);

        return $this->success(
            SubscriptionData::fromModel($subscription),
            'Subscription cancelled'
        );
    }

    /**
     * Reactivate a cancelled subscription.
     *
     * POST /api/v1/billing/subscription/reactivate
     */
    public function reactivate(Request $request): JsonResponse
    {
        $user = $request->user();

        // Only owner can manage billing
        if (!$user->isOwner()) {
            return $this->forbidden('Only the account owner can manage billing');
        }

        $tenant = $user->tenant;
        $subscription = $this->subscriptionService->getCurrentForTenant($tenant);

        if ($subscription === null) {
            return $this->notFound('No active subscription found');
        }

        $subscription = $this->subscriptionService->reactivate($subscription);

        return $this->success(
            SubscriptionData::fromModel($subscription),
            'Subscription reactivated'
        );
    }
}
