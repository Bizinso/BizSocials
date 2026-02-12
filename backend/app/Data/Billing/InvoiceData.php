<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Models\Billing\Invoice;
use Spatie\LaravelData\Data;

final class InvoiceData extends Data
{
    public function __construct(
        public string $id,
        public ?string $subscription_id,
        public string $invoice_number,
        public string $status,
        public string $currency,
        public string $subtotal,
        public string $tax,
        public string $total,
        public string $amount_paid,
        public string $amount_due,
        public ?string $due_date,
        public ?string $paid_at,
        public ?string $razorpay_invoice_id,
        public ?string $pdf_url,
        public array $line_items,
        public string $created_at,
    ) {}

    /**
     * Create InvoiceData from an Invoice model.
     */
    public static function fromModel(Invoice $invoice): self
    {
        return new self(
            id: $invoice->id,
            subscription_id: $invoice->subscription_id,
            invoice_number: $invoice->invoice_number,
            status: $invoice->status->value,
            currency: $invoice->currency->value,
            subtotal: number_format((float) $invoice->subtotal, 2, '.', ''),
            tax: number_format((float) $invoice->tax_amount, 2, '.', ''),
            total: number_format((float) $invoice->total, 2, '.', ''),
            amount_paid: number_format((float) $invoice->amount_paid, 2, '.', ''),
            amount_due: number_format((float) $invoice->amount_due, 2, '.', ''),
            due_date: $invoice->due_at?->toIso8601String(),
            paid_at: $invoice->paid_at?->toIso8601String(),
            razorpay_invoice_id: $invoice->razorpay_invoice_id,
            pdf_url: $invoice->pdf_url,
            line_items: $invoice->line_items ?? [],
            created_at: $invoice->created_at->toIso8601String(),
        );
    }
}
