<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Data\Billing\AddPaymentMethodData;
use App\Enums\Billing\PaymentMethodType;
use App\Models\Billing\PaymentMethod;
use App\Models\Tenant\Tenant;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class PaymentMethodService extends BaseService
{
    /**
     * List all payment methods for a tenant.
     *
     * @return Collection<int, PaymentMethod>
     */
    public function listForTenant(Tenant $tenant): Collection
    {
        return PaymentMethod::forTenant($tenant->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get a payment method by ID.
     */
    public function get(string $id): PaymentMethod
    {
        $method = PaymentMethod::find($id);

        if ($method === null) {
            throw ValidationException::withMessages([
                'payment_method' => ['Payment method not found.'],
            ]);
        }

        return $method;
    }

    /**
     * Get a payment method by ID, ensuring it belongs to the tenant.
     */
    public function getByTenant(Tenant $tenant, string $id): PaymentMethod
    {
        $method = PaymentMethod::forTenant($tenant->id)->find($id);

        if ($method === null) {
            throw ValidationException::withMessages([
                'payment_method' => ['Payment method not found.'],
            ]);
        }

        return $method;
    }

    /**
     * Get the default payment method for a tenant.
     */
    public function getDefaultForTenant(Tenant $tenant): ?PaymentMethod
    {
        return PaymentMethod::forTenant($tenant->id)
            ->default()
            ->first();
    }

    /**
     * Add a new payment method for a tenant.
     */
    public function add(Tenant $tenant, AddPaymentMethodData $data): PaymentMethod
    {
        return $this->transaction(function () use ($tenant, $data) {
            // Build details array based on payment method type
            $details = $this->buildDetails($data);

            // Calculate expiry date for cards
            $expiresAt = null;
            if ($data->type === PaymentMethodType::CARD && $data->card_exp_year !== null && $data->card_exp_month !== null) {
                $expiresAt = now()
                    ->setYear($data->card_exp_year)
                    ->setMonth($data->card_exp_month)
                    ->endOfMonth();
            }

            // If this is the first payment method or set as default, handle default logic
            $existingCount = PaymentMethod::forTenant($tenant->id)->count();
            $isDefault = $data->is_default || $existingCount === 0;

            // If setting as default, unset others
            if ($isDefault) {
                PaymentMethod::forTenant($tenant->id)->update(['is_default' => false]);
            }

            // Create the payment method (stubbed - no real Razorpay call)
            $method = PaymentMethod::create([
                'tenant_id' => $tenant->id,
                'razorpay_token_id' => 'token_' . bin2hex(random_bytes(7)), // Stubbed
                'type' => $data->type,
                'is_default' => $isDefault,
                'details' => $details,
                'expires_at' => $expiresAt,
            ]);

            $this->log('Payment method added', [
                'tenant_id' => $tenant->id,
                'payment_method_id' => $method->id,
                'type' => $data->type->value,
            ]);

            return $method;
        });
    }

    /**
     * Set a payment method as default.
     */
    public function setDefault(PaymentMethod $method): PaymentMethod
    {
        return $this->transaction(function () use ($method) {
            $method->setAsDefault();

            $this->log('Payment method set as default', [
                'payment_method_id' => $method->id,
                'tenant_id' => $method->tenant_id,
            ]);

            return $method->fresh();
        });
    }

    /**
     * Remove a payment method.
     */
    public function remove(PaymentMethod $method): void
    {
        $this->transaction(function () use ($method) {
            // Check if this is the only payment method
            $count = PaymentMethod::forTenant($method->tenant_id)->count();

            if ($count === 1 && $method->is_default) {
                throw ValidationException::withMessages([
                    'payment_method' => ['Cannot delete the only payment method.'],
                ]);
            }

            // If deleting the default, set another as default
            if ($method->is_default && $count > 1) {
                $newDefault = PaymentMethod::forTenant($method->tenant_id)
                    ->where('id', '!=', $method->id)
                    ->first();

                if ($newDefault !== null) {
                    $newDefault->update(['is_default' => true]);
                }
            }

            $this->log('Payment method removed', [
                'payment_method_id' => $method->id,
                'tenant_id' => $method->tenant_id,
            ]);

            $method->delete();
        });
    }

    /**
     * Build details array based on payment method type.
     *
     * @return array<string, mixed>
     */
    private function buildDetails(AddPaymentMethodData $data): array
    {
        return match ($data->type) {
            PaymentMethodType::CARD => [
                'last4' => $data->card_last4 ?? '0000',
                'brand' => $data->card_brand ?? 'Visa',
                'exp_month' => $data->card_exp_month ?? 1,
                'exp_year' => $data->card_exp_year ?? (int) now()->addYear()->format('Y'),
                'name' => 'Card Holder',
            ],
            PaymentMethodType::UPI => [
                'vpa' => $data->upi_id ?? 'user@upi',
            ],
            PaymentMethodType::NETBANKING => [
                'bank' => $data->bank_name ?? 'HDFC',
            ],
            PaymentMethodType::WALLET => [
                'provider' => 'PayTM',
            ],
            PaymentMethodType::EMANDATE => [
                'bank' => $data->bank_name ?? 'HDFC',
                'account_last4' => '0000',
            ],
        };
    }
}
