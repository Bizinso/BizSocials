<?php

declare(strict_types=1);

namespace Database\Seeders\Billing;

use App\Enums\Billing\InvoiceStatus;
use App\Enums\Billing\PaymentStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\Payment;
use Illuminate\Database\Seeder;

/**
 * Seeder for Payment model.
 *
 * Creates sample payments for paid invoices.
 */
final class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paidInvoices = Invoice::where('status', InvoiceStatus::PAID)->get();

        foreach ($paidInvoices as $invoice) {
            $amount = (float) $invoice->total;
            $fee = round($amount * 0.02, 2); // 2% fee
            $taxOnFee = round($fee * 0.18, 2); // 18% GST on fee

            // Random payment method
            $method = fake()->randomElement(['card', 'upi', 'netbanking']);
            $methodDetails = $this->getMethodDetails($method);

            Payment::firstOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'tenant_id' => $invoice->tenant_id,
                    'subscription_id' => $invoice->subscription_id,
                    'razorpay_payment_id' => 'pay_' . fake()->regexify('[A-Za-z0-9]{14}'),
                    'razorpay_order_id' => 'order_' . fake()->regexify('[A-Za-z0-9]{14}'),
                    'status' => PaymentStatus::CAPTURED,
                    'amount' => $amount,
                    'currency' => $invoice->currency,
                    'method' => $method,
                    'method_details' => $methodDetails,
                    'fee' => $fee,
                    'tax_on_fee' => $taxOnFee,
                    'error_code' => null,
                    'error_description' => null,
                    'captured_at' => $invoice->paid_at,
                    'refunded_at' => null,
                    'refund_amount' => null,
                    'metadata' => null,
                ]
            );
        }

        $this->command->info('Payments seeded successfully.');
    }

    /**
     * Get method details based on payment method type.
     *
     * @return array<string, mixed>
     */
    private function getMethodDetails(string $method): array
    {
        return match ($method) {
            'card' => [
                'last4' => fake()->numerify('####'),
                'brand' => fake()->randomElement(['Visa', 'Mastercard', 'RuPay']),
                'exp_month' => fake()->numberBetween(1, 12),
                'exp_year' => fake()->numberBetween(2025, 2030),
            ],
            'upi' => [
                'vpa' => fake()->userName() . '@upi',
            ],
            'netbanking' => [
                'bank' => fake()->randomElement(['HDFC', 'ICICI', 'SBI', 'Axis']),
            ],
            default => [],
        };
    }
}
