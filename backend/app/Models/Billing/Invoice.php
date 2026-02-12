<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Enums\Billing\Currency;
use App\Enums\Billing\InvoiceStatus;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Invoice Model
 *
 * Represents a billing invoice for a tenant.
 *
 * @property string $id UUID primary key
 * @property string $tenant_id Tenant UUID
 * @property string|null $subscription_id Subscription UUID
 * @property string $invoice_number Invoice number (BIZ/YYYY-YY/NNNNN)
 * @property string|null $razorpay_invoice_id Razorpay invoice ID
 * @property InvoiceStatus $status Invoice status
 * @property Currency $currency Currency code
 * @property float $subtotal Subtotal amount
 * @property float $tax_amount Tax amount
 * @property float $total Total amount
 * @property float $amount_paid Amount paid
 * @property float $amount_due Amount due
 * @property array|null $gst_details GST breakdown
 * @property array $billing_address Billing address snapshot
 * @property array $line_items Invoice line items
 * @property \Carbon\Carbon|null $issued_at Issue date
 * @property \Carbon\Carbon|null $due_at Due date
 * @property \Carbon\Carbon|null $paid_at Payment date
 * @property string|null $pdf_url PDF download URL
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Tenant $tenant
 * @property-read Subscription|null $subscription
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Payment> $payments
 *
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> paid()
 * @method static Builder<static> unpaid()
 * @method static Builder<static> overdue()
 */
final class Invoice extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'invoices';

    /**
     * Invoice number prefix.
     */
    public const NUMBER_PREFIX = 'BIZ';

    /**
     * GST rate (18%).
     */
    public const GST_RATE = 0.18;

    /**
     * CGST/SGST rate (9% each).
     */
    public const CGST_SGST_RATE = 0.09;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'invoice_number',
        'razorpay_invoice_id',
        'status',
        'currency',
        'subtotal',
        'tax_amount',
        'total',
        'amount_paid',
        'amount_due',
        'gst_details',
        'billing_address',
        'line_items',
        'issued_at',
        'due_at',
        'paid_at',
        'pdf_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'currency' => Currency::class,
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'amount_due' => 'decimal:2',
            'gst_details' => 'array',
            'billing_address' => 'array',
            'line_items' => 'array',
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Bootstrap the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Invoice $invoice): void {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    /**
     * Get the tenant that owns the invoice.
     *
     * @return BelongsTo<Tenant, Invoice>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the subscription for this invoice.
     *
     * @return BelongsTo<Subscription, Invoice>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get payments for this invoice.
     *
     * @return HasMany<Payment>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get only paid invoices.
     *
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::PAID);
    }

    /**
     * Scope to get unpaid invoices (issued but not paid).
     *
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::ISSUED);
    }

    /**
     * Scope to get overdue invoices.
     *
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::ISSUED)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }

    /**
     * Generate an invoice number in the format BIZ/YYYY-YY/NNNNN.
     * Uses Indian financial year (April to March).
     */
    public static function generateInvoiceNumber(): string
    {
        $now = now();
        $month = $now->month;
        $year = $now->year;

        // Indian financial year: April to March
        // If we're in Jan-March, we're still in the previous FY
        if ($month < 4) {
            $fyStart = $year - 1;
            $fyEnd = $year;
        } else {
            $fyStart = $year;
            $fyEnd = $year + 1;
        }

        $fyString = sprintf('%d-%02d', $fyStart, $fyEnd % 100);

        // Get the next sequence number for this financial year
        $prefix = self::NUMBER_PREFIX . '/' . $fyString . '/';

        $lastInvoice = self::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice !== null) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -5);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Check if the invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::PAID;
    }

    /**
     * Check if the invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::ISSUED
            && $this->due_at !== null
            && $this->due_at->isPast();
    }

    /**
     * Get the formatted total amount with currency symbol.
     */
    public function getFormattedTotal(): string
    {
        $symbol = $this->currency->symbol();
        $formatted = number_format((float) $this->total, 2);

        return $symbol . $formatted;
    }

    /**
     * Mark the invoice as paid.
     */
    public function markAsPaid(): void
    {
        $this->status = InvoiceStatus::PAID;
        $this->paid_at = now();
        $this->amount_paid = $this->total;
        $this->amount_due = 0;
        $this->save();
    }

    /**
     * Mark the invoice as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->status = InvoiceStatus::CANCELLED;
        $this->save();
    }

    /**
     * Calculate GST for the invoice.
     * Returns CGST+SGST for same state, IGST for different state.
     *
     * @return array{gstin: string|null, place_of_supply: string, cgst: float, sgst: float, igst: float, total_gst: float}
     */
    public function calculateGst(string $customerState, string $businessState = 'Maharashtra'): array
    {
        $subtotal = (float) $this->subtotal;
        $isSameState = strtolower($customerState) === strtolower($businessState);

        if ($isSameState) {
            $cgst = round($subtotal * self::CGST_SGST_RATE, 2);
            $sgst = round($subtotal * self::CGST_SGST_RATE, 2);
            $igst = 0.0;
        } else {
            $cgst = 0.0;
            $sgst = 0.0;
            $igst = round($subtotal * self::GST_RATE, 2);
        }

        $totalGst = $cgst + $sgst + $igst;

        return [
            'gstin' => $this->billing_address['gstin'] ?? null,
            'place_of_supply' => $customerState,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => $igst,
            'total_gst' => $totalGst,
        ];
    }

    /**
     * Add a line item to the invoice.
     *
     * @param  array{description: string, quantity: int, unit_price: float, amount: float, hsn_code?: string}  $item
     */
    public function addLineItem(array $item): void
    {
        $lineItems = $this->line_items ?? [];
        $lineItems[] = $item;
        $this->line_items = $lineItems;
        $this->save();
    }

    /**
     * Get the invoice line items.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLineItems(): array
    {
        return $this->line_items ?? [];
    }
}
