<?php

declare(strict_types=1);

namespace Database\Factories\Billing;

use App\Enums\Billing\Currency;
use App\Enums\Billing\InvoiceStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\Subscription;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for Invoice model.
 *
 * @extends Factory<Invoice>
 */
final class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Invoice>
     */
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 500, 10000);
        $taxAmount = round($subtotal * 0.18, 2);
        $total = $subtotal + $taxAmount;

        return [
            'tenant_id' => Tenant::factory(),
            'subscription_id' => null,
            'invoice_number' => 'BIZ/' . now()->format('Y') . '-' . (now()->format('y') + 1) . '/' . str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'razorpay_invoice_id' => 'inv_' . fake()->regexify('[A-Za-z0-9]{14}'),
            'status' => InvoiceStatus::ISSUED,
            'currency' => Currency::INR,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'amount_paid' => 0,
            'amount_due' => $total,
            'gst_details' => [
                'gstin' => '27AABCU9603R1ZM',
                'place_of_supply' => 'Maharashtra',
                'cgst' => round($subtotal * 0.09, 2),
                'sgst' => round($subtotal * 0.09, 2),
                'igst' => 0,
                'total_gst' => $taxAmount,
            ],
            'billing_address' => [
                'name' => fake()->company(),
                'address_line1' => fake()->streetAddress(),
                'address_line2' => 'Floor ' . fake()->numberBetween(1, 20),
                'city' => fake()->city(),
                'state' => 'Maharashtra',
                'country' => 'IN',
                'postal_code' => fake()->postcode(),
                'gstin' => '27AABCU9603R1ZM',
            ],
            'line_items' => [
                [
                    'description' => 'Professional Plan - Monthly',
                    'quantity' => 1,
                    'unit_price' => $subtotal,
                    'amount' => $subtotal,
                    'hsn_code' => '998314',
                ],
            ],
            'issued_at' => now()->subDays(fake()->numberBetween(1, 15)),
            'due_at' => now()->addDays(fake()->numberBetween(5, 30)),
            'paid_at' => null,
            'pdf_url' => null,
        ];
    }

    /**
     * Set status to draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::DRAFT,
            'issued_at' => null,
        ]);
    }

    /**
     * Set status to issued.
     */
    public function issued(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::ISSUED,
            'issued_at' => now()->subDays(fake()->numberBetween(1, 15)),
        ]);
    }

    /**
     * Set status to paid.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes): array {
            $total = $attributes['total'] ?? 1000;

            return [
                'status' => InvoiceStatus::PAID,
                'amount_paid' => $total,
                'amount_due' => 0,
                'paid_at' => now()->subDays(fake()->numberBetween(0, 7)),
            ];
        });
    }

    /**
     * Set status to cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::CANCELLED,
        ]);
    }

    /**
     * Set status to expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::EXPIRED,
            'due_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Set invoice to be overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::ISSUED,
            'due_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
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
     * Set GST details with IGST (different state).
     */
    public function withIgst(): static
    {
        return $this->state(function (array $attributes): array {
            $subtotal = $attributes['subtotal'] ?? 1000;
            $taxAmount = round($subtotal * 0.18, 2);

            return [
                'gst_details' => [
                    'gstin' => '07AABCU9603R1ZM',
                    'place_of_supply' => 'Delhi',
                    'cgst' => 0,
                    'sgst' => 0,
                    'igst' => $taxAmount,
                    'total_gst' => $taxAmount,
                ],
                'billing_address' => array_merge($attributes['billing_address'] ?? [], [
                    'state' => 'Delhi',
                    'gstin' => '07AABCU9603R1ZM',
                ]),
            ];
        });
    }

    /**
     * Alias for withIgst().
     */
    public function withGst(): static
    {
        return $this;
    }

    /**
     * Set custom line items.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public function withLineItems(array $items): static
    {
        $subtotal = array_sum(array_column($items, 'amount'));
        $taxAmount = round($subtotal * 0.18, 2);
        $total = $subtotal + $taxAmount;

        return $this->state(fn (array $attributes): array => [
            'line_items' => $items,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'amount_due' => $total,
        ]);
    }
}
