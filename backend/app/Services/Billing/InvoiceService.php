<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\Billing\InvoiceStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\Subscription;
use App\Models\Tenant\Tenant;
use App\Services\BaseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class InvoiceService extends BaseService
{
    /**
     * List invoices for a tenant with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function listForTenant(Tenant $tenant, array $filters = []): LengthAwarePaginator
    {
        $query = Invoice::forTenant($tenant->id)
            ->with('subscription.plan');

        // Filter by status
        if (!empty($filters['status'])) {
            $status = InvoiceStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        // Filter by date range
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = min($perPage, 100); // Max 100 per page

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get an invoice by ID.
     */
    public function get(string $id): Invoice
    {
        $invoice = Invoice::with('subscription.plan')->find($id);

        if ($invoice === null) {
            throw ValidationException::withMessages([
                'invoice' => ['Invoice not found.'],
            ]);
        }

        return $invoice;
    }

    /**
     * Get an invoice by ID, ensuring it belongs to the tenant.
     */
    public function getByTenant(Tenant $tenant, string $id): Invoice
    {
        $invoice = Invoice::forTenant($tenant->id)
            ->with('subscription.plan')
            ->find($id);

        if ($invoice === null) {
            throw ValidationException::withMessages([
                'invoice' => ['Invoice not found.'],
            ]);
        }

        return $invoice;
    }

    /**
     * Create an invoice for a subscription.
     *
     * @param array<string, mixed> $data
     */
    public function create(Subscription $subscription, array $data): Invoice
    {
        return $this->transaction(function () use ($subscription, $data) {
            $subtotal = (float) ($data['subtotal'] ?? $subscription->amount);
            $taxRate = 0.18; // 18% GST
            $taxAmount = round($subtotal * $taxRate, 2);
            $total = $subtotal + $taxAmount;

            $invoice = Invoice::create([
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'status' => InvoiceStatus::ISSUED,
                'currency' => $subscription->currency,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'amount_paid' => 0,
                'amount_due' => $total,
                'gst_details' => [
                    'gstin' => null,
                    'place_of_supply' => $data['state'] ?? 'Maharashtra',
                    'cgst' => round($subtotal * 0.09, 2),
                    'sgst' => round($subtotal * 0.09, 2),
                    'igst' => 0,
                    'total_gst' => $taxAmount,
                ],
                'billing_address' => $data['billing_address'] ?? [],
                'line_items' => [
                    [
                        'description' => $data['description'] ?? 'Subscription',
                        'quantity' => 1,
                        'unit_price' => $subtotal,
                        'amount' => $subtotal,
                        'hsn_code' => '998314',
                    ],
                ],
                'issued_at' => now(),
                'due_at' => now()->addDays(15),
            ]);

            $this->log('Invoice created', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $subscription->id,
            ]);

            return $invoice;
        });
    }

    /**
     * Mark an invoice as paid.
     */
    public function markAsPaid(Invoice $invoice, string $paymentId): Invoice
    {
        return $this->transaction(function () use ($invoice, $paymentId) {
            if ($invoice->isPaid()) {
                throw ValidationException::withMessages([
                    'invoice' => ['Invoice is already paid.'],
                ]);
            }

            $invoice->markAsPaid();

            $this->log('Invoice marked as paid', [
                'invoice_id' => $invoice->id,
                'payment_id' => $paymentId,
            ]);

            return $invoice->fresh();
        });
    }

    /**
     * Generate and store invoice PDF, return download URL.
     */
    public function downloadUrl(Invoice $invoice): string
    {
        if ($invoice->pdf_url !== null) {
            return $invoice->pdf_url;
        }

        return $this->generatePdf($invoice);
    }

    /**
     * Generate a PDF for an invoice and store it.
     */
    public function generatePdf(Invoice $invoice): string
    {
        $invoice->loadMissing('subscription.plan', 'tenant');

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'tenantName' => $invoice->tenant?->name ?? 'Customer',
            'planName' => $invoice->subscription?->plan?->name ?? 'N/A',
        ]);

        $path = "invoices/{$invoice->tenant_id}/{$invoice->invoice_number}.pdf";
        Storage::disk('s3')->put($path, $pdf->output());

        $url = Storage::disk('s3')->temporaryUrl($path, now()->addHours(24));

        $invoice->update(['pdf_url' => $path]);

        return $url;
    }

    /**
     * Get total paid amount for a tenant.
     */
    public function getTotalPaidForTenant(Tenant $tenant): float
    {
        return (float) Invoice::forTenant($tenant->id)
            ->paid()
            ->sum('total');
    }

    /**
     * Get total invoice count for a tenant.
     */
    public function getCountForTenant(Tenant $tenant): int
    {
        return Invoice::forTenant($tenant->id)->count();
    }
}
