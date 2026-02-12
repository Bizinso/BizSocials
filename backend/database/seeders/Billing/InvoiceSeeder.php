<?php

declare(strict_types=1);

namespace Database\Seeders\Billing;

use App\Enums\Billing\InvoiceStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\Subscription;
use Illuminate\Database\Seeder;

/**
 * Seeder for Invoice model.
 *
 * Creates sample invoices for existing subscriptions.
 */
final class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subscriptions = Subscription::all();

        foreach ($subscriptions as $subscription) {
            $amount = (float) $subscription->amount;

            // Skip free plans
            if ($amount <= 0) {
                continue;
            }

            // Create 1-3 invoices per subscription
            $invoiceCount = fake()->numberBetween(1, 3);

            for ($i = 0; $i < $invoiceCount; $i++) {
                $subtotal = $amount;
                $taxAmount = round($subtotal * 0.18, 2);
                $total = $subtotal + $taxAmount;

                // Most invoices are paid
                $isPaid = $i < $invoiceCount - 1 || fake()->boolean(70);
                $status = $isPaid ? InvoiceStatus::PAID : InvoiceStatus::ISSUED;

                $issuedAt = now()->subMonths($invoiceCount - $i);
                $dueAt = $issuedAt->copy()->addDays(15);
                $paidAt = $isPaid ? $issuedAt->copy()->addDays(fake()->numberBetween(1, 10)) : null;

                Invoice::create([
                    'tenant_id' => $subscription->tenant_id,
                    'subscription_id' => $subscription->id,
                    'invoice_number' => Invoice::generateInvoiceNumber(),
                    'razorpay_invoice_id' => 'inv_' . fake()->regexify('[A-Za-z0-9]{14}'),
                    'status' => $status,
                    'currency' => $subscription->currency,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                    'amount_paid' => $isPaid ? $total : 0,
                    'amount_due' => $isPaid ? 0 : $total,
                    'gst_details' => [
                        'gstin' => '27AABCU9603R1ZM',
                        'place_of_supply' => 'Maharashtra',
                        'cgst' => round($subtotal * 0.09, 2),
                        'sgst' => round($subtotal * 0.09, 2),
                        'igst' => 0,
                        'total_gst' => $taxAmount,
                    ],
                    'billing_address' => [
                        'name' => $subscription->tenant->name,
                        'address_line1' => fake()->streetAddress(),
                        'address_line2' => 'Floor ' . fake()->numberBetween(1, 20),
                        'city' => 'Mumbai',
                        'state' => 'Maharashtra',
                        'country' => 'IN',
                        'postal_code' => '400001',
                        'gstin' => '27AABCU9603R1ZM',
                    ],
                    'line_items' => [
                        [
                            'description' => sprintf(
                                '%s Plan - %s',
                                $subscription->plan->name ?? 'Subscription',
                                $subscription->billing_cycle->label()
                            ),
                            'quantity' => 1,
                            'unit_price' => $subtotal,
                            'amount' => $subtotal,
                            'hsn_code' => '998314',
                        ],
                    ],
                    'issued_at' => $issuedAt,
                    'due_at' => $dueAt,
                    'paid_at' => $paidAt,
                    'pdf_url' => null,
                ]);
            }
        }

        $this->command->info('Invoices seeded successfully.');
    }
}
