<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Billing;

use App\Data\Billing\AddPaymentMethodData;
use App\Data\Billing\PaymentMethodData;
use App\Enums\Billing\PaymentMethodType;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Billing\AddPaymentMethodRequest;
use App\Services\Billing\PaymentMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentMethodController extends Controller
{
    public function __construct(
        private readonly PaymentMethodService $paymentMethodService,
    ) {}

    /**
     * List payment methods for the current tenant.
     *
     * GET /api/v1/billing/payment-methods
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $methods = $this->paymentMethodService->listForTenant($tenant);

        return $this->success(
            $methods->map(fn ($method) => PaymentMethodData::fromModel($method)),
            'Payment methods retrieved'
        );
    }

    /**
     * Add a new payment method.
     *
     * POST /api/v1/billing/payment-methods
     */
    public function store(AddPaymentMethodRequest $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $data = new AddPaymentMethodData(
            type: PaymentMethodType::from($request->validated('type')),
            is_default: $request->boolean('is_default', false),
            card_token: $request->validated('card_token'),
            upi_id: $request->validated('upi_id'),
            card_last4: $request->validated('card_last4'),
            card_brand: $request->validated('card_brand'),
            card_exp_month: $request->validated('card_exp_month'),
            card_exp_year: $request->validated('card_exp_year'),
            bank_name: $request->validated('bank_name'),
        );

        $method = $this->paymentMethodService->add($tenant, $data);

        return $this->created(
            PaymentMethodData::fromModel($method),
            'Payment method added'
        );
    }

    /**
     * Set a payment method as default.
     *
     * PUT /api/v1/billing/payment-methods/{paymentMethod}/default
     */
    public function setDefault(Request $request, string $paymentMethod): JsonResponse
    {
        $user = $request->user();

        // Only owner can manage billing
        if (!$user->isOwner()) {
            return $this->forbidden('Only the account owner can manage billing');
        }

        $tenant = $user->tenant;
        $method = $this->paymentMethodService->getByTenant($tenant, $paymentMethod);

        $method = $this->paymentMethodService->setDefault($method);

        return $this->success(
            PaymentMethodData::fromModel($method),
            'Payment method set as default'
        );
    }

    /**
     * Remove a payment method.
     *
     * DELETE /api/v1/billing/payment-methods/{paymentMethod}
     */
    public function destroy(Request $request, string $paymentMethod): JsonResponse
    {
        $user = $request->user();

        // Only owner can manage billing
        if (!$user->isOwner()) {
            return $this->forbidden('Only the account owner can manage billing');
        }

        $tenant = $user->tenant;
        $method = $this->paymentMethodService->getByTenant($tenant, $paymentMethod);

        $this->paymentMethodService->remove($method);

        return $this->success(null, 'Payment method removed');
    }
}
