<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Api\V1\Controller;
use App\Services\Billing\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BillingController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
    ) {}

    /**
     * Get billing summary for the current tenant.
     *
     * GET /api/v1/billing/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $summary = $this->billingService->getBillingSummary($tenant);

        return $this->success($summary, 'Billing summary retrieved');
    }

    /**
     * Get usage statistics for the current tenant.
     *
     * GET /api/v1/billing/usage
     */
    public function usage(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $usage = $this->billingService->getUsage($tenant);

        return $this->success($usage, 'Usage statistics retrieved');
    }

    /**
     * Get available plans for upgrade/subscription.
     *
     * GET /api/v1/billing/plans
     */
    public function plans(Request $request): JsonResponse
    {
        $plans = $this->billingService->getAvailablePlans();

        $data = $plans->map(fn ($plan) => [
            'id' => $plan->id,
            'code' => $plan->code->value,
            'name' => $plan->name,
            'description' => $plan->description,
            'price_inr_monthly' => number_format((float) $plan->price_inr_monthly, 2, '.', ''),
            'price_inr_yearly' => number_format((float) $plan->price_inr_yearly, 2, '.', ''),
            'price_usd_monthly' => number_format((float) $plan->price_usd_monthly, 2, '.', ''),
            'price_usd_yearly' => number_format((float) $plan->price_usd_yearly, 2, '.', ''),
            'trial_days' => $plan->trial_days,
            'features' => $plan->features,
            'yearly_discount_percent' => $plan->yearly_discount_percent,
        ]);

        return $this->success($data, 'Plans retrieved');
    }
}
