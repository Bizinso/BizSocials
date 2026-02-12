<?php

declare(strict_types=1);

namespace Database\Factories\Billing;

use App\Enums\Billing\Currency;
use App\Enums\Billing\PaymentStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\Payment;
use App\Models\Billing\Subscription;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for Payment model.
 *
 * @extends Factory<Payment>
 */
final class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Payment>
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 500, 10000);
        $fee = round($amount * 0.02, 2); // 2% fee
        $taxOnFee = round($fee * 0.18, 2); // 18% GST on fee

        return [
            'tenant_id' => Tenant::factory(),
            'subscription_id' => null,
            'invoice_id' => null,
            'razorpay_payment_id' => 'pay_' . fake()->regexify('[A-Za-z0-9]{14}'),
            'razorpay_order_id' => 'order_' . fake()->regexify('[A-Za-z0-9]{14}'),
            'status' => PaymentStatus::CAPTURED,
            'amount' => $amount,
            'currency' => Currency::INR,
            'method' => 'card',
            'method_details' => [
                'last4' => fake()->numerify('####'),
                'brand' => fake()->randomElement(['Visa', 'Mastercard', 'RuPay']),
                'exp_month' => fake()->numberBetween(1, 12),
                'exp_year' => fake()->numberBetween(2025, 2030),
            ],
            'fee' => $fee,
            'tax_on_fee' => $taxOnFee,
            'error_code' => null,
            'error_description' => null,
            'captured_at' => now()->subDays(fake()->numberBetween(0, 30)),
            'refunded_at' => null,
            'refund_amount' => null,
            'metadata' => null,
        ];
    }

    /**
     * Set status to created.
     */
    public function created(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::CREATED,
            'captured_at' => null,
            'fee' => null,
            'tax_on_fee' => null,
        ]);
    }

    /**
     * Set status to authorized.
     */
    public function authorized(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::AUTHORIZED,
            'captured_at' => null,
        ]);
    }

    /**
     * Set status to captured.
     */
    public function captured(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::CAPTURED,
            'captured_at' => now()->subDays(fake()->numberBetween(0, 30)),
        ]);
    }

    /**
     * Set status to failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::FAILED,
            'captured_at' => null,
            'fee' => null,
            'tax_on_fee' => null,
            'error_code' => fake()->randomElement([
                'BAD_REQUEST_ERROR',
                'GATEWAY_ERROR',
                'SERVER_ERROR',
            ]),
            'error_description' => fake()->sentence(),
        ]);
    }

    /**
     * Set status to refunded.
     */
    public function refunded(): static
    {
        return $this->state(function (array $attributes): array {
            $amount = $attributes['amount'] ?? 1000;

            return [
                'status' => PaymentStatus::REFUNDED,
                'refunded_at' => now()->subDays(fake()->numberBetween(0, 7)),
                'refund_amount' => $amount,
            ];
        });
    }

    /**
     * Associate with a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes): array => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Associate with a specific subscription.
     */
    public function forSubscription(Subscription $subscription): static
    {
        return $this->state(fn (array $attributes): array => [
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
        ]);
    }

    /**
     * Associate with a specific invoice.
     */
    public function forInvoice(Invoice $invoice): static
    {
        return $this->state(fn (array $attributes): array => [
            'invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
        ]);
    }

    /**
     * Set payment method to card.
     */
    public function card(): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => 'card',
            'method_details' => [
                'last4' => fake()->numerify('####'),
                'brand' => fake()->randomElement(['Visa', 'Mastercard', 'RuPay']),
                'exp_month' => fake()->numberBetween(1, 12),
                'exp_year' => fake()->numberBetween(2025, 2030),
            ],
        ]);
    }

    /**
     * Set payment method to UPI.
     */
    public function upi(): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => 'upi',
            'method_details' => [
                'vpa' => fake()->userName() . '@upi',
            ],
        ]);
    }

    /**
     * Set payment method to netbanking.
     */
    public function netbanking(): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => 'netbanking',
            'method_details' => [
                'bank' => fake()->randomElement(['HDFC', 'ICICI', 'SBI', 'Axis']),
            ],
        ]);
    }
}
